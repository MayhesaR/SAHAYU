<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Events\MaterialUsed;
use App\Events\StockLowAlert;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return view('ManajemenProduk', [
            'products' => Product::with(['category', 'ingredients'])->filterSortPaginate(
                $request,
                searchableColumns: ['name'],
                filterableColumns: [],
                defaultSort: 'name',
                defaultOrder: 'asc',
                perPage: 15,
            ),
            'categories' => Category::orderBy('name')->get(),
            'materials' => \App\Models\Material::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('products', 'public');
            $validated['image'] = $path;
        }

        $categoryId = $request->input('category_id');
        if ($request->filled('new_category_name')) {
            $newCat = Category::firstOrCreate([
                'company_id' => auth()->user()->company_id,
                'name' => trim($request->input('new_category_name'))
            ]);
            $categoryId = $newCat->id;
        }
        $validated['category_id'] = $categoryId;

        $product = Product::create($validated);

        // Sync recipe ingredients
        $syncData = [];
        $companyId = auth()->user()->company_id;
        foreach ($request->input('ingredients', []) as $ing) {
            if (!empty($ing['material_id']) && !empty($ing['quantity'])) {
                $syncData[(int) $ing['material_id']] = [
                    'quantity' => (float) $ing['quantity'],
                    'company_id' => $companyId
                ];
            }
        }
        $product->ingredients()->sync($syncData);

        return redirect()->route('products.index')->with('success', 'Produk baru berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $file = $request->file('image');
            $path = $file->store('products', 'public');
            $validated['image'] = $path;
        }

        $categoryId = $request->input('category_id');
        if ($request->filled('new_category_name')) {
            $newCat = Category::firstOrCreate([
                'company_id' => auth()->user()->company_id,
                'name' => trim($request->input('new_category_name'))
            ]);
            $categoryId = $newCat->id;
        }
        $validated['category_id'] = $categoryId;

        $product->update($validated);

        // Sync recipe ingredients
        $syncData = [];
        $companyId = auth()->user()->company_id;
        foreach ($request->input('ingredients', []) as $ing) {
            if (!empty($ing['material_id']) && !empty($ing['quantity'])) {
                $syncData[(int) $ing['material_id']] = [
                    'quantity' => (float) $ing['quantity'],
                    'company_id' => $companyId
                ];
            }
        }
        $product->ingredients()->sync($syncData);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function addStock(Request $request, Product $product): RedirectResponse
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $amount = (int) $validated['amount'];

        try {
            DB::transaction(function () use ($product, $amount) {
                // Load recipe ingredients
                $product->load('ingredients');

                if ($product->ingredients->isNotEmpty()) {
                    $materialIds = $product->ingredients->pluck('id')->all();

                    // Lock materials to prevent race conditions
                    $lockedMaterials = Material::query()
                        ->whereIn('id', $materialIds)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    // 1. Verify stocks
                    foreach ($product->ingredients as $ingredient) {
                        $material = $lockedMaterials->get($ingredient->id);
                        if (!$material) {
                            throw new \Exception("Bahan baku '{$ingredient->name}' tidak ditemukan.");
                        }

                        $requiredQty = (float) $ingredient->pivot->quantity * $amount;
                        if ((float) $material->stock < $requiredQty) {
                            throw new \Exception("Stok bahan '{$material->name}' tidak mencukupi. Butuh " . number_format($requiredQty, 2, ',', '.') . " " . $material->unit . " tetapi hanya tersedia " . number_format((float) $material->stock, 2, ',', '.') . " " . $material->unit . ".");
                        }
                    }

                    // 2. Deduct material stocks, write movements, and dispatch events
                    foreach ($product->ingredients as $ingredient) {
                        $material = $lockedMaterials->get($ingredient->id);
                        $requiredQty = (float) $ingredient->pivot->quantity * $amount;

                        $before = (float) $material->stock;
                        $after = $before - $requiredQty;

                        $material->update([
                            'stock' => $after
                        ]);

                        $material->stockMovements()->create([
                            'type' => 'out',
                            'quantity' => $requiredQty,
                            'stock_before' => $before,
                            'stock_after' => $after,
                            'unit_price' => (float) $material->price,
                            'transaction_date' => now()->toDateString(),
                            'reference' => 'Tambah Stok Langsung',
                            'note' => "Pengurangan otomatis bahan baku untuk penambahan {$amount} pcs produk: {$product->name}",
                        ]);

                        // Fire MaterialUsed event
                        event(new MaterialUsed(
                            materialId: (int) $material->id,
                            quantityUsed: (float) $requiredQty,
                            productionId: 0
                        ));

                        // Fire StockLowAlert if needed
                        if ($after <= (float) ($material->minimum_stock ?? 0)) {
                            event(new StockLowAlert(
                                productId: (int) $material->id,
                                currentStock: $after,
                                minimumThreshold: (float) ($material->minimum_stock ?? 0),
                                itemType: 'material'
                            ));
                        }
                    }
                }

                // 3. Increment product stock, write movements
                $beforeProduct = (int) $product->stock;
                $afterProduct = $beforeProduct + $amount;

                $product->update([
                    'stock' => $afterProduct
                ]);

                $product->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => $amount,
                    'stock_before' => $beforeProduct,
                    'stock_after' => $afterProduct,
                    'unit_price' => (float) $product->selling_price,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Tambah Stok Langsung',
                    'note' => "Penambahan stok langsung sebanyak {$amount} pcs dari menu katalog.",
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('products.index')->with('success', "Stok produk {$product->name} berhasil ditambah sebanyak {$amount} pcs.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized action.');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
