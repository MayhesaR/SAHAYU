<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(['name' => 'SAHAYU Bakery']);

        User::updateOrCreate(
            ['email' => 'admin@umkm.com'],
            [
                'company_id' => $company->id,
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@umkm.com'],
            [
                'company_id' => $company->id,
                'name' => 'Staff Kasir',
                'password' => Hash::make('password123'),
                'role' => 'staff',
            ]
        );
    }
}
