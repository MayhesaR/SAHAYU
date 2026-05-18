<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ExpenseController extends Controller
{
    /**
     * Display a listing of petty cash operational expenses.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $category = $request->query('category');
        $sortBy = $request->query('sort_by', 'newest');

        $query = Expense::where('company_id', $companyId);

        if ($startDate) {
            $query->whereDate('expense_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('expense_date', '<=', $endDate);
        }
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($sortBy === 'highest') {
            $query->orderByDesc('amount');
        } else {
            $query->orderByDesc('expense_date')->orderByDesc('id');
        }

        $expenses = $query->paginate(15)->withQueryString();

        // Fetch statistics for cards scoped to tenant
        $todayExpensesSum = Expense::where('company_id', $companyId)->whereDate('expense_date', now()->toDateString())->sum('amount');
        $monthExpensesSum = Expense::where('company_id', $companyId)->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
        $totalExpensesCount = Expense::where('company_id', $companyId)->count();

        return view('ManajemenPengeluaran', [
            'expenses' => $expenses,
            'todayExpensesSum' => $todayExpensesSum,
            'monthExpensesSum' => $monthExpensesSum,
            'totalExpensesCount' => $totalExpensesCount,
        ]);
    }

    /**
     * Store a newly created operational expense.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'in:Listrik/Air,Transportasi,Perlengkapan,Gaji/Honor,Lain-lain'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['company_id'] = auth()->user()->company_id;

        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran operasional berhasil dicatat.');
    }

    /**
     * Remove the specified operational expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        // Enforce boundary checks
        if ($expense->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }

    /**
     * Export petty cash operational expenses to native styled Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;

            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $category = $request->query('category');
            $sortBy = $request->query('sort_by', 'newest');

            $query = Expense::where('company_id', $companyId);

            if ($startDate) {
                $query->whereDate('expense_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('expense_date', '<=', $endDate);
            }
            if ($category && $category !== 'all') {
                $query->where('category', $category);
            }

            if ($sortBy === 'highest') {
                $query->orderByDesc('amount');
            } else {
                $query->orderByDesc('expense_date')->orderByDesc('id');
            }

            $expenses = $query->get();

            // Construct PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Pengeluaran Operasional');
            $sheet->setShowGridlines(true);

            // Title & Metadata Header
            $sheet->setCellValue('A1', 'LAPORAN CATATAN PENGELUARAN (PETTY CASH)');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF990000'));

            $companyName = auth()->user()->company->name ?? 'SAHAYU Bakery';
            $sheet->setCellValue('A2', 'UMKM: ' . $companyName);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

            $periodStr = 'Semua Periode';
            if ($startDate && $endDate) {
                $periodStr = \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y');
            } elseif ($startDate) {
                $periodStr = 'Mulai ' . \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y');
            } elseif ($endDate) {
                $periodStr = 'Sampai ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y');
            }
            $sheet->setCellValue('A3', 'Periode Laporan: ' . $periodStr);
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

            $sheet->setCellValue('A4', 'Dicetak Oleh: ' . auth()->user()->name . ' | Waktu: ' . now()->translatedFormat('d F Y, H:i'));
            $sheet->getStyle('A4')->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF94A3B8'));

            // Table headers
            $sheet->setCellValue('A6', 'Waktu / Tanggal');
            $sheet->setCellValue('B6', 'Kategori Pengeluaran');
            $sheet->setCellValue('C6', 'Deskripsi / Keterangan');
            $sheet->setCellValue('D6', 'Nominal Pengeluaran (Rp)');

            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF990000'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];
            $sheet->getStyle('A6:D6')->applyFromArray($headerStyle);

            $rowNum = 7;
            foreach ($expenses as $exp) {
                $sheet->setCellValue('A' . $rowNum, $exp->expense_date->format('Y-m-d'));
                $sheet->setCellValue('B' . $rowNum, $exp->category);
                $sheet->setCellValue('C' . $rowNum, $exp->description ?: '-');
                $sheet->setCellValue('D' . $rowNum, (float)$exp->amount);
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
                $sheet->setCellValue('C' . $rowNum, 'TOTAL PENGELUARAN');
                $sheet->setCellValue('D' . $rowNum, '=SUM(D7:D' . $lastRow . ')');

                $sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getFont()->setBold(true);
                $sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');

                $sheet->getStyle('D7:D' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet->getStyle('D7:D' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A6:D' . $rowNum)->applyFromArray($borderStyle);
            } else {
                $sheet->setCellValue('A7', 'Belum ada data pengeluaran operasional.');
                $sheet->mergeCells('A7:D7');
                $sheet->getStyle('A7')->getFont()->setItalic(true);
                $sheet->getStyle('A6:D7')->applyFromArray($borderStyle);
            }

            for ($col = 'A'; $col <= 'D'; $col++) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $safeFilename = 'Pengeluaran_Operasional_' . now()->format('Y-m-d');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $safeFilename . '.xlsx"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Expense XLSX Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export XLSX: ' . $e->getMessage()]);
        }
    }
}
