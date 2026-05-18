<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $search = $request->query('search');
        $status = $request->query('status'); // 'all', 'active', 'inactive'

        $query = Customer::with(['sales', 'debts'])->where('company_id', $companyId);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where(function($q) {
                $q->has('sales')->orHas('debts');
            });
        } elseif ($status === 'inactive') {
            $query->doesntHave('sales')->doesntHave('debts');
        }

        $sort = $request->query('sort', 'name_asc');
        if ($sort === 'name_desc') {
            $query->orderBy('name', 'desc');
        } elseif ($sort === 'created_at_desc') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('ManajemenCustomer', [
            'customers' => $customers,
        ]);
    }

    /**
     * Export customer CRM database to native styled Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            $search = $request->query('search');
            $status = $request->query('status'); // 'all', 'active', 'inactive'

            $query = Customer::with(['sales', 'debts'])->where('company_id', $companyId);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            if ($status === 'active') {
                $query->where(function($q) {
                    $q->has('sales')->orHas('debts');
                });
            } elseif ($status === 'inactive') {
                $query->doesntHave('sales')->doesntHave('debts');
            }

            $sort = $request->query('sort', 'name_asc');
            if ($sort === 'name_desc') {
                $query->orderBy('name', 'desc');
            } elseif ($sort === 'created_at_desc') {
                $query->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('name', 'asc');
            }

            $customers = $query->get();

            // Construct PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Database Customer');
            $sheet->setShowGridlines(true);

            // Title & Metadata Header
            $sheet->setCellValue('A1', 'DATABASE CRM CUSTOMER & PIUTANG');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF005050'));

            $companyName = auth()->user()->company->name ?? 'SAHAYU Bakery';
            $sheet->setCellValue('A2', 'UMKM: ' . $companyName);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

            $sheet->setCellValue('A3', 'Dicetak Oleh: ' . auth()->user()->name . ' | Waktu: ' . now()->translatedFormat('d F Y, H:i'));
            $sheet->getStyle('A3')->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF94A3B8'));

            // Table headers
            $sheet->setCellValue('A5', 'Nama Pelanggan');
            $sheet->setCellValue('B5', 'Kontak (HP/WA)');
            $sheet->setCellValue('C5', 'Alamat');
            $sheet->setCellValue('D5', 'Total Belanja (Rp)');
            $sheet->setCellValue('E5', 'Total Kasbon (Rp)');
            $sheet->setCellValue('F5', 'Status Keaktifan');

            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF005050'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];
            $sheet->getStyle('A5:F5')->applyFromArray($headerStyle);

            $rowNum = 6;
            foreach ($customers as $c) {
                $totalBelanja = (float)$c->sales->sum('total');
                $sisaKasbon = (float)$c->debts->where('status', '!=', 'paid')->sum('remaining_amount');
                $statusKeaktifan = ($totalBelanja > 0 || $sisaKasbon > 0) ? 'AKTIF' : 'INAKTIF';

                $sheet->setCellValue('A' . $rowNum, $c->name);
                $sheet->setCellValue('B' . $rowNum, $c->phone ?: '-');
                $sheet->setCellValue('C' . $rowNum, $c->address ?: '-');
                $sheet->setCellValue('D' . $rowNum, $totalBelanja);
                $sheet->setCellValue('E' . $rowNum, $sisaKasbon);
                $sheet->setCellValue('F' . $rowNum, $statusKeaktifan);
                $rowNum++;
            }

            $lastRow = $rowNum - 1;

            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
            ];

            if ($lastRow >= 6) {
                // Summary row
                $sheet->setCellValue('C' . $rowNum, 'TOTAL KASBON PIUTANG');
                $sheet->setCellValue('E' . $rowNum, '=SUM(E6:E' . $lastRow . ')');

                $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
                $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0F2F1');

                $sheet->getStyle('D6:E' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet->getStyle('D6:E' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A5:F' . $rowNum)->applyFromArray($borderStyle);
            } else {
                $sheet->setCellValue('A6', 'Belum ada data pelanggan.');
                $sheet->mergeCells('A6:F6');
                $sheet->getStyle('A6')->getFont()->setItalic(true);
                $sheet->getStyle('A5:F6')->applyFromArray($borderStyle);
            }

            for ($col = 'A'; $col <= 'F'; $col++) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $safeFilename = 'Database_Customer_' . now()->format('Y-m-d');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $safeFilename . '.xlsx"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Customer XLSX Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export XLSX: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        Customer::create([
            'company_id' => auth()->user()->company_id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->route('customers.index')->with('success', 'Pelanggan baru berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        // Scope Check (Multi-tenancy audit defense)
        if ($customer->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya Admin yang diizinkan untuk menghapus data pelanggan.');
        }

        // Scope Check
        if ($customer->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil dihapus.');
    }
}
