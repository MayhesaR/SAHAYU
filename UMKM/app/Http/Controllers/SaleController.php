<?php

namespace App\Http\Controllers;

use App\Events\ProductSold;
use App\Events\StockLowAlert;
use App\Events\SalesAnalyticsUpdated;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $todaySales = Sale::with(['items.product'])
            ->whereDate('created_at', $today)
            ->latest()
            ->get();

        $todayUnits = (int) SaleItem::whereIn('sale_id', $todaySales->pluck('id'))->sum('quantity');

        $salesHistory = Sale::with(['items.product'])
            ->filterSortPaginate(
                $request,
                searchableColumns: ['customer', 'payment_method', 'items.product.name'],
                filterableColumns: ['payment_method', 'status'],
                defaultSort: 'created_at',
                defaultOrder: 'desc',
                perPage: 15,
            );

        $topProductsRaw = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', $today)
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total'))
            ->groupBy('sale_items.product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topProductNames = Product::whereIn('id', $topProductsRaw->pluck('product_id')->all())
            ->pluck('name', 'id');

        $topProducts = $topProductsRaw->map(fn ($item) => [
            'id' => $item->product_id,
            'name' => $topProductNames[$item->product_id] ?? '-',
            'qty' => (int) $item->total,
        ])->toArray();

        return view('PencatatanPenjualan', [
            'products' => Product::orderBy('name')->get(),
            'todayRevenue' => $todaySales->sum('total'),
            'todayTransactions' => $todaySales->count(),
            'todayUnits' => $todayUnits,
            'todaySales' => $todaySales->take(5),
            'salesHistory' => $salesHistory,
            'topProducts' => $topProducts,
            'businessTip' => $this->generateDynamicTip(),
        ]);
    }

    private function generateDynamicTip()
    {
        // 1. Check for Low Stock
        $lowStockProduct = Product::whereColumn('stock', '<=', 'minimum_stock')->first();
        if ($lowStockProduct) {
            return [
                'title' => 'Stok Kritis!',
                'content' => "Stok {$lowStockProduct->name} sisa {$lowStockProduct->stock} unit. Segera restock agar tidak kehilangan potensi penjualan.",
                'icon' => 'warning'
            ];
        }

        // 2. Check for Popular Product (All Time)
        $popular = SaleItem::select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->first();

        if ($popular) {
            $product = Product::find($popular->product_id);
            if ($product) {
                return [
                    'title' => 'Produk Terlaris',
                    'content' => "{$product->name} adalah bintang Anda! Pertimbangkan untuk membuat paket bundling dengan produk lain.",
                    'icon' => 'star'
                ];
            }
        }

        // 3. Payment Method Insight
        $qrisUsage = Sale::where('payment_method', 'qris')->count();
        if ($qrisUsage < 5) {
            return [
                'title' => 'Tips Pembayaran',
                'content' => "Promosikan pembayaran QRIS untuk mempercepat proses transaksi di kasir.",
                'icon' => 'qr_code_2'
            ];
        }

        // Default Tip
        return [
            'title' => 'Tips Harian',
            'content' => "Selalu catat setiap transaksi sekecil apa pun untuk akurasi laporan laba rugi di akhir bulan.",
            'icon' => 'lightbulb'
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'customer' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $unitPrice = (float) ($product->selling_price ?? 0);
        $total = $unitPrice * (int) $validated['quantity'];
        $quantity = (int) $validated['quantity'];

        $createdSaleId = null;

        DB::transaction(function () use ($validated, $total, $unitPrice, $product, $quantity, &$createdSaleId) {
            $freshProduct = Product::query()->lockForUpdate()->findOrFail($product->id);

            if ((int) $freshProduct->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok produk "'.$freshProduct->name.'" tidak mencukupi.',
                ]);
            }

            $before = (int) $freshProduct->stock;
            $after = $before - $quantity;

            $freshProduct->update(['stock' => $after]);

            $freshProduct->stockMovements()->create([
                'type' => 'out',
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'unit_price' => (float) $freshProduct->selling_price,
                'transaction_date' => now()->toDateString(),
                'reference' => 'Penjualan',
                'note' => 'Pengurangan stok barang jadi karena penjualan.',
            ]);

            $sale = Sale::create([
                'customer' => $validated['customer'] ?? null,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'status' => 'paid',
            ]);

            $createdSaleId = $sale->id;

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'price' => $unitPrice,
            ]);
        });

        event(new ProductSold(
            productId: (int) $validated['product_id'],
            qtyDeducted: $quantity,
        ));

        $updatedProduct = Product::findOrFail($validated['product_id']);
        if ((int) $updatedProduct->stock <= (int) ($updatedProduct->minimum_stock ?? 0)) {
            event(new StockLowAlert(
                productId: (int) $validated['product_id'],
                currentStock: (float) $updatedProduct->stock,
                minimumThreshold: (float) ($updatedProduct->minimum_stock ?? 0),
                itemType: 'product'
            ));
        }

        $todayRevenue = Sale::whereDate('created_at', Carbon::today())->sum('total');
        $todayTransactions = Sale::whereDate('created_at', Carbon::today())->count();
        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', Carbon::today())
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total'))
            ->groupBy('sale_items.product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $productNames = Product::whereIn('id', $topProducts->pluck('product_id')->all())
            ->pluck('name', 'id');

        $topProducts = $topProducts->map(fn ($item) => [
            'id' => $item->product_id,
            'name' => $productNames[$item->product_id] ?? '-',
            'qty' => $item->total,
        ])->toArray();

        $latestSale = null;
        if ($createdSaleId) {
            $sale = Sale::with(['items.product'])->find($createdSaleId);
            if ($sale) {
                $latestSale = [
                    'id' => (int) $sale->id,
                    'time' => $sale->created_at->format('H:i'),
                    'product' => $sale->items->first()?->product?->name ?? '-',
                    'qty' => (int) $sale->items->sum('quantity'),
                    'customer' => $sale->customer ?: 'Walk-in',
                    'payment_method' => strtoupper((string) $sale->payment_method),
                    'payment_dot_class' => $sale->payment_method === 'cash'
                        ? 'bg-amber-500'
                        : ($sale->payment_method === 'qris' ? 'bg-teal-500' : 'bg-blue-500'),
                    'total' => (float) $sale->total,
                    'status' => (string) $sale->status,
                    'status_label' => $sale->status === 'paid' ? 'Lunas' : 'Belum Lunas',
                    'status_class' => $sale->status === 'paid' ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700',
                    'can_delete' => auth()->check() && auth()->user()->isAdmin(),
                    'destroy_url' => route('sales.destroy', $sale),
                ];
            }
        }

        event(new SalesAnalyticsUpdated(
            totalSales: (float) SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereDate('sales.created_at', Carbon::today())
                ->sum('sale_items.quantity'),
            totalTransactions: $todayTransactions,
            totalRevenue: $todayRevenue,
            topProducts: $topProducts,
            latestSale: $latestSale
        ));

        return redirect()->route('sales.index')->with('success', 'Transaksi berhasil dicatat.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        DB::transaction(function () use ($sale): void {
            $sale->loadMissing('items.product');

            foreach ($sale->items as $item) {
                if (! $item->product) {
                    continue;
                }

                $freshProduct = Product::query()->lockForUpdate()->findOrFail($item->product->id);
                $before = (int) $freshProduct->stock;
                $after = $before + (int) $item->quantity;

                $freshProduct->update(['stock' => $after]);

                $freshProduct->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => (int) $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshProduct->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Rollback penjualan #'.$sale->id,
                    'note' => 'Pengembalian stok barang jadi karena transaksi dihapus.',
                ]);
            }

            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function exportPdf()
    {
        // Limit data SIGNIFICANTLY to prevent memory exhaustion - export only last 100 sales
        $sales = Sale::with('items.product')->orderByDesc('created_at')->limit(100)->get();

        // Set very high memory limit for PDF generation (must be before DomPDF loads)
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.export_pdf', [
                'sales' => $sales,
            ]);

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOption('dpi', 96);
            $pdf->setOption('enable_font_subsetting', true);

            return $pdf->download('laporan-penjualan-'.now()->format('Y-m-d').'.pdf');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['pdf' => 'Gagal generate PDF: ' . $e->getMessage()]);
        }
    }

    public function exportGoogleSheets(\App\Services\SpreadsheetExportService $exportService)
    {
        try {
            $sales = Sale::with('items.product')->orderByDesc('created_at')->get();

            $headers = ['No Transaksi', 'Tanggal', 'Pelanggan', 'Metode Bayar', 'Produk', 'Jumlah (Pcs)', 'Total (Rp)'];
            $data = [];

            foreach ($sales as $s) {
                // Kumpulkan produk yang dibeli
                $products = $s->items->map(function ($item) {
                    return ($item->product ? $item->product->name : '-') . ' ('.$item->quantity.')';
                })->join(', ');

                $totalQty = $s->items->sum('quantity');

                $data[] = [
                    $s->id,
                    $s->created_at->format('Y-m-d H:i'),
                    $s->customer ?: 'Umum',
                    strtoupper($s->payment_method),
                    $products,
                    $totalQty,
                    number_format($s->total, 0, ',', '')
                ];
            }

            $options = [
                'statistics' => [
                    6 => 'Total Omzet (Rp)',
                    5 => 'Total Item Terjual'
                ]
            ];

            return $exportService->exportAsXlsx('Riwayat-Penjualan-UMKM-Pancasila', $headers, $data, $options);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export Spreadsheet Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export spreadsheet: ' . $e->getMessage()]);
        }
    }
}
