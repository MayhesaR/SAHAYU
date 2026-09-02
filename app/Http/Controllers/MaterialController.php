<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\RawMaterialCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\MaterialUsed;
use App\Events\StockLowAlert;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $rawMaterialCategories = RawMaterialCategory::orderBy('name')->get();
        $categoryChoices = RawMaterialCategory::pluck('name', 'id')->toArray();

        $materials = Material::with('rawMaterialCategory')->filterSortPaginate(
            $request,
            searchableColumns: ['name', 'rawMaterialCategory.name', 'default_supplier', 'unit'],
            filterableColumns: ['raw_material_category_id'],
            defaultSort: 'created_at',
            defaultOrder: 'desc',
            perPage: 15,
        );
        $recentMovements = MaterialStockMovement::with(['material', 'user'])
            ->filterSortPaginate(
                $request,
                searchableColumns: ['reference', 'note', 'material.name'],
                filterableColumns: ['type'],
                defaultSort: 'transaction_date',
                defaultOrder: 'desc',
                perPage: 12,
                dateColumn: 'transaction_date',
                pageName: 'h_page',
                prefix: 'h',
            );

        $criticalMaterials = Material::query()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        return view('ManajemenBahanBaku', [
            'materials' => $materials,
            'rawMaterialCategories' => $rawMaterialCategories,
            'categoryChoices' => $categoryChoices,
            'totalCategories' => RawMaterialCategory::count(),
            'lowStockCount' => $criticalMaterials,
            'inventoryValue' => Material::selectRaw('SUM(stock * price) as total')->value('total') ?? 0,
            'materialsLastUpdatedAt' => Material::max('updated_at'),
            'recentMovements' => $recentMovements,
            'stockInToday' => DB::table('material_stock_movements')
                ->whereDate('transaction_date', now()->toDateString())
                ->where('type', 'in')
                ->sum('quantity'),
            'stockOutToday' => DB::table('material_stock_movements')
                ->whereDate('transaction_date', now()->toDateString())
                ->whereIn('type', ['out', 'adjustment'])
                ->whereColumn('stock_after', '<', 'stock_before')
                ->sum('quantity'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'raw_material_category_id' => ['required', 'exists:raw_material_categories,id'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'purchase_unit' => ['nullable', 'string', 'max:50'],
            'unit_conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'default_supplier' => ['nullable', 'string', 'max:255'],
            'supplier_lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        if (! filled($validated['purchase_unit'] ?? null)) {
            $validated['purchase_unit'] = $validated['unit'];
        }

        if (! filled($validated['unit_conversion_factor'] ?? null)) {
            $validated['unit_conversion_factor'] = 1;
        }

        // Backward compatibility: Populate category string column with the category name
        $categoryName = RawMaterialCategory::where('id', $validated['raw_material_category_id'])->value('name');
        $validated['category'] = $categoryName;

        DB::transaction(function () use ($validated): void {
            $material = Material::create($validated);

            if ((int) $material->stock > 0) {
                $material->stockMovements()->create([
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'quantity' => (int) $material->stock,
                    'stock_before' => 0,
                    'stock_after' => (int) $material->stock,
                    'unit_price' => (float) $material->price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Saldo awal',
                    'note' => 'Stok awal saat material dibuat.',
                ]);
            }
        });

        return redirect()->route('materials.index')->with('success', 'Material berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'raw_material_category_id' => ['required', 'exists:raw_material_categories,id'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'purchase_unit' => ['nullable', 'string', 'max:50'],
            'unit_conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'default_supplier' => ['nullable', 'string', 'max:255'],
            'supplier_lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        // Backward compatibility: Populate category string column with the category name
        $categoryName = RawMaterialCategory::where('id', $validated['raw_material_category_id'])->value('name');
        $validated['category'] = $categoryName;

        $material->update($validated);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $companyId = auth()->user()->company_id;

        $category = RawMaterialCategory::where('company_id', $companyId)
            ->where('name', $validated['name'])
            ->first();

        if (!$category) {
            $category = RawMaterialCategory::create([
                'company_id' => $companyId,
                'name' => $validated['name'],
            ]);
        }

        return response()->json([
            'success' => true,
            'category' => $category,
        ]);
    }

    public function stockIn(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'total_spent' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $material): void {
            /** @var Material $freshMaterial */
            $freshMaterial = Material::query()->lockForUpdate()->findOrFail($material->id);

            $before = (int) $freshMaterial->stock;
            $quantity = (int) $validated['quantity'];
            $after = $before + $quantity;

            $freshMaterial->update([
                'stock' => $after,
                'price' => $validated['unit_price'],
            ]);

            $freshMaterial->stockMovements()->create([
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'unit_price' => (float) $validated['unit_price'],
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                'reference' => $validated['reference'] ?? 'Pembelian bahan',
                'note' => $validated['note'] ?? null,
            ]);

            // Automatically record cash-basis purchases if total spent > 0
            $totalSpent = isset($validated['total_spent']) ? (float)$validated['total_spent'] : 0.0;
            if ($totalSpent > 0) {
                \App\Models\Purchase::create([
                    'company_id' => auth()->user()->company_id,
                    'purchase_date' => $validated['transaction_date'] ?? now()->toDateString(),
                    'total_amount' => $totalSpent,
                    'description' => "Belanja Stok: {$freshMaterial->name} sebanyak {$quantity} {$freshMaterial->unit}",
                ]);
            }
        });

        $this->dispatchEvent(new MaterialUsed(
            materialId: (int) $material->id,
            quantityUsed: (float) (0 - (int) $validated['quantity']),
            productionId: 0
        ));

        $updatedMaterial = Material::findOrFail($material->id);
        if ((int) $updatedMaterial->stock <= (int) ($updatedMaterial->minimum_stock ?? 0)) {
            $this->dispatchEvent(new StockLowAlert(
                productId: (int) $material->id,
                currentStock: (float) $updatedMaterial->stock,
                minimumThreshold: (float) ($updatedMaterial->minimum_stock ?? 0),
                itemType: 'material'
            ));
        }

        return redirect()->route('materials.index')->with('success', 'Stok masuk berhasil dicatat.');
    }

    public function stockOut(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated, $material): void {
            /** @var Material $freshMaterial */
            $freshMaterial = Material::query()->lockForUpdate()->findOrFail($material->id);

            $before = (int) $freshMaterial->stock;
            $quantity = (int) $validated['quantity'];

            if ($quantity > $before) {
                throw ValidationException::withMessages([
                    'stock_out' => 'Jumlah stok keluar melebihi stok tersedia.',
                ]);
            }

            $after = $before - $quantity;

            $freshMaterial->update([
                'stock' => $after,
            ]);

            $freshMaterial->stockMovements()->create([
                'user_id' => auth()->id(),
                'type' => 'out',
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'unit_price' => (float) $freshMaterial->price,
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                'reference' => $validated['reference'] ?? 'Pemakaian bahan',
                'note' => $validated['note'] ?? null,
            ]);
        });

        $this->dispatchEvent(new MaterialUsed(
            materialId: (int) $material->id,
            quantityUsed: (float) $validated['quantity'],
            productionId: 0
        ));

        $updatedMaterial = Material::findOrFail($material->id);
        if ((int) $updatedMaterial->stock <= (int) ($updatedMaterial->minimum_stock ?? 0)) {
            $this->dispatchEvent(new StockLowAlert(
                productId: (int) $material->id,
                currentStock: (float) $updatedMaterial->stock,
                minimumThreshold: (float) ($updatedMaterial->minimum_stock ?? 0),
                itemType: 'material'
            ));
        }

        return redirect()->route('materials.index')->with('success', 'Stok keluar berhasil dicatat.');
    }

    public function adjustStock(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'target_stock' => ['required', 'integer', 'min:0'],
            'transaction_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['required', 'string', 'max:1000'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $originalStock = (int) $material->stock;

        DB::transaction(function () use ($validated, $material): void {
            /** @var Material $freshMaterial */
            $freshMaterial = Material::query()->lockForUpdate()->findOrFail($material->id);

            $before = (int) $freshMaterial->stock;
            $after = (int) $validated['target_stock'];

            if ($before === $after && ! array_key_exists('minimum_stock', $validated)) {
                return;
            }

            $payload = ['stock' => $after];

            if (array_key_exists('minimum_stock', $validated) && $validated['minimum_stock'] !== null) {
                $payload['minimum_stock'] = (int) $validated['minimum_stock'];
            }

            $freshMaterial->update($payload);

            if ($before !== $after) {
                $freshMaterial->stockMovements()->create([
                    'user_id' => auth()->id(),
                    'type' => 'adjustment',
                    'quantity' => abs($after - $before),
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $freshMaterial->price,
                    'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                    'reference' => $validated['reference'] ?? 'Stock opname',
                    'note' => $validated['note'],
                ]);
            }
        });

        $delta = (float) ((int) $validated['target_stock'] - $originalStock);
        if ($delta !== 0.0) {
            $this->dispatchEvent(new MaterialUsed(
                materialId: (int) $material->id,
                quantityUsed: (float) (0 - $delta),
                productionId: 0
            ));
        }

        $updatedMaterial = Material::findOrFail($material->id);
        if ((int) $updatedMaterial->stock <= (int) ($updatedMaterial->minimum_stock ?? 0)) {
            $this->dispatchEvent(new StockLowAlert(
                productId: (int) $material->id,
                currentStock: (float) $updatedMaterial->stock,
                minimumThreshold: (float) ($updatedMaterial->minimum_stock ?? 0),
                itemType: 'material'
            ));
        }

        return redirect()->route('materials.index')->with('success', 'Penyesuaian stok berhasil disimpan.');
    }

    public function updateMinimumStock(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'default_supplier' => ['nullable', 'string', 'max:255'],
            'supplier_lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $material->update($validated);

        if ((int) $material->stock <= (int) ($material->minimum_stock ?? 0)) {
            $this->dispatchEvent(new StockLowAlert(
                productId: (int) $material->id,
                currentStock: (float) $material->stock,
                minimumThreshold: (float) ($material->minimum_stock ?? 0),
                itemType: 'material'
            ));
        }

        return redirect()->route('materials.index')->with('success', 'Batas minimum stok berhasil diperbarui.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        if ($material->productions()->exists()) {
            return redirect()->route('materials.index')->withErrors(['material' => 'Material yang sudah dipakai produksi tidak bisa dihapus.']);
        }

        DB::transaction(function () use ($material) {
            $stockDelta = (int) $material->stock;
            $material->stockMovements()->delete();
            $material->delete();

            if ($stockDelta !== 0) {
                $this->dispatchEvent(new MaterialUsed(
                    materialId: (int) $material->id,
                    quantityUsed: (float) (0 - $stockDelta),
                    productionId: 0
                ));
            }
        });

        return redirect()->route('materials.index')->with('success', 'Material berhasil dihapus.');
    }
    public function exportPdf()
    {
        // Set very high memory limit for PDF generation
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $materials = Material::orderBy('category')->orderBy('name')->get();
            $totalValue = Material::selectRaw('SUM(stock * price) as total')->value('total') ?? 0;

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('materials.export_pdf', [
                'materials' => $materials,
                'totalValue' => $totalValue,
            ]);

            // Mengatur ukuran kertas dan orientasi (Landscape karena kolom mungkin banyak)
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOption('dpi', 96);
            $pdf->setOption('enable_font_subsetting', true);

            return $pdf->download('stok-bahan-baku-'.now()->format('Y-m-d').'.pdf');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['pdf' => 'Gagal generate PDF: ' . $e->getMessage()]);
        }
    }

    public function exportGoogleSheets(\App\Services\SpreadsheetExportService $exportService)
    {
        try {
            $materials = Material::orderBy('category')->orderBy('name')->get();

            $headers = ['No', 'Nama Material', 'Kategori', 'Stok Aktual', 'Unit', 'Harga Satuan (Rp)', 'Total Valuasi (Rp)', 'Supplier'];
            $data = [];

            foreach ($materials as $index => $m) {
                $data[] = [
                    $index + 1,
                    $m->name,
                    $m->category,
                    $m->stock,
                    $m->unit,
                    number_format($m->price, 0, ',', ''),
                    number_format($m->stock * $m->price, 0, ',', ''),
                    $m->default_supplier ?: '-'
                ];
            }

            $options = [
                'statistics' => [
                    6 => 'Total Nilai Inventaris',
                    3 => 'Total Stok Item'
                ]
            ];

            return $exportService->exportAsXlsx('Laporan-Bahan-Baku-UMKM-Pancasila', $headers, $data, $options);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export Spreadsheet Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export spreadsheet: ' . $e->getMessage()]);
        }
    }

    /**
     * Export raw materials to native styled Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        try {
            $export = new \App\Exports\BahanBakuExport($request->all());
            return $export->download();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Material XLSX Export Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['export' => 'Gagal export XLSX: ' . $e->getMessage()]);
        }
    }
}
