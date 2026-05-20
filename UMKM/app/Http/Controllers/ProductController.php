<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return view('ManajemenProduk', [
            'products' => Product::with('category')->filterSortPaginate(
                $request,
                searchableColumns: ['name'],
                filterableColumns: [],
                defaultSort: 'name',
                defaultOrder: 'asc',
                perPage: 15,
            ),
            'categories' => Category::orderBy('name')->get(),
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

        Product::create($validated);

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

        $product->increment('stock', $validated['amount']);

        return redirect()->route('products.index')->with('success', "Stok produk {$product->name} berhasil ditambah sebanyak {$validated['amount']} pcs.");
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
