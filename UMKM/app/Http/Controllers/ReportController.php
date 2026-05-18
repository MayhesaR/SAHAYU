<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Production;
use App\Models\Expense;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $data = $this->getReportData(request());
        return view('LaporanAnalisis', $data);
    }

    private function getReportData($request)
    {
        $companyId = auth()->user()->company_id;

        // ═══ Resolve Date Range from filters ═══
        if ($request->filled('filter_date')) {
            $date = $request->input('filter_date', Carbon::today()->toDateString());
            $startDate = Carbon::parse($date)->startOfDay();
            $endDate = Carbon::parse($date)->endOfDay();
            $periodLabel = $startDate->translatedFormat('d F Y');
            $activePeriod = 'harian';
            $specificMonth = $startDate->format('Y-m');
            $weekNumber = 1;
        } else {
            [$startDate, $endDate, $periodLabel, $activePeriod, $specificMonth, $weekNumber] = $this->resolveDateRange($request);
        }

        // 1. ISOLATE OPERATIONAL EXPENSES (Outside Loop)
        $operationalExpenses = Expense::whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        // 2. FETCH SALES FOR THE DAY
        $sales = Sale::with('items.product')->whereBetween('created_at', [$startDate, $endDate])->get();

        // 3. CALCULATE REVENUE (Accrual)
        $totalPendapatan = $sales->sum('total_amount');

        // 4. CALCULATE HPP (Loop ONLY for HPP)
        $totalHPP = 0;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                // Fallback to 60% of price if base_hpp is 0 or null
                $base_hpp = $item->product->base_hpp > 0 ? $item->product->base_hpp : ($item->product->price * 0.6);
                $totalHPP += ($item->quantity * $base_hpp);
            }
        }

        // 5. FINAL AGGREGATION (Outside Loop)
        $totalPengeluaran = $totalHPP + $operationalExpenses;
        $labaBersih = $totalPendapatan - $totalPengeluaran;
        $marginLaba = ($totalPendapatan > 0) ? ($labaBersih / $totalPendapatan) * 100 : 0;

        // Map variables back to view parameters & breakdown data
        $totalRevenue = $totalPendapatan;
        $totalExpense = $totalPengeluaran;
        $netProfit = $labaBersih;
        $profitMargin = $marginLaba;

        $totalMaterialCost = $totalHPP * 0.70;
        $totalLaborCost = $totalHPP * 0.20;
        $totalOverheadCost = $totalHPP * 0.10;

        // 5. Popular Products (scoped)
        $popularProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sale_items.company_id', $companyId)
            ->where('sales.company_id', $companyId)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.quantity * sale_items.price) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(3)
            ->get();

        // 6. Yield / Waste KPI (Reject Rate)
        $prodStats = Production::where('company_id', $companyId)
            ->where('status', 'done')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('SUM(good_quantity) as total_good, SUM(reject_quantity) as total_reject')
            ->first();
        
        $totalProduced = ($prodStats->total_good ?? 0) + ($prodStats->total_reject ?? 0);
        $rejectRate = $totalProduced > 0 ? (($prodStats->total_reject ?? 0) / $totalProduced) * 100 : 0;

        // 7. Expense Breakdown (HPP Breakdown)
        $expenseBreakdown = [
            'Bahan Baku' => $totalMaterialCost,
            'Tenaga Kerja' => $totalLaborCost,
            'Overhead' => $totalOverheadCost,
        ];

        // 8. Dynamic Chart & Trend Data based on view_mode
        $trendData = [];
        $anchorDate = Carbon::parse($startDate);
        $totalMonthlyTarget = 5000000; // Baseline target for a month

        if ($activePeriod === 'tahunan') {
            $prevRealization = 0;
            // 12 months loop
            for ($i = 1; $i <= 12; $i++) {
                $monthStart = $anchorDate->copy()->month($i)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();
                
                $realization = (float) Sale::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->sum('total');
                
                $hpp = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                    ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
                    ->where('sales.company_id', $companyId)
                    ->where('sale_items.company_id', $companyId)
                    ->select(DB::raw('SUM(sale_items.quantity * COALESCE(p_hpp.avg_hpp, products.selling_price * 0.6)) as total_cogs'))
                    ->value('total_cogs') ?? 0;

                $target = $prevRealization > 0 ? $prevRealization * 1.1 : $totalMonthlyTarget;
                $growth = $prevRealization > 0 ? (($realization - $prevRealization) / $prevRealization) * 100 : 0;
                
                $trendData[] = [
                    'label' => $monthStart->translatedFormat('F'), // e.g., "Januari"
                    'target' => $target,
                    'realization' => $realization,
                    'hpp' => (float)$hpp,
                    'growth' => $growth,
                    'status' => $realization >= $target ? 'Exceeded' : ($realization >= $target * 0.8 ? 'Near Target' : 'Under Target'),
                ];
                
                $prevRealization = $realization;
            }
        } elseif ($activePeriod === 'bulanan') {
            $prevRealization = 0;
            $weeklyTarget = $totalMonthlyTarget / 5; // Flat target per week bucket

            // 5 Weekly Buckets Loop for the anchor month
            for ($week = 1; $week <= 5; $week++) {
                $startDay = (($week - 1) * 7) + 1;
                
                // Handle cases where the month has fewer days than the start of Week 5 (e.g. Feb)
                if ($startDay > $anchorDate->daysInMonth) {
                    $realization = 0;
                    $hpp = 0;
                } else {
                    $weekStart = $anchorDate->copy()->addDays($startDay - 1)->startOfDay();
                    
                    if ($week >= 5) {
                        $weekEnd = $anchorDate->copy()->endOfMonth()->endOfDay();
                    } else {
                        $endDay = min($startDay + 6, $anchorDate->daysInMonth);
                        $weekEnd = $anchorDate->copy()->addDays($endDay - 1)->endOfDay();
                    }

                    $realization = (float) Sale::where('company_id', $companyId)
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->sum('total');
                    
                    // Calculate HPP specifically for this weekly bucket
                    $hpp = DB::table('sale_items')
                        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                        ->whereBetween('sales.created_at', [$weekStart, $weekEnd])
                        ->where('sales.company_id', $companyId)
                        ->where('sale_items.company_id', $companyId)
                        ->select(DB::raw('SUM(sale_items.quantity * COALESCE(p_hpp.avg_hpp, products.selling_price * 0.6)) as total_cogs'))
                        ->value('total_cogs') ?? 0;
                }
                
                $target = $weeklyTarget;
                $growth = $prevRealization > 0 ? (($realization - $prevRealization) / $prevRealization) * 100 : 0;
                
                $trendData[] = [
                    'label' => 'Minggu ' . $week,
                    'target' => $target,
                    'realization' => $realization,
                    'hpp' => (float)$hpp,
                    'growth' => $growth,
                    'status' => $realization >= $target ? 'Exceeded' : ($realization >= $target * 0.8 ? 'Near Target' : 'Under Target'),
                ];
                
                $prevRealization = $realization;
            }
        } elseif ($activePeriod === 'mingguan') {
            $dailyTarget = $totalMonthlyTarget / $anchorDate->daysInMonth;
            $currentDate = $startDate->copy();
            $prevRealization = 0;

            while ($currentDate->lte($endDate)) {
                $dayStart = $currentDate->copy()->startOfDay();
                $dayEnd = $currentDate->copy()->endOfDay();
                
                $realization = (float) Sale::where('company_id', $companyId)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('total');
                
                $hpp = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                    ->whereBetween('sales.created_at', [$dayStart, $dayEnd])
                    ->where('sales.company_id', $companyId)
                    ->where('sale_items.company_id', $companyId)
                    ->select(DB::raw('SUM(sale_items.quantity * COALESCE(p_hpp.avg_hpp, products.selling_price * 0.6)) as total_cogs'))
                    ->value('total_cogs') ?? 0;

                // Flat target for daily granularity
                $target = $dailyTarget;
                $growth = $prevRealization > 0 ? (($realization - $prevRealization) / $prevRealization) * 100 : 0;
                
                $trendData[] = [
                    'label' => $currentDate->translatedFormat('D, d M'),
                    'target' => $target,
                    'realization' => $realization,
                    'hpp' => (float)$hpp,
                    'growth' => $growth,
                    'status' => $realization >= $target ? 'Exceeded' : ($realization >= $target * 0.8 ? 'Near Target' : 'Under Target'),
                ];
                
                $prevRealization = $realization;
                $currentDate->addDay();
            }
        } elseif ($activePeriod === 'harian') {
            $prevRealization = 0;
            // 4-hour buckets for harian trend (00:00, 04:00, 08:00, 12:00, 16:00, 20:00)
            for ($hour = 0; $hour < 24; $hour += 4) {
                $bucketStart = $startDate->copy()->hour($hour)->minute(0)->second(0);
                $bucketEnd = $bucketStart->copy()->addHours(4)->subSecond();

                $realization = (float) Sale::where('company_id', $companyId)
                    ->whereBetween('created_at', [$bucketStart, $bucketEnd])
                    ->sum('total');

                $hpp = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                    ->whereBetween('sales.created_at', [$bucketStart, $bucketEnd])
                    ->where('sales.company_id', $companyId)
                    ->where('sale_items.company_id', $companyId)
                    ->select(DB::raw('SUM(sale_items.quantity * COALESCE(p_hpp.avg_hpp, products.selling_price * 0.6)) as total_cogs'))
                    ->value('total_cogs') ?? 0;

                $target = 500000 / 6; // Benchmark daily target partitioned
                $growth = $prevRealization > 0 ? (($realization - $prevRealization) / $prevRealization) * 100 : 0;

                $trendData[] = [
                    'label' => sprintf('%02d:00 - %02d:00', $hour, $hour + 4),
                    'target' => $target,
                    'realization' => $realization,
                    'hpp' => (float)$hpp,
                    'growth' => $growth,
                    'status' => $realization >= $target ? 'Exceeded' : ($realization >= $target * 0.8 ? 'Near Target' : 'Under Target'),
                ];

                $prevRealization = $realization;
            }
        }

        return [
            'totalRevenue' => $totalRevenue,
            'netProfit' => $netProfit,
            'totalExpense' => $totalExpense,
            'profitMargin' => $profitMargin,
            'rejectRate' => $rejectRate,
            'expenseBreakdown' => $expenseBreakdown,
            'popularProducts' => $popularProducts,
            'trendData' => $trendData,
            'periodLabel' => $periodLabel,
            'activePeriod' => $activePeriod,
            'specificMonth' => $specificMonth,
            'weekNumber' => $weekNumber,
            'filterDate' => $request->input('filter_date'),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * Resolve the date range from the current request.
     *
     * Priority: specific_month (Anchor) -> view_mode -> week_number
     */
    private function resolveDateRange($request): array
    {
        $specificMonth = $request->query('specific_month', Carbon::now()->format('Y-m'));
        $viewMode = $request->query('view_mode', 'bulanan');
        $weekNumber = (int) $request->query('week_number', 1);

        try {
            $anchorDate = Carbon::createFromFormat('Y-m', $specificMonth)->startOfMonth();
        } catch (\Exception $e) {
            $anchorDate = Carbon::now()->startOfMonth();
            $specificMonth = $anchorDate->format('Y-m');
        }

        switch ($viewMode) {
            case 'tahunan':
                $startDate = $anchorDate->copy()->startOfYear();
                $endDate = $anchorDate->copy()->endOfYear();
                $periodLabel = 'Tahun ' . $anchorDate->year;
                break;

            case 'mingguan':
                // Week 1: 1-7, Week 2: 8-14, Week 3: 15-21, Week 4: 22-28, Week 5: 29-end
                $startDay = (($weekNumber - 1) * 7) + 1;
                
                if ($startDay > $anchorDate->daysInMonth) {
                    $startDay = $anchorDate->daysInMonth;
                }

                $startDate = $anchorDate->copy()->addDays($startDay - 1)->startOfDay();
                
                if ($weekNumber >= 5) {
                    $endDate = $anchorDate->copy()->endOfMonth()->endOfDay();
                } else {
                    $endDay = min($startDay + 6, $anchorDate->daysInMonth);
                    $endDate = $anchorDate->copy()->addDays($endDay - 1)->endOfDay();
                }
                
                $periodLabel = 'Minggu ke-' . $weekNumber . ' ' . $anchorDate->translatedFormat('F Y');
                break;

            case 'bulanan':
            default:
                $viewMode = 'bulanan';
                $startDate = $anchorDate->copy()->startOfMonth()->startOfDay();
                $endDate = $anchorDate->copy()->endOfMonth()->endOfDay();
                $periodLabel = $anchorDate->translatedFormat('F Y');
                break;
        }

        return [$startDate, $endDate, $periodLabel, $viewMode, $specificMonth, $weekNumber];
    }

    public function exportExcel()
    {
        try {
            $data = $this->getReportData(request());
            $trendData = array_reverse($data['trendData']);

            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=laporan-performa-" . now()->format('Y-m-d') . ".csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function() use ($trendData) {
                $file = fopen('php://output', 'w');

                // Header CSV (BOM untuk dukungan UTF-8 di Excel)
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['Interval Waktu / Periode', 'Target Penjualan (Rp)', 'Realisasi (Rp)', 'Pencapaian (%)', 'Status'], ';');

                foreach ($trendData as $row) {
                    fputcsv($file, [
                        $row['label'],
                        number_format($row['target'], 0, ',', ''),
                        number_format($row['realization'], 0, ',', ''),
                        number_format($row['growth'], 2, ',', ''),
                        $row['status']
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('CSV Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['csv' => 'Gagal generate CSV: ' . $e->getMessage()]);
        }
    }

    public function exportPdf()
    {
        // Set very high memory limit for PDF generation
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $data = $this->getReportData(request());

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.export_pdf', $data);

            $pdf->setOption('dpi', 96);
            $pdf->setOption('enable_font_subsetting', true);

            return $pdf->download('laporan-performa-bisnis-'.now()->format('Y-m-d').'.pdf');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['pdf' => 'Gagal generate PDF: ' . $e->getMessage()]);
        }
    }

    public function exportGoogleSheets(\App\Services\SpreadsheetExportService $exportService)
    {
        try {
            $companyId = auth()->user()->company_id;
            $data = $this->getReportData(request());
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];
            $periodLabel = $data['periodLabel'];

            // Fetch Top 5 popular products specifically for this range
            $popularProducts = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sale_items.company_id', $companyId)
                ->where('sales.company_id', $companyId)
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.quantity * sale_items.price) as total_revenue'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_revenue')
                ->limit(5)
                ->get();

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            // --- SHEET 1: Ringkasan Performa ---
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Ringkasan Performa');
            $sheet1->setShowGridlines(true);

            // Title & Headers
            $sheet1->setCellValue('A1', 'LAPORAN PERFORMA BISNIS (ACCRUAL BASIS)');
            $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F766E'));

            $sheet1->setCellValue('A2', 'UMKM: ' . (auth()->user()->company->name ?? 'SAHAYU UMKM'));
            $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

            $sheet1->setCellValue('A3', 'Periode Laporan: ' . $periodLabel);
            $sheet1->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

            $sheet1->setCellValue('A4', 'Dicetak Oleh: ' . auth()->user()->name . ' | Waktu: ' . now()->translatedFormat('d F Y, H:i'));
            $sheet1->getStyle('A4')->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF94A3B8'));

            // SECTION I: METRIK UTAMA
            $sheet1->setCellValue('A6', 'I. METRIK UTAMA');
            $sheet1->getStyle('A6')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F766E'));

            $sheet1->setCellValue('A7', 'Metrik');
            $sheet1->setCellValue('B7', 'Nilai / Rasio');
            $sheet1->getStyle('A7:B7')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet1->getStyle('A7:B7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D9488');
            $sheet1->getStyle('A7:B7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet1->setCellValue('A8', 'Nilai Barang Terjual');
            $sheet1->setCellValue('B8', (float)$data['totalRevenue']);

            $sheet1->setCellValue('A9', 'Total Modal (HPP) & Operasional');
            $sheet1->setCellValue('B9', (float)$data['totalExpense']);

            $sheet1->setCellValue('A10', 'Margin Laba Penjualan');
            $sheet1->setCellValue('B10', '=B8-B9');

            $sheet1->setCellValue('A11', 'Rasio Margin Laba');
            $sheet1->setCellValue('B11', '=IF(B8>0, B10/B8, 0)');

            // Styling Metrik
            $sheet1->getStyle('B8:B10')->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet1->getStyle('B11')->getNumberFormat()->setFormatCode('0.0%');
            $sheet1->getStyle('A8:B11')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet1->getStyle('B8:B11')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            // Borders for Metrik table
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
            ];
            $sheet1->getStyle('A7:B11')->applyFromArray($borderStyle);

            // SECTION II: STRUKTUR BIAYA MODAL (HPP)
            $sheet1->setCellValue('A13', 'II. STRUKTUR BIAYA MODAL (HPP)');
            $sheet1->getStyle('A13')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F766E'));

            $sheet1->setCellValue('A14', 'Komponen Biaya');
            $sheet1->setCellValue('B14', 'Estimasi Porsi (%)');
            $sheet1->setCellValue('C14', 'Nilai Rupiah');
            $sheet1->getStyle('A14:C14')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet1->getStyle('A14:C14')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D9488');

            $sheet1->setCellValue('A15', 'Bahan Baku');
            $sheet1->setCellValue('B15', 0.70);
            $sheet1->setCellValue('C15', (float)$data['expenseBreakdown']['Bahan Baku']);

            $sheet1->setCellValue('A16', 'Tenaga Kerja');
            $sheet1->setCellValue('B16', 0.20);
            $sheet1->setCellValue('C16', (float)$data['expenseBreakdown']['Tenaga Kerja']);

            $sheet1->setCellValue('A17', 'Overhead');
            $sheet1->setCellValue('B17', 0.10);
            $sheet1->setCellValue('C17', (float)$data['expenseBreakdown']['Overhead']);

            $sheet1->setCellValue('A18', 'Total HPP (Modal Pokok)');
            $sheet1->setCellValue('B18', '=SUM(B15:B17)');
            $sheet1->setCellValue('C18', '=SUM(C15:C17)');

            $sheet1->getStyle('B15:B18')->getNumberFormat()->setFormatCode('0%');
            $sheet1->getStyle('C15:C18')->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet1->getStyle('B15:C18')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle('A18:C18')->getFont()->setBold(true);
            $sheet1->getStyle('A18:C18')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
            $sheet1->getStyle('A14:C18')->applyFromArray($borderStyle);

            // SECTION III: TOP 5 PRODUK TERLARIS
            $sheet1->setCellValue('A20', 'III. TOP 5 PRODUK TERLARIS');
            $sheet1->getStyle('A20')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F766E'));

            $sheet1->setCellValue('A21', 'Nama Produk');
            $sheet1->setCellValue('B21', 'Volume Terjual');
            $sheet1->setCellValue('C21', 'Total Omzet');
            $sheet1->getStyle('A21:C21')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet1->getStyle('A21:C21')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D9488');

            $rowNum = 22;
            foreach ($popularProducts as $p) {
                $sheet1->setCellValue('A' . $rowNum, $p->name);
                $sheet1->setCellValue('B' . $rowNum, (float)$p->total_qty);
                $sheet1->setCellValue('C' . $rowNum, (float)$p->total_revenue);
                $rowNum++;
            }
            if ($rowNum == 22) {
                $sheet1->setCellValue('A22', 'Belum ada data produk terpopuler.');
                $sheet1->mergeCells('A22:C22');
                $sheet1->getStyle('A22')->getFont()->setItalic(true);
                $rowNum = 23;
            }
            $sheet1->getStyle('B22:B' . ($rowNum - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet1->getStyle('C22:C' . ($rowNum - 1))->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet1->getStyle('B22:C' . ($rowNum - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle('A21:C' . ($rowNum - 1))->applyFromArray($borderStyle);

            $sheet1->getColumnDimension('A')->setAutoSize(true);
            $sheet1->getColumnDimension('B')->setAutoSize(true);
            $sheet1->getColumnDimension('C')->setAutoSize(true);

            // --- SHEET 2: Rincian Transaksi ---
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Rincian Transaksi');
            $sheet2->setShowGridlines(true);

            $sheet2->setCellValue('A1', 'RINCIAN TRANSAKSI (AUDIT TRAIL)');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F766E'));

            $sheet2->setCellValue('A2', 'UMKM: ' . (auth()->user()->company->name ?? 'SAHAYU UMKM'));
            $sheet2->setCellValue('A3', 'Periode Laporan: ' . $periodLabel);
            $sheet2->getStyle('A2:A3')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

            $sheet2->setCellValue('A5', 'Tanggal & Waktu');
            $sheet2->setCellValue('B5', 'Nomor Nota');
            $sheet2->setCellValue('C5', 'Nama Pelanggan');
            $sheet2->setCellValue('D5', 'Rincian Item (Nama Produk x Qty)');
            $sheet2->setCellValue('E5', 'Total Omzet Nota');
            $sheet2->setCellValue('F5', 'Total HPP Nota');
            $sheet2->setCellValue('G5', 'Laba Nota');

            $sheet2->getStyle('A5:G5')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet2->getStyle('A5:G5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D9488');
            $sheet2->getStyle('A5:G5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Query all Sales strictly within start and end date
            $salesDetail = Sale::with(['items.product', 'customer'])
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'asc')
                ->get();

            $rowNum = 6;
            foreach ($salesDetail as $sale) {
                $sheet2->setCellValue('A' . $rowNum, $sale->created_at->format('Y-m-d H:i'));
                $sheet2->setCellValue('B' . $rowNum, sprintf('TRX-%05d', $sale->id));

                $custName = $sale->customer;
                if (!$custName && $sale->customer()->exists()) {
                    $custName = $sale->customer->name;
                }
                $sheet2->setCellValue('C' . $rowNum, $custName ?: 'Umum');

                $itemsStr = $sale->items->map(function($item) {
                    return ($item->product->name ?? 'Produk') . ' x ' . $item->quantity;
                })->implode(', ');
                $sheet2->setCellValue('D' . $rowNum, $itemsStr);

                $sheet2->setCellValue('E' . $rowNum, (float)$sale->total);

                // Calculate Sale HPP
                $saleHpp = 0;
                foreach ($sale->items as $item) {
                    $base_hpp = $item->product->base_hpp > 0 ? $item->product->base_hpp : ($item->product->price * 0.6);
                    $saleHpp += ($item->quantity * $base_hpp);
                }
                $sheet2->setCellValue('F' . $rowNum, (float)$saleHpp);

                $sheet2->setCellValue('G' . $rowNum, '=E' . $rowNum . '-F' . $rowNum);

                $rowNum++;
            }

            $lastRow = $rowNum - 1;

            // Add Totals row
            if ($lastRow >= 6) {
                $sheet2->setCellValue('C' . $rowNum, 'TOTAL');
                $sheet2->setCellValue('E' . $rowNum, '=SUM(E6:E' . $lastRow . ')');
                $sheet2->setCellValue('F' . $rowNum, '=SUM(F6:F' . $lastRow . ')');
                $sheet2->setCellValue('G' . $rowNum, '=SUM(G6:G' . $lastRow . ')');

                $sheet2->getStyle('A' . $rowNum . ':G' . $rowNum)->getFont()->setBold(true);
                $sheet2->getStyle('A' . $rowNum . ':G' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');

                $sheet2->getStyle('E6:G' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet2->getStyle('E6:G' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                $sheet2->getStyle('A5:G' . $rowNum)->applyFromArray($borderStyle);
            } else {
                $sheet2->setCellValue('A6', 'Belum ada data transaksi pada periode terpilih.');
                $sheet2->mergeCells('A6:G6');
                $sheet2->getStyle('A6')->getFont()->setItalic(true);
                $sheet2->getStyle('A5:G6')->applyFromArray($borderStyle);
            }

            // Auto size sheet 2 columns
            for ($col = 'A'; $col <= 'G'; $col++) {
                $sheet2->getColumnDimension($col)->setAutoSize(true);
            }

            // Set active sheet to Summary
            $spreadsheet->setActiveSheetIndex(0);

            // Set response
            $safeFilename = 'Laporan_Performa_Bisnis_' . now()->format('Y-m-d');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $safeFilename . '.xlsx"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('XLSX Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export spreadsheet: ' . $e->getMessage()]);
        }
    }
}
