<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiReportController extends Controller
{
    /**
     * Tampilkan halaman SAHAYU AI Assistant.
     */
    public function index(Request $request)
    {
        $targetMonth = $this->resolveTargetMonth($request->input('filter_month'));
        $monthlyData = $this->gatherMonthlyData($targetMonth);
        $historicalData = $this->gatherHistoricalData($targetMonth);

        return view('AiReport', [
            'monthlyData' => $monthlyData,
            'historicalData' => $historicalData,
            'filterMonth' => $targetMonth->format('Y-m'),
        ]);
    }

    /**
     * Kirim data bulanan + historis ke Groq AI untuk analisis lanjutan.
     */
    public function analyze(Request $request)
    {
        try {
            $targetMonth = $this->resolveTargetMonth($request->input('filter_month'));
            $monthlyData = $this->gatherMonthlyData($targetMonth);
            $historicalData = $this->gatherHistoricalData($targetMonth);
            $calculatedInsights = $this->calculateInsights($targetMonth, $monthlyData, $historicalData);

            // Build the user message with current + historical data + hard facts
            $userMessage = $this->buildUserMessage($monthlyData, $historicalData, $calculatedInsights);

            // System prompt v2: Anomaly Detection + Forecasting
            $systemPrompt = <<<PROMPT
Kamu adalah "SAHAYU Assistant", asisten AI untuk sistem manajemen UMKM bernama SAHAYU (Sistem Analisis HPP dan Arus Yield UMKM). Kamu memiliki DUA peran utama: AUDITOR dan FORECASTER.

Kamu akan menerima data bulan yang dipilih dan data historis 3 bulan sebelumnya. Analisis kedua set data tersebut.

═══ PERAN 1: AUDITOR (Deteksi Anomali) ═══
Evaluasi data BULAN YANG DIPILIH untuk menemukan anomali yang mencurigakan:
- Profit margin di atas 60% → ANOMALI (terlalu tinggi, tidak realistis untuk UMKM manufaktur)
- Profit margin negatif (< 0%) → ANOMALI (rugi, mungkin ada kesalahan input)
- Reject rate = 0% padahal ada produksi → ANOMALI (tidak realistis, semua manufaktur pasti ada waste)
- Reject rate > 20% → ANOMALI (waste sangat tinggi, perlu investigasi)
- Revenue = 0 tapi ada HPP → ANOMALI (produksi tanpa penjualan)
- HPP = 0 tapi ada revenue → ANOMALI (penjualan tanpa biaya produksi)
- Lonjakan/penurunan > 50% dibanding rata-rata 3 bulan sebelumnya → ANOMALI
Jika TIDAK ada anomali, set is_anomaly = false dan anomaly_reason = null.

═══ PERAN 2: FORECASTER (Prediksi) ═══
Analisis TREN dari 3 bulan historis + bulan yang dipilih untuk memprediksi bulan berikutnya:
- Prediksi Revenue bulan berikutnya (naik/turun berapa persen, perkiraan angka)
- Prediksi HPP bulan berikutnya
- Prediksi Reject Rate bulan berikutnya
- Jelaskan alasan prediksi berdasarkan tren yang terlihat

═══ KLASIFIKASI KESEHATAN ═══
- "Sehat" → profit margin ≥ 20% DAN reject rate ≤ 5%
- "Waspada" → profit margin 10%-19% ATAU reject rate 5%-10%
- "Kritis" → profit margin < 10% ATAU reject rate > 10%
- "Data Tidak Cukup" → jika data tidak memadai

═══ FORMAT OUTPUT ═══
Kamu WAJIB merespons HANYA dengan objek JSON berikut (TANPA markdown, TANPA backtick, TANPA teks di luar JSON):
{
  "is_anomaly": true atau false,
  "anomaly_reason": "Penjelasan anomali yang ditemukan" atau null jika tidak ada anomali,
  "health_status": "Sehat" atau "Waspada" atau "Kritis" atau "Data Tidak Cukup",
  "summary": "Ringkasan performa operasional bulan yang dipilih dalam 3-4 kalimat dengan angka spesifik.",
  "prediction": "Prediksi detail untuk bulan berikutnya: perkiraan revenue, HPP, dan reject rate beserta alasan tren. Minimal 3 kalimat.",
  "advice": "Satu saran actionable dan spesifik untuk meningkatkan performa bisnis bulan depan."
}

PENTING:
- Selalu gunakan Bahasa Indonesia.
- Jangan tambahkan teks apapun di luar JSON.
- Pastikan JSON valid dan bisa di-parse oleh json_decode.
- Format angka rupiah: gunakan titik sebagai pemisah ribuan (contoh: Rp 15.000.000).
PROMPT;

            // Call Groq API directly via Guzzle (bypasses SSL issues on Windows)
            $httpClient = new \GuzzleHttp\Client([
                'verify' => false,
                'timeout' => 60,
            ]);

            $response = $httpClient->post('https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 1500,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $content = $body['choices'][0]['message']['content'] ?? '';

            // Parse JSON response from AI
            $parsed = $this->parseAiResponse($content);

            return response()->json([
                'success' => true,
                'data' => $parsed,
                'calculated_insights' => $calculatedInsights,
                'raw_data' => $monthlyData,
                'historical_data' => $historicalData,
            ]);

        } catch (\Exception $e) {
            Log::error('SAHAYU AI Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal menghubungi AI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Chatbot interaktif: jawab pertanyaan follow-up user berdasarkan konteks LENGKAP bulan yang difilter.
     * Data context mencakup: finansial, operasional, detail per-produk, overhead, dan inventori bahan baku.
     */
    public function askChatbot(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'filter_month' => 'nullable|string',
        ]);

        try {
            $targetMonth = $this->resolveTargetMonth($request->input('filter_month'));
            $monthlyData = $this->gatherMonthlyData($targetMonth);

            // ═══ Build comprehensive monthly report ═══
            $monthlyReport = $this->buildComprehensiveReport($targetMonth, $monthlyData);
            
            // Data Enrichment: Net Profit Calculation
            // Gross Profit = Revenue - Raw Material Cost
            // Net Profit = Gross Profit - Total Expenses (Overhead + Labor)
            $revenue = $monthlyReport['financial_totals']['total_revenue'] ?? 0;
            $materialCost = $monthlyReport['expense_breakdown']['raw_material_cost'] ?? 0;
            $overheadCost = $monthlyReport['expense_breakdown']['overhead_cost'] ?? 0;
            $laborCost = $monthlyReport['expense_breakdown']['labor_cost'] ?? 0;
            
            $totalExpenses = $overheadCost + $laborCost;
            $grossProfit = $revenue - $materialCost;
            $netProfit = $grossProfit - $totalExpenses;
            
            $monthlyReport['financial_totals']['gross_profit'] = $grossProfit;
            $monthlyReport['financial_totals']['total_expenses'] = $totalExpenses;
            $monthlyReport['financial_totals']['net_profit'] = $netProfit;

            $reportJson = json_encode($monthlyReport, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $periodLabel = $monthlyData['period'];

            $systemPrompt = <<<PROMPT
Kamu adalah "SAHAYU Assistant", seorang Senior Financial & Operational Advisor untuk UMKM.
Kamu membantu user menganalisis data bisnis untuk periode: {$periodLabel}.

Berikut adalah LAPORAN KOMPREHENSIF dalam format JSON. Gunakan data ini sebagai SATU-SATUNYA sumber kebenaran untuk menjawab pertanyaan user:

{$reportJson}

═══ ATURAN WAJIB ═══
1. NADA PROFESIONAL: Bertindak sebagai advisor senior. Gunakan bahasa yang sangat profesional, analitis, dan solutif.
2. TANPA KATA PENGISI: Hilangkan sama sekali kata-kata kasual atau filler words seperti "oy", "hmm", "nah", "baiklah", "wah", dll.
3. FORMAT MARKDOWN: Selalu gunakan format Markdown (bullet points, **teks tebal**, heading kecil) agar jawaban mudah dibaca dan terstruktur.
4. PENANGANAN DATA KOSONG: Jika data spesifik yang ditanyakan TIDAK ADA dalam JSON, JANGAN hanya menjawab "Data tidak tersedia". Sebagai gantinya, berikan **saran atau praktik terbaik industri (best practice)** UMKM manufaktur yang relevan dengan pertanyaan tersebut.
5. Gunakan Bahasa Indonesia.
6. Format angka: gunakan "Rp" diikuti titik sebagai pemisah ribuan (contoh: Rp 15.000.000).
7. Format persentase: gunakan 1-2 desimal (contoh: 4,5%).
8. Jika bertanya perbandingan produk, rujuk ke "product_details".
PROMPT;

            $httpClient = new \GuzzleHttp\Client([
                'verify' => false,
                'timeout' => 45,
            ]);

            $response = $httpClient->post('https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $request->input('message')],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 800,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $reply = $body['choices'][0]['message']['content'] ?? 'Maaf, saya tidak dapat memproses pertanyaan Anda saat ini.';

            return response()->json(['reply' => trim($reply)]);

        } catch (\Exception $e) {
            Log::error('SAHAYU Chatbot Error: ' . $e->getMessage());

            return response()->json([
                'reply' => 'Maaf, terjadi kesalahan saat memproses pertanyaan Anda. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Bangun laporan komprehensif bulanan untuk konteks chatbot.
     * Mencakup finansial, operasional, detail per-produk, overhead, dan inventori.
     */
    private function buildComprehensiveReport(Carbon $targetMonth, array $monthlyData): array
    {
        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();
        $isCurrentMonth = $startOfMonth->equalTo(Carbon::now()->startOfMonth());
        $effectiveEnd = $isCurrentMonth ? Carbon::now()->endOfDay() : $endOfMonth->endOfDay();

        $startDate = $startOfMonth->toDateString();
        $endDate = $isCurrentMonth ? Carbon::now()->toDateString() : $endOfMonth->toDateString();

        // ─── 1. Financial Totals (from existing gatherMonthlyData) ───
        $financialTotals = [
            'total_revenue' => $monthlyData['total_revenue'],
            'total_hpp' => $monthlyData['total_hpp'],
            'gross_profit' => $monthlyData['gross_profit'],
            'profit_margin_pct' => $monthlyData['profit_margin'],
        ];

        // ─── 2. Operational Totals ───
        $operationalTotals = [
            'total_batches_completed' => $monthlyData['done_batches'],
            'total_units_produced' => $monthlyData['total_produced_units'],
            'total_good_units' => $monthlyData['total_good_units'],
            'total_reject_units' => $monthlyData['total_reject_units'],
            'overall_reject_rate_pct' => $monthlyData['reject_rate'],
        ];

        // ─── 3. Expense Breakdown ───
        $expenseBreakdown = [
            'raw_material_cost' => $monthlyData['material_cost'],
            'overhead_cost' => $monthlyData['overhead_cost'],
            'labor_cost' => $monthlyData['labor_cost'],
        ];

        // ─── 4. Overhead Details (per category) ───
        $overheadDetails = OverheadCost::whereBetween('transaction_date', [$startDate, $endDate])
            ->select('category', DB::raw('SUM(cost) as total_cost'), DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category ?? 'Umum',
                'total_cost' => (float) $row->total_cost,
                'transaction_count' => (int) $row->transaction_count,
            ])
            ->toArray();

        // ─── 5. Product Details (per-product production + sales breakdown) ───
        $productDetails = [];

        // 5a. Production data per product
        $productionByProduct = Production::where('status', 'done')
            ->whereBetween('production_date', [$startDate, $endDate])
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_produced'),
                DB::raw('SUM(CASE WHEN good_quantity > 0 THEN good_quantity ELSE quantity - reject_quantity END) as total_good'),
                DB::raw('SUM(reject_quantity) as total_rejected'),
                DB::raw('SUM(material_cost_snapshot) as material_cost'),
                DB::raw('SUM(overhead_cost_snapshot) as overhead_cost'),
                DB::raw('SUM(labor_cost) as labor_cost'),
                DB::raw('SUM(total_cost_snapshot) as total_hpp'),
                DB::raw('COUNT(*) as batch_count')
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // 5b. Sales data per product
        $salesByProduct = SaleItem::whereHas('sale', function ($q) use ($startOfMonth, $effectiveEnd) {
                $q->whereBetween('created_at', [$startOfMonth->startOfDay(), $effectiveEnd]);
            })
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('SUM(price * quantity) as total_sales_revenue')
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // 5c. Merge all products with their data
        $activeProductIds = $productionByProduct->keys()->merge($salesByProduct->keys())->unique();
        $products = Product::whereIn('id', $activeProductIds)->get()->keyBy('id');

        foreach ($activeProductIds as $pid) {
            $product = $products->get($pid);
            if (!$product) continue;

            $prod = $productionByProduct->get($pid);
            $sale = $salesByProduct->get($pid);

            $unitsSold = $sale ? (int) $sale->units_sold : 0;
            $salesRevenue = $sale ? (float) $sale->total_sales_revenue : 0;
            $unitsProduced = $prod ? (int) $prod->total_produced : 0;
            $unitsRejected = $prod ? (int) $prod->total_rejected : 0;
            $unitsGood = $prod ? (int) $prod->total_good : 0;
            $productHpp = $prod ? (float) $prod->total_hpp : 0;
            $rejectRate = $unitsProduced > 0 ? round(($unitsRejected / $unitsProduced) * 100, 2) : 0;
            $unitHpp = $unitsGood > 0 ? round($productHpp / $unitsGood, 2) : 0;

            $productDetails[] = [
                'product_name' => $product->name,
                'selling_price_per_unit' => (float) $product->selling_price,
                'units_sold' => $unitsSold,
                'sales_revenue' => $salesRevenue,
                'units_produced' => $unitsProduced,
                'good_units' => $unitsGood,
                'rejected_units' => $unitsRejected,
                'reject_rate_pct' => $rejectRate,
                'total_hpp' => $productHpp,
                'hpp_per_unit' => $unitHpp,
                'batch_count' => $prod ? (int) $prod->batch_count : 0,
            ];
        }

        // Sort by sales revenue descending
        usort($productDetails, fn ($a, $b) => $b['sales_revenue'] <=> $a['sales_revenue']);

        // ─── 6. Top Selling Products (ranked) ───
        $topSelling = array_slice(
            array_map(fn ($p) => [
                'rank' => 0,
                'product_name' => $p['product_name'],
                'units_sold' => $p['units_sold'],
                'revenue' => $p['sales_revenue'],
            ], $productDetails),
            0, 5
        );
        foreach ($topSelling as $idx => &$item) {
            $item['rank'] = $idx + 1;
        }
        unset($item);

        // ─── 7. Material Inventory Snapshot ───
        $materialInventory = Material::where('stock', '>', 0)
            ->orWhere('minimum_stock', '>', 0)
            ->get()
            ->map(fn ($mat) => [
                'name' => $mat->name,
                'category' => $mat->category ?? 'Umum',
                'current_stock' => (float) $mat->stock,
                'unit' => $mat->unit,
                'minimum_stock' => (float) $mat->minimum_stock,
                'is_low_stock' => $mat->stock <= $mat->minimum_stock,
                'price_per_unit' => (float) $mat->price,
            ])
            ->toArray();

        // ─── 8. Sales Transaction Summary ───
        $salesSummary = Sale::whereBetween('created_at', [$startOfMonth->startOfDay(), $effectiveEnd])
            ->select(
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(total) as avg_transaction_value'),
                DB::raw('MAX(total) as largest_transaction'),
                DB::raw('MIN(total) as smallest_transaction')
            )
            ->first();

        $transactionSummary = [
            'total_transactions' => (int) ($salesSummary->total_transactions ?? 0),
            'total_revenue' => (float) ($salesSummary->total_revenue ?? 0),
            'avg_transaction_value' => round((float) ($salesSummary->avg_transaction_value ?? 0), 2),
            'largest_transaction' => (float) ($salesSummary->largest_transaction ?? 0),
            'smallest_transaction' => (float) ($salesSummary->smallest_transaction ?? 0),
        ];

        // ═══ Assemble the full report ═══
        return [
            'report_period' => $monthlyData['period'],
            'generated_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'financial_totals' => $financialTotals,
            'operational_totals' => $operationalTotals,
            'expense_breakdown' => $expenseBreakdown,
            'overhead_details' => $overheadDetails,
            'product_details' => $productDetails,
            'top_selling_products' => $topSelling,
            'transaction_summary' => $transactionSummary,
            'material_inventory' => $materialInventory,
        ];
    }

    /**
     * Resolve target month dari input filter_month (format: "2026-05") atau default ke bulan ini.
     */
    private function resolveTargetMonth(?string $filterMonth): Carbon
    {
        if ($filterMonth) {
            try {
                return Carbon::createFromFormat('Y-m', $filterMonth)->startOfMonth();
            } catch (\Throwable $e) {
                // Jika format tidak valid, fallback ke bulan ini
            }
        }

        return Carbon::now()->startOfMonth();
    }

    /**
     * Kumpulkan data operasional untuk bulan tertentu dari database.
     */
    private function gatherMonthlyData(Carbon $targetMonth): array
    {
        $startOfMonth = $targetMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetMonth->copy()->endOfMonth()->toDateString();

        // Jika bulan target = bulan ini, gunakan hari ini sebagai batas akhir
        $isCurrentMonth = $targetMonth->copy()->startOfMonth()->equalTo(Carbon::now()->startOfMonth());
        if ($isCurrentMonth) {
            $endOfMonth = Carbon::now()->endOfDay()->toDateString();
        }

        // 1. Total Revenue
        $totalRevenue = (float) Sale::whereBetween('created_at', [
            $targetMonth->copy()->startOfMonth()->startOfDay(),
            $isCurrentMonth ? Carbon::now()->endOfDay() : $targetMonth->copy()->endOfMonth()->endOfDay(),
        ])->sum('total');

        // 2. Total HPP dari produksi selesai
        $doneProductions = Production::where('status', 'done')
            ->whereBetween('production_date', [$startOfMonth, $endOfMonth]);

        $materialCost = (float) (clone $doneProductions)->sum('material_cost_snapshot');
        $overheadCost = (float) (clone $doneProductions)->sum('overhead_cost_snapshot');
        $laborCost = (float) (clone $doneProductions)->sum('labor_cost');

        // Fallback: jika snapshot kosong, hitung dari tabel overhead
        if ($overheadCost <= 0) {
            $overheadCost = (float) OverheadCost::whereBetween('transaction_date', [$startOfMonth, $endOfMonth])->sum('cost');
        }
        if ($laborCost <= 0) {
            $laborCost = $overheadCost * 0.2;
        }

        $totalHpp = $materialCost + $overheadCost + $laborCost;

        // 3. Waste/Reject data
        $totalProduced = (int) (clone $doneProductions)->sum('quantity');
        $totalReject = (int) (clone $doneProductions)->sum('reject_quantity');
        $totalGood = (int) (clone $doneProductions)->sum(DB::raw('CASE WHEN good_quantity > 0 THEN good_quantity ELSE quantity - reject_quantity END'));

        $rejectRate = $totalProduced > 0 ? round(($totalReject / $totalProduced) * 100, 2) : 0;

        // 4. Profit calculation
        $grossProfit = $totalRevenue - $totalHpp;
        $profitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        // 5. Batch count
        $doneBatches = (clone $doneProductions)->count();

        return [
            'period' => $targetMonth->translatedFormat('F Y'),
            'total_revenue' => $totalRevenue,
            'total_hpp' => $totalHpp,
            'material_cost' => $materialCost,
            'overhead_cost' => $overheadCost,
            'labor_cost' => $laborCost,
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
            'total_produced_units' => $totalProduced,
            'total_reject_units' => $totalReject,
            'total_good_units' => $totalGood,
            'reject_rate' => $rejectRate,
            'done_batches' => $doneBatches,
        ];
    }

    /**
     * Kumpulkan data historis 3 bulan SEBELUM bulan target dari database.
     * Jika data DB kosong, gunakan dummy realistis agar AI tetap bisa membuat prediksi.
     */
    private function gatherHistoricalData(Carbon $targetMonth): array
    {
        $history = [];

        for ($i = 3; $i >= 1; $i--) {
            $histMonth = $targetMonth->copy()->subMonths($i);
            $monthStart = $histMonth->copy()->startOfMonth()->toDateString();
            $monthEnd = $histMonth->copy()->endOfMonth()->toDateString();
            $periodLabel = $histMonth->translatedFormat('F Y');

            // Revenue
            $revenue = (float) Sale::whereBetween('created_at', [
                $histMonth->copy()->startOfMonth()->startOfDay(),
                $histMonth->copy()->endOfMonth()->endOfDay(),
            ])->sum('total');

            // Production
            $prods = Production::where('status', 'done')
                ->whereBetween('production_date', [$monthStart, $monthEnd]);

            $matCost = (float) (clone $prods)->sum('material_cost_snapshot');
            $ohCost = (float) (clone $prods)->sum('overhead_cost_snapshot');
            $lbCost = (float) (clone $prods)->sum('labor_cost');

            if ($ohCost <= 0) {
                $ohCost = (float) OverheadCost::whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('cost');
            }
            if ($lbCost <= 0) {
                $lbCost = $ohCost * 0.2;
            }

            $hpp = $matCost + $ohCost + $lbCost;
            $produced = (int) (clone $prods)->sum('quantity');
            $rejected = (int) (clone $prods)->sum('reject_quantity');
            $rejectRate = $produced > 0 ? round(($rejected / $produced) * 100, 2) : 0;
            $margin = $revenue > 0 ? round((($revenue - $hpp) / $revenue) * 100, 2) : 0;

            $history[] = [
                'period' => $periodLabel,
                'revenue' => $revenue,
                'hpp' => $hpp,
                'profit_margin' => $margin,
                'produced_units' => $produced,
                'reject_units' => $rejected,
                'reject_rate' => $rejectRate,
            ];
        }

        return $history;
    }

    /**
     * Hitung insight matematis (BEP, Reject Tertinggi, Prediksi)
     */
    private function calculateInsights(Carbon $targetMonth, array $monthlyData, array $historicalData): array
    {
        $daysInMonth = $targetMonth->copy()->daysInMonth;
        
        // 1. BEP Calculation (HPP sudah mencakup Overhead dan Labor di controller ini)
        $totalHpp = $monthlyData['total_hpp'] ?? 0;
        $dailyBep = $daysInMonth > 0 ? $totalHpp / $daysInMonth : 0;

        // 2. Highest Reject Product
        $start = $targetMonth->copy()->startOfMonth()->toDateString();
        $end = $targetMonth->copy()->endOfMonth()->toDateString();
        
        $companyId = auth()->user()->company_id;
        $highestRejectProduct = DB::table('productions')
            ->join('products', 'products.id', '=', 'productions.product_id')
            ->where('productions.status', 'done')
            ->where('productions.company_id', $companyId)
            ->whereBetween('productions.production_date', [$start, $end])
            ->select(
                'products.name',
                DB::raw('SUM(productions.quantity) as total_produced'),
                DB::raw('SUM(productions.reject_quantity) as total_reject')
            )
            ->groupBy('products.id', 'products.name')
            ->havingRaw('SUM(productions.quantity) > 0')
            ->orderByRaw('(SUM(productions.reject_quantity) / SUM(productions.quantity)) DESC')
            ->first();

        $rejectInsight = null;
        if ($highestRejectProduct) {
            $totalProduced = (int) $highestRejectProduct->total_produced;
            $totalReject = (int) $highestRejectProduct->total_reject;
            $rejectRate = $totalProduced > 0 ? ($totalReject / $totalProduced) * 100 : 0;
            
            if ($rejectRate > 5) {
                $lostValue = DB::table('productions')
                    ->join('products', 'products.id', '=', 'productions.product_id')
                    ->where('productions.status', 'done')
                    ->where('productions.company_id', $companyId)
                    ->whereBetween('productions.production_date', [$start, $end])
                    ->where('products.name', $highestRejectProduct->name)
                    ->selectRaw('SUM((material_cost_snapshot / quantity) * reject_quantity) as lost_value')
                    ->value('lost_value');

                $targetRejectQty = $totalProduced * 0.03; // Target 3%
                $excessReject = $totalReject - $targetRejectQty;
                $savings = $excessReject > 0 && $totalReject > 0 ? ((float)$lostValue / $totalReject) * $excessReject : 0;

                $rejectInsight = [
                    'product_name' => $highestRejectProduct->name,
                    'reject_rate' => round($rejectRate, 2),
                    'lost_value' => round((float)$lostValue, 0),
                    'potential_savings' => round((float)$savings, 0),
                ];
            }
        }

        // 3. Prediction Logic
        $prevMonthPeriod = $targetMonth->copy()->subMonth()->translatedFormat('F Y');
        $prevMonthData = collect($historicalData)->firstWhere('period', $prevMonthPeriod);
        $prevRevenue = $prevMonthData['revenue'] ?? 0;
        $currentRevenue = $monthlyData['total_revenue'] ?? 0;

        $growthRate = 0;
        if ($prevRevenue > 0) {
            $growthRate = (($currentRevenue - $prevRevenue) / $prevRevenue) * 100;
        }

        $predictedNextMonthRevenue = $currentRevenue * (1 + ($growthRate / 100));

        $revs = array_map(fn($h) => $h['revenue'], $historicalData);
        $revs[] = $currentRevenue;
        $mean = count($revs) > 0 ? array_sum($revs) / count($revs) : 0;
        $variance = 0;
        foreach ($revs as $r) {
            $variance += pow($r - $mean, 2);
        }
        $stdDev = $mean > 0 && count($revs) > 0 ? sqrt($variance / count($revs)) : 0;
        $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

        $confidence = $cv < 15 ? 'Tinggi' : ($cv < 30 ? 'Sedang' : 'Rendah');

        return [
            'daily_bep' => round($dailyBep, 0),
            'reject_insight' => $rejectInsight,
            'prediction' => [
                'growth_rate' => round($growthRate, 2),
                'predicted_revenue' => round($predictedNextMonthRevenue, 0),
                'confidence' => $confidence,
            ],
            'profit_margin' => $monthlyData['profit_margin'],
        ];
    }

    /**
     * Bangun pesan user berisi data bulan target + historis + insight matematis.
     */
    private function buildUserMessage(array $current, array $history, array $insights): string
    {
        $historyText = '';
        foreach ($history as $idx => $h) {
            $num = $idx + 1;
            $historyText .= <<<HIST

📅 BULAN {$num}: {$h['period']}
  - Revenue: Rp {$this->formatNumber($h['revenue'])}
  - HPP: Rp {$this->formatNumber($h['hpp'])}
  - Margin Laba: {$h['profit_margin']}%
  - Unit Diproduksi: {$this->formatNumber($h['produced_units'])}
  - Unit Reject: {$this->formatNumber($h['reject_units'])}
  - Reject Rate: {$h['reject_rate']}%
HIST;
        }

    // Format hard facts for AI
        $bepFormatted = $this->formatNumber($insights['daily_bep']);
        $predRev = $this->formatNumber($insights['prediction']['predicted_revenue']);
        $predGrowth = $insights['prediction']['growth_rate'];
        
        $rejectFact = "Tidak ada produk dengan reject rate berbahaya (>5%).";
        if ($insights['reject_insight']) {
            $rInfo = $insights['reject_insight'];
            $rLoss = $this->formatNumber($rInfo['lost_value']);
            $rSave = $this->formatNumber($rInfo['potential_savings']);
            $rejectFact = "Produk '{$rInfo['product_name']}' memiliki reject rate tertinggi ({$rInfo['reject_rate']}%). Kerugian: Rp {$rLoss}. Potensi penghematan jika reject ditekan ke 3%: Rp {$rSave}.";
        }

        return <<<MSG
═══════════════════════════════════════
DATA HISTORIS 3 BULAN SEBELUMNYA (untuk analisis tren & prediksi):
═══════════════════════════════════════
{$historyText}

═══════════════════════════════════════
DATA BULAN YANG DIANALISIS: {$current['period']} (untuk audit anomali & klasifikasi):
═══════════════════════════════════════

📊 RINGKASAN FINANSIAL:
- Total Pendapatan (Revenue): Rp {$this->formatNumber($current['total_revenue'])}
- Total HPP (Harga Pokok Produksi): Rp {$this->formatNumber($current['total_hpp'])}
  • Bahan Baku: Rp {$this->formatNumber($current['material_cost'])}
  • Biaya Overhead: Rp {$this->formatNumber($current['overhead_cost'])}
  • Tenaga Kerja: Rp {$this->formatNumber($current['labor_cost'])}
- Laba Kotor: Rp {$this->formatNumber($current['gross_profit'])}
- Margin Laba: {$current['profit_margin']}%

🏭 DATA PRODUKSI:
- Jumlah Batch Selesai: {$current['done_batches']}
- Total Unit Diproduksi: {$this->formatNumber($current['total_produced_units'])}
- Total Unit Baik (Good): {$this->formatNumber($current['total_good_units'])}
- Total Unit Reject (Waste): {$this->formatNumber($current['total_reject_units'])}
- Reject Rate (Tingkat Waste): {$current['reject_rate']}%

💡 FAKTA MATEMATIS (Gunakan data ini sebagai fakta absolut dalam saranmu):
- Target BEP Harian (Break-Even Point): Rp {$bepFormatted}
- Insight Reject: {$rejectFact}
- Prediksi Pendapatan Bulan Depan secara Matematis: Rp {$predRev} (berdasarkan tren pertumbuhan {$predGrowth}%)

Silakan lakukan:
1. AUDIT ANOMALI pada data bulan yang dianalisis.
2. PREDIKSI bulan berikutnya berdasarkan tren historis + fakta matematis di atas.
3. KLASIFIKASI kesehatan bisnis dan ringkasan performa.
4. SARAN STRATEGIS: Wajib manfaatkan Insight Reject jika ada, dan sebutkan nominal uang yang bisa diselamatkan.
MSG;
    }

    /**
     * Parse AI response menjadi array dengan key yang diharapkan.
     */
    private function parseAiResponse(string $content): array
    {
        // Clean up: remove markdown code fences if present
        $content = trim($content);
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('SAHAYU AI: Failed to parse JSON response', ['raw' => $content]);
            return [
                'is_anomaly' => false,
                'anomaly_reason' => null,
                'health_status' => 'Error',
                'summary' => $content,
                'prediction' => 'Tidak tersedia karena AI tidak mengembalikan format JSON yang valid.',
                'advice' => 'Silakan coba lagi.',
            ];
        }

        // Normalize keys — ensure all expected keys exist
        return [
            'is_anomaly' => (bool) ($decoded['is_anomaly'] ?? false),
            'anomaly_reason' => $decoded['anomaly_reason'] ?? null,
            'health_status' => $decoded['health_status'] ?? ($decoded['klasifikasi'] ?? 'Data Tidak Cukup'),
            'summary' => $decoded['summary'] ?? ($decoded['ringkasan'] ?? ''),
            'prediction' => $decoded['prediction'] ?? '',
            'advice' => $decoded['advice'] ?? ($decoded['saran_waste'] ?? ''),
        ];
    }

    /**
     * Format angka ke format Indonesia.
     */
    private function formatNumber(float|int $number): string
    {
        return number_format($number, 0, ',', '.');
    }
}
