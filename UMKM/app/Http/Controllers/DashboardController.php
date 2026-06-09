<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\AIService;

class DashboardController extends Controller
{
    public function index(Request $request, AIService $aiService)
    {
        if (!auth()->check()) {
            return view('welcome');
        }

        $targetDateString = $request->input('date', Carbon::today()->toDateString());
        $isTimeTravel = $request->filled('date');

        $filter = $request->query('range');
        if (!$filter) {
            $filter = '30';
        }
        if ($isTimeTravel) {
            $filter = '1';
        }

        $companyId = auth()->user()->company_id;

        if ($filter === '1') {
            $startDate = Carbon::parse($targetDateString)->startOfDay();
            $endDate = Carbon::parse($targetDateString)->endOfDay();
            $now = Carbon::parse($targetDateString);
            
            $prevStartDate = $startDate->copy()->subDay()->startOfDay();
            $prevEndDate = $startDate->copy()->subDay()->endOfDay();
        } else {
            $days = (int) $filter;
            $now = Carbon::now();
            $startDate = $now->copy()->subDays($days)->startOfDay();
            $endDate = $now->copy()->endOfDay();
            
            $prevStartDate = $now->copy()->subDays($days * 2)->startOfDay();
            $prevEndDate = $now->copy()->subDays($days)->endOfDay();
        }

        // 1. Sales Statistics (Strict Cash Basis: Cash Sales + Debt/Installment Payments)
        $cashSalesThisPeriod = Sale::where('company_id', $companyId)
            ->whereIn(DB::raw('LOWER(payment_method)'), ['cash', 'transfer', 'qris'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $debtPaymentsThisPeriod = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount_paid');

        $salesThisPeriod = $cashSalesThisPeriod + $debtPaymentsThisPeriod;

        $cashSalesPrevPeriod = Sale::where('company_id', $companyId)
            ->whereIn(DB::raw('LOWER(payment_method)'), ['cash', 'transfer', 'qris'])
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('total');

        $debtPaymentsPrevPeriod = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('amount_paid');

        $salesPrevPeriod = $cashSalesPrevPeriod + $debtPaymentsPrevPeriod;

        $salesGrowth = $salesPrevPeriod > 0 ? (($salesThisPeriod - $salesPrevPeriod) / $salesPrevPeriod) * 100 : ($salesThisPeriod > 0 ? 100 : 0);

        // 2. Expense Statistics (Strict Cash Basis: Petty Cash Expenses + Raw Material Purchases)
        $opExpensesThis = \App\Models\Expense::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        $purchasesThis = \App\Models\Purchase::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');
        $expensesThisPeriod = $opExpensesThis + $purchasesThis;

        $opExpensesPrev = \App\Models\Expense::where('company_id', $companyId)->whereBetween('created_at', [$prevStartDate, $prevEndDate])->sum('amount');
        $purchasesPrev = \App\Models\Purchase::where('company_id', $companyId)->whereBetween('created_at', [$prevStartDate, $prevEndDate])->sum('total_amount');
        $expensesPrevPeriod = $opExpensesPrev + $purchasesPrev;
        $expenseGrowth = $expensesPrevPeriod > 0 ? (($expensesThisPeriod - $expensesPrevPeriod) / $expensesPrevPeriod) * 100 : ($expensesThisPeriod > 0 ? 100 : 0);

        // 3. Production & Stock Alerts
        $prodThisPeriod = Production::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->sum('quantity');
        $lowStockMaterials = Material::where('company_id', $companyId)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->get();
        $lowStock = $lowStockMaterials->count();
        $totalMaterials = Material::where('company_id', $companyId)->count();
        $stockSafePercent = $totalMaterials > 0 ? round((($totalMaterials - $lowStock) / $totalMaterials) * 100) : 0;

        // 4. Recent Activities (Merged Sales, Production & Installment Payments)
        $latestSales = Sale::where('company_id', $companyId)->latest()->limit(5)->get()->map(function($sale) {
            $isDebt = $sale->payment_method === 'debt';
            return [
                'type' => 'sale',
                'title' => $isDebt ? "Penjualan Tempo #{$sale->id}" : "Penjualan #{$sale->id}",
                'amount' => 'Rp ' . number_format($sale->total, 0, ',', '.'),
                'time' => $sale->created_at,
                'icon' => $isDebt ? 'menu_book' : 'payments',
                'color' => $isDebt ? 'amber' : 'emerald'
            ];
        });

        $latestProds = Production::with('product')->where('company_id', $companyId)->latest()->limit(5)->get()->map(function($prod) {
            return [
                'type' => 'production',
                'title' => "Produksi " . ($prod->product->name ?? 'Produk'),
                'amount' => "{$prod->quantity} Unit",
                'time' => $prod->created_at,
                'icon' => 'precision_manufacturing',
                'color' => 'indigo'
            ];
        });

        $latestPayments = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($pay) {
                return [
                    'type' => 'payment',
                    'title' => "Cicilan " . ($pay->debt->customer->name ?? 'Pelanggan'),
                    'amount' => 'Rp ' . number_format($pay->amount_paid, 0, ',', '.'),
                    'time' => $pay->created_at,
                    'icon' => 'price_check',
                    'color' => 'emerald'
                ];
            });

        $recentActivities = $latestSales->concat($latestProds)->concat($latestPayments)->sortByDesc('time')->take(5);

        // 5. Chart Data (Dynamic Grouping under Strict Cash Basis)
        $chartLabels = [];
        $chartSales = [];
        $chartExpenses = [];
        $chartType = $filter == '1' ? 'bar' : 'line';

        if ($filter == '1') {
            // Hourly grouping for Today (Cash Inflow = Cash Sales + Installment Payments)
            $salesTrend = Sale::where('company_id', $companyId)
                ->whereIn(DB::raw('LOWER(payment_method)'), ['cash', 'transfer', 'qris'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('HOUR(created_at) as hour, SUM(total) as total')
                ->groupBy('hour')->get()->keyBy('hour');

            $paymentTrend = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('HOUR(created_at) as hour, SUM(amount_paid) as total')
                ->groupBy('hour')->get()->keyBy('hour');
                
            $expTrend = \App\Models\Expense::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('HOUR(created_at) as hour, SUM(amount) as total')
                ->groupBy('hour')->get()->keyBy('hour');

            $purTrend = \App\Models\Purchase::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('HOUR(created_at) as hour, SUM(total_amount) as total')
                ->groupBy('hour')->get()->keyBy('hour');

            for ($i = 0; $i < 24; $i++) {
                $chartLabels[] = sprintf('%02d:00', $i);
                $chartSales[] = (float) ($salesTrend[$i]->total ?? 0) + (float) ($paymentTrend[$i]->total ?? 0);
                $chartExpenses[] = (float) ($expTrend[$i]->total ?? 0) + (float) ($purTrend[$i]->total ?? 0);
            }
        } else {
            // Daily grouping for Weekly/Monthly (Cash Inflow = Cash Sales + Installment Payments)
            $salesTrend = Sale::where('company_id', $companyId)
                ->whereIn(DB::raw('LOWER(payment_method)'), ['cash', 'transfer', 'qris'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(total) as total')
                ->groupBy('date')->get()->keyBy('date');

            $paymentTrend = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(amount_paid) as total')
                ->groupBy('date')->get()->keyBy('date');

            $expTrend = \App\Models\Expense::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
                ->groupBy('date')->get()->keyBy('date');

            $purTrend = \App\Models\Purchase::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                ->groupBy('date')->get()->keyBy('date');

            for ($i = $days; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->toDateString();
                $chartLabels[] = Carbon::parse($date)->format('d/m');
                $chartSales[] = (float) ($salesTrend[$date]->total ?? 0) + (float) ($paymentTrend[$date]->total ?? 0);
                $chartExpenses[] = (float) ($expTrend[$date]->total ?? 0) + (float) ($purTrend[$date]->total ?? 0);
            }
        }

        // 6. Dynamic Sales-Driven HPP & Cost Distribution
        $saleItemsForCogs = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin(DB::raw("(SELECT product_id, 
                                       AVG(unit_hpp_snapshot) as avg_hpp,
                                       AVG(material_cost_snapshot / quantity) as avg_mat,
                                       AVG(labor_cost / quantity) as avg_lab,
                                       AVG(overhead_cost_snapshot / quantity) as avg_over
                                FROM productions 
                                WHERE status = 'done' AND company_id = " . intval($companyId) . " 
                                GROUP BY product_id) as p_hpp"), 'p_hpp.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.company_id', $companyId)
            ->where('sale_items.company_id', $companyId)
            ->select(
                'sale_items.quantity',
                'products.selling_price',
                'p_hpp.avg_hpp',
                'p_hpp.avg_mat',
                'p_hpp.avg_lab',
                'p_hpp.avg_over'
            )
            ->get();

        $totalCogs = 0;
        $totalMaterialCost = 0;
        $totalLaborCost = 0;
        $totalOverheadCost = 0;

        foreach ($saleItemsForCogs as $item) {
            $qty = (float) $item->quantity;
            $baseHpp = (float) ($item->avg_hpp ?: ($item->selling_price * 0.6));
            $itemHpp = $baseHpp * $qty;
            $totalCogs += $itemHpp;

            if ($item->avg_hpp > 0) {
                $totalComp = (float)$item->avg_mat + (float)$item->avg_lab + (float)$item->avg_over;
                if ($totalComp > 0) {
                    $matRatio = (float)$item->avg_mat / $totalComp;
                    $labRatio = (float)$item->avg_lab / $totalComp;
                    $overRatio = (float)$item->avg_over / $totalComp;
                } else {
                    $matRatio = 0.70;
                    $labRatio = 0.20;
                    $overRatio = 0.10;
                }
            } else {
                $matRatio = 0.70;
                $labRatio = 0.20;
                $overRatio = 0.10;
            }

            $totalMaterialCost += $itemHpp * $matRatio;
            $totalLaborCost += $itemHpp * $labRatio;
            $totalOverheadCost += $itemHpp * $overRatio;
        }

        $costDist = [
            'material' => (float) $totalMaterialCost,
            'labor' => (float) $totalLaborCost,
            'overhead' => (float) $totalOverheadCost,
        ];

        // 7. Top Selling Products
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.company_id', $companyId)
            ->where('sale_items.company_id', $companyId)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.quantity * sale_items.price) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return view('DashboardUtama', [
            'companyName' => auth()->user()->company->name ?? 'UMKM Anda',
            'totalSales' => $salesThisPeriod,
            'salesGrowth' => $salesGrowth,
            'totalExpenses' => $expensesThisPeriod,
            'expenseGrowth' => $expenseGrowth,
            'totalProduction' => $prodThisPeriod,
            'lowStock' => $lowStock,
            'lowStockMaterials' => $lowStockMaterials,
            'stockSafePercent' => $stockSafePercent,
            'chartLabels' => $chartLabels,
            'chartSales' => $chartSales,
            'chartExpenses' => $chartExpenses,
            'chartType' => $chartType,
            'costDist' => $costDist,
            'aiInsight' => $this->generateAiInsight($salesThisPeriod, $expensesThisPeriod, $lowStock, $salesGrowth),
            'currentFilter' => $filter,
            'recentActivities' => $recentActivities,
            'targetDate' => $now->toDateString(),
            'isTimeTravel' => $isTimeTravel,
            'topProducts' => $topProducts,
            'bcgAnalysis' => $aiService->getBcgMenuAnalysis($companyId),
        ]);
    }

    private function generateAiInsight($sales, $expenses, $lowStock, $growth)
    {
        $margin = $sales > 0 ? (($sales - $expenses) / $sales) * 100 : 0;
        
        $insights = [];

        // Critical Alerts (Prioritized)
        if ($lowStock > 0) {
            $insights[] = "Krisis stok terdeteksi! Ada {$lowStock} bahan baku di bawah batas minimum. Segera belanja agar produksi tidak macet.";
        }
        
        if ($expenses > $sales && $sales > 0) {
            $insights[] = "Waspada kebocoran biaya: Pengeluaran Anda melampaui pendapatan. Cek kembali biaya overhead atau efisiensi penggunaan bahan baku.";
        }

        // Performance Insights
        if ($growth > 20) {
            $insights[] = "Luar biasa! Penjualan melonjak " . round($growth, 1) . "%. Pertimbangkan untuk menambah kapasitas produksi atau stok cadangan.";
        } elseif ($growth > 5) {
            $insights[] = "Tren positif terlihat. Penjualan tumbuh stabil. Pertahankan ritme pemasaran Anda saat ini.";
        } elseif ($growth < -10) {
            $insights[] = "Penjualan menurun dibanding periode lalu. Mungkin ini saatnya mencoba promo baru atau mengevaluasi harga jual produk.";
        }

        // Margin Analysis
        if ($margin > 30) {
            $insights[] = "Efisiensi bisnis Anda sangat baik dengan margin laba di atas 30%. Anda memiliki arus kas yang sehat untuk investasi alat baru.";
        } elseif ($margin < 10 && $sales > 0) {
            $insights[] = "Margin laba Anda tipis (di bawah 10%). Coba negosiasi ulang harga bahan baku atau tingkatkan harga jual produk unggulan.";
        }

        // Fallback/Generic (if no strong insights)
        if (empty($insights)) {
            $templates = [
                "Performa operasional Anda cukup stabil. Fokus pada konsistensi kualitas produk untuk menjaga loyalitas pelanggan.",
                "Stok aman dan keuangan terkendali. Gunakan waktu luang ini untuk merencanakan strategi pemasaran bulan depan.",
                "Data menunjukkan alur bisnis yang sehat. Pastikan pencatatan biaya operasional tetap disiplin setiap hari."
            ];
            return $templates[array_rand($templates)];
        }

        // Pick 1 random from qualified insights
        return $insights[array_rand($insights)];
    }

    public function exportExcel(Request $request)
    {
        try {
            $targetDateString = $request->input('date', Carbon::today()->toDateString());
            $isTimeTravel = $request->filled('date');

            $filter = $request->query('range');
            if (!$filter) {
                $filter = '30';
            }
            if ($isTimeTravel) {
                $filter = '1';
            }

            $companyId = auth()->user()->company_id;

            if ($filter === '1') {
                $startDate = Carbon::parse($targetDateString)->startOfDay();
                $endDate = Carbon::parse($targetDateString)->endOfDay();
                $periodLabel = Carbon::parse($targetDateString)->translatedFormat('d F Y');
            } else {
                $days = (int) $filter;
                $now = Carbon::now();
                $startDate = $now->copy()->subDays($days)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = $startDate->translatedFormat('d F Y') . ' s/d ' . $endDate->translatedFormat('d F Y');
            }

            // 1. Sales Statistics (Strict Cash Basis: Cash Sales + Debt/Installment Payments)
            $cashSales = Sale::where('company_id', $companyId)
                ->whereIn(DB::raw('LOWER(payment_method)'), ['cash', 'transfer', 'qris'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total');

            $debtPayments = \App\Models\DebtPayment::whereHas('debt', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount_paid');

            // 2. Expense Statistics (Strict Cash Basis: Petty Cash Expenses + Raw Material Purchases)
            $opExpenses = \App\Models\Expense::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->sum('amount');
            $purchases = \App\Models\Purchase::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');

            // 3. Raw detailed mutasi lists for Sheet 2
            $salesList = Sale::where('company_id', $companyId)
                ->whereIn(DB::raw('LOWER(payment_method)'), ['cash', 'transfer', 'qris'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $paymentsList = \App\Models\DebtPayment::with(['debt.customer'])
                ->whereHas('debt', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $expensesList = \App\Models\Expense::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $purchasesList = \App\Models\Purchase::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            // Merge & chronological sort
            $mutasi = collect();

            foreach ($salesList as $sale) {
                $mutasi->push([
                    'time' => $sale->created_at,
                    'ref_id' => sprintf('TRX-%05d', $sale->id),
                    'type' => 'Uang Masuk',
                    'category' => 'Penjualan Tunai',
                    'description' => 'Penjualan produk POS (' . strtoupper($sale->payment_method) . ')',
                    'amount' => (float)$sale->total,
                ]);
            }

            foreach ($paymentsList as $pay) {
                $custName = $pay->debt->customer->name ?? 'Pelanggan';
                $mutasi->push([
                    'time' => $pay->created_at,
                    'ref_id' => sprintf('PAY-%05d', $pay->id),
                    'type' => 'Uang Masuk',
                    'category' => 'Cicilan Piutang',
                    'description' => 'Pembayaran cicilan piutang oleh ' . $custName,
                    'amount' => (float)$pay->amount_paid,
                ]);
            }

            foreach ($expensesList as $exp) {
                $mutasi->push([
                    'time' => $exp->created_at,
                    'ref_id' => sprintf('EXP-%05d', $exp->id),
                    'type' => 'Uang Keluar',
                    'category' => 'Operasional',
                    'description' => $exp->description ?: 'Pengeluaran operasional harian',
                    'amount' => (float)$exp->amount,
                ]);
            }

            foreach ($purchasesList as $pur) {
                $mutasi->push([
                    'time' => $pur->created_at,
                    'ref_id' => sprintf('PUR-%05d', $pur->id),
                    'type' => 'Uang Keluar',
                    'category' => 'Belanja Stok',
                    'description' => 'Pembelian bahan baku (' . strtoupper($pur->payment_method) . ')',
                    'amount' => (float)$pur->total_amount,
                ]);
            }

            $sortedMutasi = $mutasi->sortBy('time');

            // Construct PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            // --- SHEET 1: Ringkasan Arus Kas ---
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Ringkasan Arus Kas');
            $sheet1->setShowGridlines(true);

            // Title & Metadata
            $sheet1->setCellValue('A1', 'LAPORAN ARUS KAS DIGITAL (CASH-BASIS) - SAHAYU');
            $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF047857'));

            $sheet1->setCellValue('A2', 'UMKM: ' . (auth()->user()->company->name ?? 'SAHAYU UMKM'));
            $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

            $sheet1->setCellValue('A3', 'Periode Laporan: ' . $periodLabel);
            $sheet1->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

            $sheet1->setCellValue('A4', 'Dicetak Oleh: ' . auth()->user()->name . ' | Waktu: ' . now()->translatedFormat('d F Y, H:i'));
            $sheet1->getStyle('A4')->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF94A3B8'));

            // Table 1: ARUS KAS MASUK
            $sheet1->setCellValue('A6', 'I. ARUS KAS MASUK');
            $sheet1->getStyle('A6')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF047857'));

            $sheet1->setCellValue('A7', 'Sumber Penerimaan');
            $sheet1->setCellValue('B7', 'Nominal Tunai');
            $sheet1->getStyle('A7:B7')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet1->getStyle('A7:B7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF047857');
            $sheet1->getStyle('A7:B7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet1->setCellValue('A8', 'Pendapatan Penjualan Tunai POS');
            $sheet1->setCellValue('B8', (float)$cashSales);

            $sheet1->setCellValue('A9', 'Pembayaran Cicilan Piutang Masuk');
            $sheet1->setCellValue('B9', (float)$debtPayments);

            $sheet1->setCellValue('A10', 'Total Arus Kas Masuk');
            $sheet1->setCellValue('B10', '=SUM(B8:B9)');

            // Styling Table 1
            $sheet1->getStyle('B8:B10')->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet1->getStyle('A10:B10')->getFont()->setBold(true);
            $sheet1->getStyle('A10:B10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6F4EA');
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
            ];
            $sheet1->getStyle('A7:B10')->applyFromArray($borderStyle);

            // Table 2: ARUS KAS KELUAR
            $sheet1->setCellValue('A12', 'II. ARUS KAS KELUAR');
            $sheet1->getStyle('A12')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF047857'));

            $sheet1->setCellValue('A13', 'Jenis Pengeluaran');
            $sheet1->setCellValue('B13', 'Nominal Tunai');
            $sheet1->getStyle('A13:B13')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet1->getStyle('A13:B13')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF047857');
            $sheet1->getStyle('A13:B13')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet1->setCellValue('A14', 'Biaya Operasional / Petty Cash');
            $sheet1->setCellValue('B14', (float)$opExpenses);

            $sheet1->setCellValue('A15', 'Belanja Stok Bahan Baku / Purchases');
            $sheet1->setCellValue('B15', (float)$purchases);

            $sheet1->setCellValue('A16', 'Total Arus Kas Keluar');
            $sheet1->setCellValue('B16', '=SUM(B14:B15)');

            // Styling Table 2
            $sheet1->getStyle('B14:B16')->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet1->getStyle('A16:B16')->getFont()->setBold(true);
            $sheet1->getStyle('A16:B16')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFCE8E6');
            $sheet1->getStyle('A13:B16')->applyFromArray($borderStyle);

            // Table 3: NET SUMMARY
            $sheet1->setCellValue('A18', 'III. RINGKASAN NET KAS');
            $sheet1->getStyle('A18')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF047857'));

            $sheet1->setCellValue('A19', 'Sisa Arus Kas / Net Cash Flow');
            $sheet1->setCellValue('B19', '=B10-B16');

            $sheet1->getStyle('B19')->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet1->getStyle('A19:B19')->getFont()->setBold(true);
            $sheet1->getStyle('A19:B19')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F5E9');
            $sheet1->getStyle('A19:B19')->applyFromArray($borderStyle);

            // Auto-size columns
            $sheet1->getColumnDimension('A')->setAutoSize(true);
            $sheet1->getColumnDimension('B')->setAutoSize(true);

            // --- SHEET 2: Buku Kas Umum (Jurnal Mutasi) ---
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Buku Kas Umum');
            $sheet2->setShowGridlines(true);

            // Header info
            $sheet2->setCellValue('A1', 'BUKU KAS UMUM (CASH MUTATION LEDGER)');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF047857'));

            $sheet2->setCellValue('A2', 'UMKM: ' . (auth()->user()->company->name ?? 'SAHAYU UMKM'));
            $sheet2->setCellValue('A3', 'Periode Laporan: ' . $periodLabel);
            $sheet2->getStyle('A2:A3')->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

            // Table Headers
            $sheet2->setCellValue('A5', 'Tanggal & Waktu');
            $sheet2->setCellValue('B5', 'ID Referensi');
            $sheet2->setCellValue('C5', 'Tipe Mutasi');
            $sheet2->setCellValue('D5', 'Kategori');
            $sheet2->setCellValue('E5', 'Deskripsi/Keterangan Rinci');
            $sheet2->setCellValue('F5', 'Nominal Tunai');

            $sheet2->getStyle('A5:F5')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet2->getStyle('A5:F5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF047857');
            $sheet2->getStyle('A5:F5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Populate rows
            $rowNum = 6;
            foreach ($sortedMutasi as $item) {
                $sheet2->setCellValue('A' . $rowNum, $item['time']->format('Y-m-d H:i'));
                $sheet2->setCellValue('B' . $rowNum, $item['ref_id']);
                $sheet2->setCellValue('C' . $rowNum, $item['type']);
                $sheet2->setCellValue('D' . $rowNum, $item['category']);
                $sheet2->setCellValue('E' . $rowNum, $item['description']);
                $sheet2->setCellValue('F' . $rowNum, (float)$item['amount']);

                // Alternating type color helpers (light emerald for Masuk, light rose for Keluar)
                if ($item['type'] === 'Uang Masuk') {
                    $sheet2->getStyle('C' . $rowNum)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF047857'))->setBold(true);
                } else {
                    $sheet2->getStyle('C' . $rowNum)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFB91C1C'))->setBold(true);
                }

                $rowNum++;
            }

            $lastRow = $rowNum - 1;

            if ($lastRow >= 6) {
                // Add sum totals row at the bottom
                $sheet2->setCellValue('D' . $rowNum, 'TOTAL KAS MASUK');
                $sheet2->setCellValue('F' . $rowNum, '=SUMIF(C6:C' . $lastRow . ', "Uang Masuk", F6:F' . $lastRow . ')');
                $sheet2->getStyle('D' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
                $sheet2->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet2->getStyle('D' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6F4EA');
                $sheet2->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray($borderStyle);
                $rowNum++;

                $sheet2->setCellValue('D' . $rowNum, 'TOTAL KAS KELUAR');
                $sheet2->setCellValue('F' . $rowNum, '=SUMIF(C6:C' . $lastRow . ', "Uang Keluar", F6:F' . $lastRow . ')');
                $sheet2->getStyle('D' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
                $sheet2->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet2->getStyle('D' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFCE8E6');
                $sheet2->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray($borderStyle);
                $rowNum++;

                $sheet2->setCellValue('D' . $rowNum, 'SALDO KAS BERSIH');
                $sheet2->setCellValue('F' . $rowNum, '=F' . ($rowNum - 2) . '-F' . ($rowNum - 1));
                $sheet2->getStyle('D' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
                $sheet2->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet2->getStyle('D' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F5E9');
                $sheet2->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray($borderStyle);

                $sheet2->getStyle('F6:F' . $lastRow)->getNumberFormat()->setFormatCode('Rp #,##0');
                $sheet2->getStyle('F6:F' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet2->getStyle('A5:F' . $lastRow)->applyFromArray($borderStyle);
            } else {
                $sheet2->setCellValue('A6', 'Belum ada data mutasi kas pada periode terpilih.');
                $sheet2->mergeCells('A6:F6');
                $sheet2->getStyle('A6')->getFont()->setItalic(true);
                $sheet2->getStyle('A5:F6')->applyFromArray($borderStyle);
            }

            // Auto-size columns
            for ($col = 'A'; $col <= 'F'; $col++) {
                $sheet2->getColumnDimension($col)->setAutoSize(true);
            }

            // Set active sheet to Summary
            $spreadsheet->setActiveSheetIndex(0);

            // Output XLSX
            $safeFilename = 'Buku_Kas_Umum_' . now()->format('Y-m-d');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $safeFilename . '.xlsx"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard XLSX Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export Buku Kas: ' . $e->getMessage()]);
        }
    }

    public function globalSearch(Request $request)
    {
        $q = trim($request->query('q', ''));
        $companyId = auth()->user()->company_id;
        $isAdmin = auth()->user()->role === 'admin';

        // Static menus matching the sidebar navigation
        $menus = [
            [
                'title' => 'Dashboard Harian',
                'subtitle' => 'Ringkasan performa keuangan dan statistik harian',
                'url' => route('dashboard'),
                'icon' => 'dashboard',
                'admin_only' => false
            ],
            [
                'title' => 'Kasir POS',
                'subtitle' => 'Pencatatan penjualan cepat dan kasir digital',
                'url' => route('sales.index'),
                'icon' => 'point_of_sale',
                'admin_only' => false
            ],
            [
                'title' => 'Riwayat Transaksi',
                'subtitle' => 'Log transaksi penjualan dan cetak struk kembali',
                'url' => route('history.index'),
                'icon' => 'history',
                'admin_only' => false
            ],
            [
                'title' => 'Piutang / Kasbon',
                'subtitle' => 'Catatan utang pelanggan dan cicilan pembayaran',
                'url' => route('debts.index'),
                'icon' => 'book',
                'admin_only' => false
            ],
            [
                'title' => 'Catat Pengeluaran',
                'subtitle' => 'Pencatatan petty cash dan pengeluaran harian',
                'url' => route('expenses.index'),
                'icon' => 'receipt_long',
                'admin_only' => false
            ],
            [
                'title' => 'Data Pelanggan',
                'subtitle' => 'Daftar pelanggan setia dan riwayat piutang mereka',
                'url' => route('customers.index'),
                'icon' => 'group',
                'admin_only' => false
            ],
            [
                'title' => 'Produk Jadi',
                'subtitle' => 'Manajemen produk jadi, harga jual, dan stok barang',
                'url' => route('products.index'),
                'icon' => 'inventory',
                'admin_only' => false
            ],
            [
                'title' => 'HPP Otomatis',
                'subtitle' => 'Kalkulasi HPP otomatis produk dari resep bahan baku',
                'url' => route('hpp.index'),
                'icon' => 'calculate',
                'admin_only' => true
            ],
            [
                'title' => 'Bahan Baku',
                'subtitle' => 'Stok bahan baku, kategori, dan penyesuaian stok',
                'url' => route('materials.index'),
                'icon' => 'inventory_2',
                'admin_only' => false
            ],
            [
                'title' => 'Produksi',
                'subtitle' => 'Pencatatan produksi harian dan penggunaan bahan baku',
                'url' => route('productions.index'),
                'icon' => 'precision_manufacturing',
                'admin_only' => false
            ],
            [
                'title' => 'Biaya Operasional',
                'subtitle' => 'Manajemen biaya overhead (listrik, sewa, gaji dll)',
                'url' => route('overhead.index'),
                'icon' => 'account_balance_wallet',
                'admin_only' => false
            ],
            [
                'title' => 'Laporan Analisis',
                'subtitle' => 'Laporan laba rugi, HPP, dan analisis mendalam',
                'url' => route('reports.index'),
                'icon' => 'analytics',
                'admin_only' => false
            ],
            [
                'title' => 'SAHAYU AI Assistant',
                'subtitle' => 'Analisis data UMKM interaktif dengan asisten AI',
                'url' => route('ai.index'),
                'icon' => 'smart_toy',
                'admin_only' => false
            ],
            [
                'title' => 'Manajemen Akun',
                'subtitle' => 'Kelola akun staff kasir dan administrator sistem',
                'url' => route('accounts.index'),
                'icon' => 'manage_accounts',
                'admin_only' => true
            ],
        ];

        // Filter menus based on admin status
        $allowedMenus = array_values(array_filter($menus, function($m) use ($isAdmin) {
            return !$m['admin_only'] || $isAdmin;
        }));

        $results = [];

        if (strlen($q) < 2) {
            // Suggested / Recent state
            $menuItems = $allowedMenus;
            if (strlen($q) === 1) {
                $menuItems = array_values(array_filter($allowedMenus, function($m) use ($q) {
                    return stripos($m['title'], $q) !== false || stripos($m['subtitle'], $q) !== false;
                }));
            }
            if (!empty($menuItems)) {
                $results[] = [
                    'category' => 'Fitur & Navigasi',
                    'items' => array_slice($menuItems, 0, 8)
                ];
            }

            // Recent products
            $recentProducts = \App\Models\Product::where('company_id', $companyId)
                ->latest()
                ->limit(3)
                ->get()
                ->map(function($p) {
                    return [
                        'title' => $p->name,
                        'subtitle' => 'Stok: ' . $p->stock . ' | Harga: Rp ' . number_format($p->selling_price, 0, ',', '.'),
                        'url' => route('products.index') . '?search=' . urlencode($p->name),
                        'icon' => 'inventory',
                        'badge' => 'Terbaru'
                    ];
                })->toArray();
            if (!empty($recentProducts)) {
                $results[] = [
                    'category' => 'Produk Terbaru',
                    'items' => $recentProducts
                ];
            }

            // Recent Customers
            $recentCustomers = \App\Models\Customer::where('company_id', $companyId)
                ->latest()
                ->limit(3)
                ->get()
                ->map(function($c) {
                    return [
                        'title' => $c->name,
                        'subtitle' => 'No. HP: ' . ($c->phone ?: '-') . ' | Alamat: ' . ($c->address ?: '-'),
                        'url' => route('customers.index') . '?search=' . urlencode($c->name),
                        'icon' => 'group',
                        'badge' => 'Pelanggan'
                    ];
                })->toArray();
            if (!empty($recentCustomers)) {
                $results[] = [
                    'category' => 'Pelanggan Baru',
                    'items' => $recentCustomers
                ];
            }
        } else {
            // Active search state
            // 1. Menus
            $matchedMenus = array_values(array_filter($allowedMenus, function($m) use ($q) {
                return stripos($m['title'], $q) !== false || stripos($m['subtitle'], $q) !== false;
            }));
            if (!empty($matchedMenus)) {
                $results[] = [
                    'category' => 'Fitur & Navigasi',
                    'items' => array_map(function($m) {
                        $m['badge'] = 'Fitur';
                        return $m;
                    }, $matchedMenus)
                ];
            }

            // 2. Products
            $matchedProducts = \App\Models\Product::where('company_id', $companyId)
                ->where('name', 'like', "%{$q}%")
                ->limit(5)
                ->get()
                ->map(function($p) {
                    return [
                        'title' => $p->name,
                        'subtitle' => 'Stok: ' . $p->stock . ' | Harga: Rp ' . number_format($p->selling_price, 0, ',', '.'),
                        'url' => route('products.index') . '?search=' . urlencode($p->name),
                        'icon' => 'inventory',
                        'badge' => 'Produk'
                    ];
                })->toArray();
            if (!empty($matchedProducts)) {
                $results[] = [
                    'category' => 'Produk Jadi',
                    'items' => $matchedProducts
                ];
            }

            // 3. Customers
            $matchedCustomers = \App\Models\Customer::where('company_id', $companyId)
                ->where(function($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('address', 'like', "%{$q}%");
                })
                ->limit(5)
                ->get()
                ->map(function($c) {
                    return [
                        'title' => $c->name,
                        'subtitle' => 'No. HP: ' . ($c->phone ?: '-') . ' | Alamat: ' . ($c->address ?: '-'),
                        'url' => route('customers.index') . '?search=' . urlencode($c->name),
                        'icon' => 'group',
                        'badge' => 'Pelanggan'
                    ];
                })->toArray();
            if (!empty($matchedCustomers)) {
                $results[] = [
                    'category' => 'Pelanggan',
                    'items' => $matchedCustomers
                ];
            }

            // 4. Sales / Invoices
            $matchedSales = \App\Models\Sale::where('company_id', $companyId)
                ->where(function($query) use ($q) {
                    $query->where('id', 'like', "%{$q}%")
                        ->orWhere('customer', 'like', "%{$q}%")
                        ->orWhere('payment_method', 'like', "%{$q}%");
                })
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($s) {
                    $method = strtoupper($s->payment_method);
                    $customer = $s->customer ?: 'Umum';
                    return [
                        'title' => "Penjualan #TRX-" . str_pad($s->id, 5, '0', STR_PAD_LEFT),
                        'subtitle' => "Pelanggan: {$customer} | Total: Rp " . number_format($s->total, 0, ',', '.') . " | Metode: {$method}",
                        'url' => route('history.index') . '?search=' . $s->id,
                        'icon' => $s->payment_method === 'debt' ? 'menu_book' : 'payments',
                        'badge' => 'Transaksi'
                    ];
                })->toArray();
            if (!empty($matchedSales)) {
                $results[] = [
                    'category' => 'Riwayat Transaksi',
                    'items' => $matchedSales
                ];
            }
        }

        return response()->json($results);
    }
}

