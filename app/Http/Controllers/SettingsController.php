<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $company = $user->company;
        
        // Fetch all users in the same company so the owner/staff can change their password
        $companyUsers = User::where('company_id', $user->company_id)->orderBy('name')->get();

        return view('settings.index', compact('user', 'company', 'companyUsers'));
    }

    public function updateCompany(Request $request)
    {
        $user = auth()->user();
        $company = $user->company;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $file = $request->file('logo');
            $path = $file->store('logos', 'public');
            $validated['logo'] = $path;
        }

        $company->update($validated);

        return back()->with('success', 'Informasi toko berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        if ($request->filled('user_id') && $request->input('user_id') != auth()->id()) {
            // Admin/Staff changing another user's password
            $validated = $request->validate([
                'user_id' => ['required', 'exists:users,id'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $targetUser = User::findOrFail($validated['user_id']);

            // Security check: must be in same company
            if ($targetUser->company_id !== auth()->user()->company_id) {
                abort(403, 'Aksi tidak diizinkan.');
            }

            $targetUser->update([
                'password' => Hash::make($validated['password']),
            ]);

            return back()->with('success', "Kata sandi untuk akun {$targetUser->name} berhasil diperbarui.");
        } else {
            // Changing own password
            $validated = $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            auth()->user()->update([
                'password' => Hash::make($validated['password']),
            ]);

            return back()->with('success', 'Kata sandi Anda berhasil diperbarui.');
        }
    }

    public function updatePrinter(Request $request)
    {
        $user = auth()->user();
        $company = $user->company;

        $validated = $request->validate([
            'printer_paper_width' => ['required', 'in:58mm,80mm'],
        ]);

        $company->update($validated);

        return back()->with('success', 'Konfigurasi printer thermal berhasil disimpan.');
    }

    public function downloadBackup(Request $request)
    {
        return response()->streamDownload(function() {
            $pdo = DB::connection()->getPdo();
            $driver = DB::getDriverName();
            
            echo "-- SAHAYU Database Backup\n";
            echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            echo "-- Driver: " . $driver . "\n\n";

            if ($driver === 'sqlite') {
                echo "PRAGMA foreign_keys = OFF;\n\n";
            } else {
                echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
            }

            // Get tables
            $tables = [];
            if ($driver === 'sqlite') {
                $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($rows as $row) {
                    $tables[] = $row->name;
                }
            } else {
                $rows = DB::select('SHOW TABLES');
                foreach ($rows as $row) {
                    $tables[] = reset($row);
                }
            }

            foreach ($tables as $tableName) {
                // Schema
                if ($driver === 'sqlite') {
                    $schemaQuery = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$tableName]);
                    $createSql = $schemaQuery[0]->sql ?? '';
                } else {
                    $schemaQuery = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    $createSql = $schemaQuery[0]->{'Create Table'} ?? '';
                }

                echo "-- ------------------------------------------------------\n";
                echo "-- Table structure for `{$tableName}`\n";
                echo "-- ------------------------------------------------------\n";
                echo "DROP TABLE IF EXISTS `{$tableName}`;\n";
                echo $createSql . ";\n\n";

                echo "-- ------------------------------------------------------\n";
                echo "-- Dumping data for table `{$tableName}`\n";
                echo "-- ------------------------------------------------------\n";

                $offset = 0;
                $limit = 500;
                while (true) {
                    $rows = DB::table($tableName)->offset($offset)->limit($limit)->get();
                    if ($rows->isEmpty()) {
                        break;
                    }
                    foreach ($rows as $row) {
                        $columns = [];
                        $values = [];
                        foreach ((array)$row as $column => $value) {
                            $columns[] = "`{$column}`";
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = $pdo->quote($value);
                            }
                        }
                        echo "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $offset += $limit;
                }
                echo "\n";
            }

            if ($driver === 'sqlite') {
                echo "PRAGMA foreign_keys = ON;\n";
            } else {
                echo "SET FOREIGN_KEY_CHECKS=1;\n";
            }
        }, 'SAHAYU_Backup_' . date('Y-m-d_H-i-s') . '.sql');
    }
}
