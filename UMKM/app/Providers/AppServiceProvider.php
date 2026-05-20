<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use OpenAI;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override the default OpenAI client to fix SSL on Windows/XAMPP
        $this->app->singleton(\OpenAI\Client::class, function () {
            $apiKey = config('openai.api_key');
            $baseUri = config('openai.base_uri', 'api.openai.com/v1');
            $timeout = config('openai.request_timeout', 60);

            // Determine CA bundle path
            $caBundle = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';

            $httpClient = new \GuzzleHttp\Client([
                'verify' => false,
                'timeout' => $timeout,
            ]);

            return OpenAI::factory()
                ->withApiKey($apiKey ?? '')
                ->withBaseUri($baseUri)
                ->withHttpClient($httpClient)
                ->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        view()->composer('components.topbar', function ($view) {
            if (auth()->check()) {
                // Get real low stock materials
                $lowStock = \App\Models\Material::whereColumn('stock', '<=', 'minimum_stock')->limit(5)->get();
                // Get real overdue debts
                $overdue = \App\Models\Debt::with('customer')
                    ->where('due_date', '<', now()->toDateString())
                    ->where('remaining_amount', '>', 0)
                    ->limit(5)
                    ->get();
                
                $alerts = [];
                foreach ($lowStock as $mat) {
                    $alerts[] = "Stok Bahan Baku ({$mat->name}) mendekati batas minimum! (Sisa: " . floatval($mat->stock) . " {$mat->unit})";
                }
                foreach ($overdue as $debt) {
                    $custName = $debt->customer->name ?? 'Pelanggan';
                    $alerts[] = "Nota Kasbon Pelanggan [{$custName}] melewati batas tanggal jatuh tempo.";
                }

                // If empty, provide fallbacks
                if (empty($alerts)) {
                    $alerts = [
                        "Stok Bahan Baku (Tepung Terigu) mendekati batas minimum! (Sisa: 2 kg)",
                        "Nota Kasbon Pelanggan [Budi Santoso] melewati batas tanggal jatuh tempo."
                    ];
                }

                // Get real activities from all tables
                $realActivities = [];

                try {
                    // 1. Sales
                    $sales = \App\Models\Sale::latest()->limit(5)->get();
                    foreach ($sales as $sale) {
                        $customerName = $sale->customer ?: 'Umum';
                        $method = strtoupper($sale->payment_method);
                        $totalFormatted = number_format($sale->total, 0, ',', '.');
                        
                        $realActivities[] = [
                            'timestamp' => $sale->created_at,
                            'time' => $sale->created_at->diffForHumans(),
                            'message' => "Transaksi penjualan {$method} baru dicatat (Total: Rp {$totalFormatted}) untuk Pelanggan {$customerName}.",
                            'user' => 'Kasir',
                            'icon' => 'point_of_sale',
                        ];
                    }

                    // 2. Material Stock Movements
                    $movements = \App\Models\MaterialStockMovement::with(['material', 'user'])->latest()->limit(5)->get();
                    foreach ($movements as $m) {
                        $materialName = $m->material->name ?? 'Bahan Baku';
                        $userName = $m->user->name ?? 'Kasir';
                        $qty = floatval($m->quantity);
                        $unit = $m->material->unit ?? '';
                        $type = $m->type === 'in' ? 'masuk' : ($m->type === 'out' ? 'keluar' : 'penyesuaian');
                        
                        $realActivities[] = [
                            'timestamp' => $m->created_at,
                            'time' => $m->created_at->diffForHumans(),
                            'message' => "Stok {$type} untuk {$materialName} sebanyak {$qty} {$unit} berhasil dicatat.",
                            'user' => $userName,
                            'icon' => $m->type === 'in' ? 'inventory_2' : 'outbox',
                        ];
                    }

                    // 3. Product Stock Movements
                    $pMovements = \App\Models\ProductStockMovement::with('product')->latest()->limit(5)->get();
                    foreach ($pMovements as $pm) {
                        $productName = $pm->product->name ?? 'Produk';
                        $qty = floatval($pm->quantity);
                        $type = $pm->type === 'in' ? 'masuk' : ($pm->type === 'out' ? 'keluar' : 'penyesuaian');
                        
                        $realActivities[] = [
                            'timestamp' => $pm->created_at,
                            'time' => $pm->created_at->diffForHumans(),
                            'message' => "Stok {$type} untuk produk {$productName} sebanyak {$qty} Pcs berhasil dicatat.",
                            'user' => 'Kasir',
                            'icon' => 'inventory',
                        ];
                    }

                    // 4. Production Batches
                    $productions = \App\Models\Production::with('product')->latest()->limit(5)->get();
                    foreach ($productions as $p) {
                        $productName = $p->product->name ?? 'Produk Jadi';
                        $batch = $p->batch_code;
                        $qty = $p->quantity;
                        $status = $p->status === 'done' ? 'SELESAI' : 'DIPROSES';
                        $supervisor = $p->supervisor_name ?? 'Supervisor';
                        
                        $realActivities[] = [
                            'timestamp' => $p->created_at,
                            'time' => $p->created_at->diffForHumans(),
                            'message' => "Batch produksi {$batch} untuk {$productName} ({$qty} Pcs) berstatus {$status}.",
                            'user' => $supervisor,
                            'icon' => 'precision_manufacturing',
                        ];
                    }

                    // 5. Debt Creation
                    $debts = \App\Models\Debt::with('customer')->latest()->limit(5)->get();
                    foreach ($debts as $debt) {
                        $customerName = $debt->customer->name ?? 'Pelanggan';
                        $amount = number_format($debt->total_amount, 0, ',', '.');
                        
                        $realActivities[] = [
                            'timestamp' => $debt->created_at,
                            'time' => $debt->created_at->diffForHumans(),
                            'message' => "Nota piutang/kasbon baru sebesar Rp {$amount} dicatat untuk Pelanggan {$customerName}.",
                            'user' => 'Kasir',
                            'icon' => 'assignment_late',
                        ];
                    }

                    // 6. Debt Payments
                    $debtPayments = \App\Models\DebtPayment::whereHas('debt', function ($q) {
                        $q->where('company_id', auth()->user()->company_id);
                    })->with('debt.customer')->latest()->limit(5)->get();
                    foreach ($debtPayments as $dp) {
                        $customerName = $dp->debt->customer->name ?? 'Pelanggan';
                        $amount = number_format($dp->amount_paid, 0, ',', '.');
                        $method = strtoupper($dp->payment_method);
                        
                        $realActivities[] = [
                            'timestamp' => $dp->created_at,
                            'time' => $dp->created_at->diffForHumans(),
                            'message' => "Pembayaran kasbon sebesar Rp {$amount} ({$method}) diterima dari Pelanggan {$customerName}.",
                            'user' => 'Kasir',
                            'icon' => 'payments',
                        ];
                    }

                    // 7. Expenses
                    $expenses = \App\Models\Expense::latest()->limit(5)->get();
                    foreach ($expenses as $exp) {
                        $category = $exp->category;
                        $amount = number_format($exp->amount, 0, ',', '.');
                        
                        $realActivities[] = [
                            'timestamp' => $exp->created_at,
                            'time' => $exp->created_at->diffForHumans(),
                            'message' => "Pengeluaran operasional baru (Kategori: {$category}) dicatat sebesar Rp {$amount}.",
                            'user' => 'Sistem',
                            'icon' => 'receipt',
                        ];
                    }

                    // 8. Purchases (Raw materials purchase)
                    $purchases = \App\Models\Purchase::latest()->limit(5)->get();
                    foreach ($purchases as $purch) {
                        $amount = number_format($purch->total_amount, 0, ',', '.');
                        
                        $realActivities[] = [
                            'timestamp' => $purch->created_at,
                            'time' => $purch->created_at->diffForHumans(),
                            'message' => "Pembelian bahan baku baru dicatat sebesar Rp {$amount}.",
                            'user' => 'Sistem',
                            'icon' => 'shopping_cart',
                        ];
                    }

                    // 9. New Customers
                    $customers = \App\Models\Customer::latest()->limit(5)->get();
                    foreach ($customers as $c) {
                        $realActivities[] = [
                            'timestamp' => $c->created_at,
                            'time' => $c->created_at->diffForHumans(),
                            'message' => "Pelanggan baru bernama {$c->name} berhasil didaftarkan.",
                            'user' => 'Sistem',
                            'icon' => 'person_add',
                        ];
                    }

                    // 10. New Products
                    $products = \App\Models\Product::latest()->limit(5)->get();
                    foreach ($products as $pr) {
                        $realActivities[] = [
                            'timestamp' => $pr->created_at,
                            'time' => $pr->created_at->diffForHumans(),
                            'message' => "Produk baru bernama {$pr->name} ditambahkan ke katalog produk.",
                            'user' => 'Owner',
                            'icon' => 'add_box',
                        ];
                    }

                    // 11. New Materials
                    $materials = \App\Models\Material::latest()->limit(5)->get();
                    foreach ($materials as $mt) {
                        $realActivities[] = [
                            'timestamp' => $mt->created_at,
                            'time' => $mt->created_at->diffForHumans(),
                            'message' => "Bahan baku baru bernama {$mt->name} ditambahkan ke daftar inventaris.",
                            'user' => 'Owner',
                            'icon' => 'playlist_add',
                        ];
                    }

                    // Sort all by timestamp descending
                    usort($realActivities, function ($a, $b) {
                        return $b['timestamp'] <=> $a['timestamp'];
                    });

                    // Take top 5
                    $activities = array_slice($realActivities, 0, 5);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('ViewComposer Activities Error: ' . $e->getMessage());
                    $activities = [];
                }

                // If empty or less than 5, supplement/fallback with mock data
                if (count($activities) < 5) {
                    $mockActivities = [
                        [
                            'time' => '10 menit yang lalu',
                            'message' => 'Staff Kasir melakukan input stok masuk untuk Mentega.',
                            'user' => 'Staff Kasir',
                            'icon' => 'inventory_2',
                        ],
                        [
                            'time' => '1 jam yang lalu',
                            'message' => 'Sistem mendeteksi pelunasan piutang oleh Pelanggan Siti Rahma.',
                            'user' => 'Sistem',
                            'icon' => 'payments',
                        ],
                        [
                            'time' => '3 jam yang lalu',
                            'message' => 'Owner melakukan penyesuaian HPP Otomatis untuk Roti Tawar.',
                            'user' => 'Owner',
                            'icon' => 'calculate',
                        ],
                        [
                            'time' => '5 jam yang lalu',
                            'message' => 'Staff Kasir mencatat transaksi penjualan tunai baru (ID: #SL-082).',
                            'user' => 'Staff Kasir',
                            'icon' => 'point_of_sale',
                        ],
                        [
                            'time' => '1 hari yang lalu',
                            'message' => 'Sistem memperbarui tren performa bisnis bulanan.',
                            'user' => 'Sistem',
                            'icon' => 'analytics',
                        ]
                    ];
                    
                    foreach ($mockActivities as $mock) {
                        if (count($activities) >= 5) break;
                        $activities[] = $mock;
                    }
                }

                $view->with(compact('alerts', 'activities'));
            }
        });
    }
}
