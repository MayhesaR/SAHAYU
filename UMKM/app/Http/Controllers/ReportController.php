<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $request = request();
        $driver = DB::connection()->getDriverName();

        // ═══ Resolve Date Range from filters ═══
        [$startDate, $endDate, $periodLabel, $activePeriod, $specificMonth] = $this->resolveDateRange($request);

        // 1. Revenue (scoped)
        $totalRevenue = Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total');

        // 2. COGS (Cost of Goods Sold / HPP) — scoped to sales within the date range
        $companyId = auth()->user()->company_id;
        $totalCogs = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.company_id', $companyId)
            ->select(DB::raw('SUM(sale_items.quantity * COALESCE(p_hpp.avg_hpp, products.selling_price * 0.6)) as total_cogs'))
            ->value('total_cogs') ?? 0;

        // 3. Operational Expenses (Overhead) — scoped
        $totalOverheadExpense = OverheadCost::whereBetween('transaction_date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])->sum('cost');

        // Total Expense = HPP + Overhead
        $totalExpense = $totalCogs + $totalOverheadExpense;

        // 4. Net Profit
        $netProfit = $totalRevenue - $totalExpense;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // 5. Popular Products (scoped)
        $popularProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.quantity * sale_items.price) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(3)
            ->get();

        // 6. Yield / Waste KPI (Reject Rate)
        $prodStats = Production::where('status', 'done')
            ->whereBetween('production_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('SUM(good_quantity) as total_good, SUM(reject_quantity) as total_reject')
            ->first();
        
        $totalProduced = ($prodStats->total_good ?? 0) + ($prodStats->total_reject ?? 0);
        $rejectRate = $totalProduced > 0 ? (($prodStats->total_reject ?? 0) / $totalProduced) * 100 : 0;

        // 7. Expense Breakdown (HPP Breakdown)
        $expenseBreakdownQuery = Production::where('status', 'done')
            ->whereBetween('production_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('SUM(material_cost_snapshot) as total_material, SUM(labor_cost) as total_labor, SUM(overhead_cost_snapshot) as total_overhead')
            ->first();
            
        $expenseBreakdown = [
            'Bahan Baku' => $expenseBreakdownQuery->total_material ?? 0,
            'Tenaga Kerja' => $expenseBreakdownQuery->total_labor ?? 0,
            'Overhead' => $expenseBreakdownQuery->total_overhead ?? 0,
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
                
                $realization = (float) Sale::whereBetween('created_at', [$monthStart, $monthEnd])->sum('total');
                
                $hpp = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                    ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
                    ->where('sales.company_id', $companyId)
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

                    $realization = (float) Sale::whereBetween('created_at', [$weekStart, $weekEnd])->sum('total');
                    
                    // Calculate HPP specifically for this weekly bucket
                    $hpp = DB::table('sale_items')
                        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                        ->whereBetween('sales.created_at', [$weekStart, $weekEnd])
                        ->where('sales.company_id', $companyId)
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
                
                $realization = (float) Sale::whereBetween('created_at', [$dayStart, $dayEnd])->sum('total');
                
                $hpp = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                    ->whereBetween('sales.created_at', [$dayStart, $dayEnd])
                    ->where('sales.company_id', $companyId)
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
        }

        return view('LaporanAnalisis', [
            'totalRevenue' => $totalRevenue,
            'netProfit' => $netProfit,
            'totalExpense' => $totalExpense,
            'profitMargin' => $profitMargin,
            'rejectRate' => $rejectRate,
            'expenseBreakdown' => $expenseBreakdown,
            'popularProducts' => $popularProducts,
            'trendData' => $trendData,
            // Filter state for the UI
            'periodLabel' => $periodLabel,
            'activePeriod' => $activePeriod,
            'specificMonth' => $specificMonth,
            'weekNumber' => request('week_number', 1),
        ]);
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
        $monthlySales = Sale::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
            ->selectRaw('SUM(total) as realization')
            ->groupBy('ym')
            ->orderBy('ym', 'asc')
            ->get();

        $monthlyGrowth = [];
        $prevRealization = 0;

        foreach ($monthlySales as $row) {
            $realization = (float) $row->realization;
            $target = $prevRealization > 0 ? $prevRealization * 1.1 : 5000000;
            $growth = $prevRealization > 0 ? (($realization - $prevRealization) / $prevRealization) * 100 : 0;

            try {
                $monthLabel = Carbon::createFromFormat('Y-m', $row->ym)->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthLabel = $row->ym;
            }

            $monthlyGrowth[] = [
                'month' => $monthLabel,
                'target' => $target,
                'realization' => $realization,
                'growth' => $growth,
                'status' => $realization >= $target ? 'Exceeded' : ($realization >= $target * 0.8 ? 'Near Target' : 'Under Target'),
            ];

            $prevRealization = $realization;
        }

        // Ambil 6 bulan terakhir
        $monthlyGrowth = array_slice(array_reverse($monthlyGrowth), 0, 6);
        $dataToExport = array_reverse($monthlyGrowth);

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=laporan-pertumbuhan.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($dataToExport) {
            $file = fopen('php://output', 'w');

            // Header CSV (BOM untuk dukungan UTF-8 di Excel)
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Bulan', 'Target Penjualan (Rp)', 'Realisasi (Rp)', 'Pertumbuhan (%)', 'Status'], ';');

            foreach ($dataToExport as $row) {
                fputcsv($file, [
                    $row['month'],
                    number_format($row['target'], 0, ',', ''),
                    number_format($row['realization'], 0, ',', ''),
                    number_format($row['growth'], 2, ',', ''),
                    $row['status']
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf()
    {
        // Set very high memory limit for PDF generation
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $driver = DB::connection()->getDriverName();
            $totalRevenue = Sale::sum('total');
            $companyId = auth()->user()->company_id;
            $totalCogs = DB::table('sale_items')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin(DB::raw("(SELECT product_id, AVG(unit_hpp_snapshot) as avg_hpp FROM productions WHERE status = 'done' AND company_id = {$companyId} GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
                ->where('sale_items.company_id', $companyId)
                ->select(DB::raw('SUM(sale_items.quantity * COALESCE(p_hpp.avg_hpp, products.selling_price * 0.6)) as total_cogs'))
                ->value('total_cogs') ?? 0;
            $totalOverheadExpense = OverheadCost::sum('cost');
            $totalExpense = $totalCogs + $totalOverheadExpense;
            $netProfit = $totalRevenue - $totalExpense;

            $monthlySales = Sale::query()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
                ->selectRaw('SUM(total) as realization')
                ->groupBy('ym')
                ->orderBy('ym', 'asc')
                ->get();

            $monthlyGrowth = [];
            $prevRealization = 0;

            foreach ($monthlySales as $row) {
                $realization = (float) $row->realization;
                $target = $prevRealization > 0 ? $prevRealization * 1.1 : 5000000;
                $growth = $prevRealization > 0 ? (($realization - $prevRealization) / $prevRealization) * 100 : 0;

                try {
                    $monthLabel = Carbon::createFromFormat('Y-m', $row->ym)->translatedFormat('F Y');
                } catch (\Exception $e) {
                    $monthLabel = $row->ym;
                }

                $monthlyGrowth[] = [
                    'month' => $monthLabel,
                    'target' => $target,
                    'realization' => $realization,
                    'growth' => $growth,
                    'status' => $realization >= $target ? 'Exceeded' : ($realization >= $target * 0.8 ? 'Near Target' : 'Under Target'),
                ];

                $prevRealization = $realization;
            }

            $monthlyGrowth = array_slice(array_reverse($monthlyGrowth), 0, 6);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.export_pdf', [
                'totalRevenue' => $totalRevenue,
                'totalExpense' => $totalExpense,
                'netProfit' => $netProfit,
                'monthlyGrowth' => array_reverse($monthlyGrowth),
            ]);

            $pdf->setOption('dpi', 96);
            $pdf->setOption('enable_font_subsetting', true);

            return $pdf->download('laporan-finansial-'.now()->format('Y-m-d').'.pdf');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['pdf' => 'Gagal generate PDF: ' . $e->getMessage()]);
        }
    }

    public function exportGoogleSheets(\App\Services\SpreadsheetExportService $exportService)
    {
        try {
            $monthlySales = Sale::query()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
                ->selectRaw('SUM(total) as realization')
                ->groupBy('ym')
                ->orderBy('ym', 'asc')
                ->get();

            $monthlyGrowth = [];
            $prevRealization = 0;

            foreach ($monthlySales as $row) {
                $realization = (float) $row->realization;
                $target = $prevRealization > 0 ? $prevRealization * 1.1 : 5000000;
                $growth = $prevRealization > 0 ? (($realization - $prevRealization) / $prevRealization) * 100 : 0;

                try {
                    $monthLabel = Carbon::createFromFormat('Y-m', $row->ym)->translatedFormat('F Y');
                } catch (\Exception $e) {
                    $monthLabel = $row->ym;
                }

                $monthlyGrowth[] = [
                    'month' => $monthLabel,
                    'target' => $target,
                    'realization' => $realization,
                    'growth' => $growth,
                    'status' => $realization >= $target ? 'Exceeded' : ($realization >= $target * 0.8 ? 'Near Target' : 'Under Target'),
                ];

                $prevRealization = $realization;
            }

            $monthlyGrowth = array_slice(array_reverse($monthlyGrowth), 0, 6);
            $dataToExport = array_reverse($monthlyGrowth);

            $headers = ['Bulan', 'Target Penjualan (Rp)', 'Realisasi (Rp)', 'Pertumbuhan (%)', 'Status'];
            $data = [];

            foreach ($dataToExport as $row) {
                $data[] = [
                    $row['month'],
                    number_format($row['target'], 0, ',', ''),
                    number_format($row['realization'], 0, ',', ''),
                    number_format(round($row['growth'], 2), 2, ',', ''),
                    $row['status']
                ];
            }

            $options = [
                'statistics' => [
                    2 => 'Total Realisasi',
                    1 => 'Total Target'
                ],
                'chart' => [
                    'label_col' => 0, // Bulan
                    'data_col' => 2,  // Realisasi
                    'title' => 'Performa Penjualan Bulanan'
                ]
            ];

            return $exportService->exportAsXlsx('Laporan-Pertumbuhan-UMKM-Pancasila', $headers, $data, $options);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['export' => 'Gagal export spreadsheet: ' . $e->getMessage()]);
        }
    }
}
