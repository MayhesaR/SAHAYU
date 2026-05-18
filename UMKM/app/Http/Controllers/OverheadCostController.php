<?php

namespace App\Http\Controllers;

use App\Models\OverheadCost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OverheadCostController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->query('month', now()->month);
        $selectedYear = $request->query('year', now()->year);

        $query = OverheadCost::query()
            ->whereMonth('transaction_date', $selectedMonth)
            ->whereYear('transaction_date', $selectedYear);

        $overheadCosts = (clone $query)->latest('transaction_date')->get();
        $totalOverhead = (clone $query)->sum('cost');

        $categoryBreakdown = (clone $query)
            ->select('category', DB::raw('SUM(cost) as total'))
            ->groupBy('category')
            ->get();

        return view('ManajemenOverhead', [
            'overheadCosts' => $overheadCosts,
            'totalOverhead' => $totalOverhead,
            'categoryBreakdown' => $categoryBreakdown,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ],
            'years' => OverheadCost::selectRaw('YEAR(transaction_date) as year')
                ->distinct()
                ->pluck('year')
                ->push(now()->year)
                ->unique()
                ->sortDesc(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'cost' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
        ]);

        $validated['company_id'] = auth()->user()->company_id;

        OverheadCost::create($validated);

        return redirect()->route('overhead.index', [
            'month' => Carbon::parse($validated['transaction_date'])->month,
            'year' => Carbon::parse($validated['transaction_date'])->year
        ])->with('success', 'Biaya operasional berhasil ditambahkan.');
    }

    public function destroy(OverheadCost $overheadCost): RedirectResponse
    {
        // Scope Check (Multi-tenancy audit defense)
        if ($overheadCost->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $overheadCost->delete();

        return redirect()->back()->with('success', 'Biaya operasional berhasil dihapus.');
    }

    /**
     * Export overhead costs to native styled Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            $selectedMonth = $request->query('month', now()->month);
            $selectedYear = $request->query('year', now()->year);

            $query = OverheadCost::where('company_id', $companyId)
                ->whereMonth('transaction_date', $selectedMonth)
                ->whereYear('transaction_date', $selectedYear);

            $overheadCosts = $query->latest('transaction_date')->get();

            // Construct PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Biaya Operasional');
            $sheet->setShowGridlines(true);

            // Title & Metadata Header
            $sheet->setCellValue('A1', 'LAPORAN MASTER BIAYA OPERASIONAL (OVERHEAD)');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E3A8A'));

            $companyName = auth()->user()->company->name ?? 'SAHAYU Bakery';
            $sheet->setCellValue('A2', 'UMKM: ' . $companyName);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

            $months = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $periodStr = ($months[(int)$selectedMonth] ?? '') . ' ' . $selectedYear;
            $sheet->setCellValue('A3', 'Periode Laporan: ' . $periodStr);
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

            $sheet->setCellValue('A4', 'Dicetak Oleh: ' . auth()->user()->name . ' | Waktu: ' . now()->translatedFormat('d F Y, H:i'));
            $sheet->getStyle('A4')->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF94A3B8'));

            // Table headers
            $sheet->setCellValue('A6', 'Waktu / Tanggal');
            $sheet->setCellValue('B6', 'Nama Pengeluaran');
            $sheet->setCellValue('C6', 'Kategori');
            $sheet->setCellValue('D6', 'Biaya (Rp)');

            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A8A'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];
            $sheet->getStyle('A6:D6')->applyFromArray($headerStyle);

            $rowNum = 7;
            foreach ($overheadCosts as $cost) {
                $sheet->setCellValue('A' . $rowNum, $cost->transaction_date->format('Y-m-d'));
                $sheet->setCellValue('B' . $rowNum, $cost->name);
                $sheet->setCellValue('C' . $rowNum, $cost->category);
                $sheet->setCellValue('D' . $rowNum, (float)$cost->cost);
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

            if ($lastRow >= 7) {
                // Summary row
                $sheet->setCellValue('C' . $rowNum, 'TOTAL BIAYA OVERHEAD');
                $sheet->setCellValue('D' . $rowNum, '=SUM(D7:D' . $lastRow . ')');

                $sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
                $sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');

                $sheet->getStyle('D7:D' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet->getStyle('D7:D' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A6:D' . $rowNum)->applyFromArray($borderStyle);
            } else {
                $sheet->setCellValue('A7', 'Belum ada data biaya operasional.');
                $sheet->mergeCells('A7:D7');
                $sheet->getStyle('A7')->getFont()->setItalic(true);
                $sheet->getStyle('A6:D7')->applyFromArray($borderStyle);
            }

            for ($col = 'A'; $col <= 'D'; $col++) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $safeFilename = 'Biaya_Operasional_' . now()->format('Y-m-d');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $safeFilename . '.xlsx"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Overhead XLSX Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export XLSX: ' . $e->getMessage()]);
        }
    }
}
