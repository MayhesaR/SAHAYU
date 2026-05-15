@extends('layouts.app')
@section('title', 'Produk Jadi')
@section('page_title', 'Manajemen Produk Jadi')
@section('search_placeholder', 'Cari produk...')

@section('content')
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-8">
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="w-full">
        <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-teal-900 tracking-tight break-words">Manajemen Produk Jadi</h2>
        <p class="text-on-surface-variant font-body mt-1 max-w-xl text-sm sm:text-base">Kelola daftar produk jadi agar proses Produksi, Penjualan, dan perhitungan HPP tetap sinkron.</p>
    </div>
    @if(auth()->user()->isAdmin())
        <a class="w-full sm:w-auto px-6 py-2.5 rounded-xl shadow-lg shadow-teal-900/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
           style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
           href="#form-produk">
            <span class="material-symbols-outlined text-base flex-shrink-0">add_circle</span>
            <span>Tambah Produk</span>
        </a>
    @else
        <button class="w-full sm:w-auto px-6 py-2.5 bg-slate-200 text-slate-400 text-sm font-semibold rounded-xl cursor-not-allowed flex items-center justify-center gap-2" type="button" disabled title="Hanya admin yang dapat menambah produk">
            <span class="material-symbols-outlined text-base flex-shrink-0">lock</span>
            <span>Tambah Produk</span>
        </button>
    @endif
</div>

@if (session('success'))
<div class="rounded-xl bg-teal-50 text-teal-800 border border-teal-100 px-4 py-3 text-sm font-medium">
{{ session('success') }}
</div>
@endif

@if ($errors->any())
<div class="rounded-xl bg-red-50 text-red-800 border border-red-100 px-4 py-3 text-sm font-medium space-y-1">
@foreach ($errors->all() as $error)
<div>{{ $error }}</div>
@endforeach
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Total Produk</p>
<h3 class="mt-2 text-3xl font-extrabold text-teal-900">{{ $products->total() }}</h3>
<p class="text-xs text-slate-500 mt-1">produk terdaftar</p>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Harga Rata-rata</p>
<h3 class="mt-2 text-3xl font-extrabold text-teal-900">Rp {{ number_format((float) $products->avg('selling_price'), 0, ',', '.') }}</h3>
<p class="text-xs text-slate-500 mt-1">nilai jual per produk</p>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Stok Barang Jadi</p>
<h3 class="mt-2 text-3xl font-extrabold text-teal-900">{{ number_format((int) $products->sum('stock'), 0, ',', '.') }}</h3>
<p class="text-xs text-slate-500 mt-1">unit siap jual</p>
</article>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
@if(auth()->user()->isAdmin())
<section class="lg:col-span-4 bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300" id="form-produk">
<div class="px-6 py-5 bg-surface-container-low border-b border-gray-100">
<h3 class="text-lg font-bold text-primary flex items-center">
<span class="material-symbols-outlined mr-2 text-primary flex-shrink-0">inventory</span> Tambah Produk
                </h3>
</div>
<form action="{{ route('products.store') }}" method="POST" class="p-6 space-y-5">
@csrf
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nama Produk</label>
<input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="name" placeholder="Contoh: Kue Kering Premium" required type="text"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Harga Jual (Rp)</label>
<input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" min="0" name="selling_price" placeholder="0" required step="0.01" type="number"/>
</div>
<div class="grid grid-cols-2 gap-4">
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Stok Awal</label>
<input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" min="0" name="stock" placeholder="0" required type="number"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Minimum Stok</label>
<input class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" min="0" name="minimum_stock" placeholder="0" required type="number"/>
</div>
</div>
                <button class="w-full px-6 py-3 rounded-full shadow-lg shadow-teal-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
                        style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
                        type="submit">
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>Simpan Produk</span>
                </button>
</form>
</section>
@endif

<section class="{{ auth()->user()->isAdmin() ? 'lg:col-span-8' : 'lg:col-span-12' }} space-y-4">
{{-- Search & Sort Controls --}}
<x-table-controls
    :action="route('products.index')"
    searchPlaceholder="Cari nama produk..."
    :sortOptions="[
        ['value' => 'name_asc', 'label' => 'Nama (A-Z)'],
        ['value' => 'name_desc', 'label' => 'Nama (Z-A)'],
        ['value' => 'selling_price_desc', 'label' => 'Harga Tertinggi'],
        ['value' => 'selling_price_asc', 'label' => 'Harga Terendah'],
        ['value' => 'stock_asc', 'label' => 'Stok Terendah'],
        ['value' => 'stock_desc', 'label' => 'Stok Tertinggi'],
        ['value' => 'created_at_desc', 'label' => 'Terbaru'],
    ]"
/>

<div class="bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
<div class="px-6 py-5 bg-surface-container-low border-b border-gray-100 flex items-center justify-between">
<h3 class="text-lg font-bold text-teal-900 flex items-center">
<span class="material-symbols-outlined mr-2 text-teal-600 flex-shrink-0">table_view</span> Daftar Produk
                </h3>
<span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-1 rounded-full border border-outline-variant/5">{{ $products->total() }} item ditemukan</span>
</div>
<div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
<table class="min-w-[800px] w-full text-xs text-left whitespace-nowrap">
<thead class="bg-slate-50 text-left text-slate-500 uppercase text-xs tracking-widest">
<tr>
<th class="px-6 py-4">Nama Produk</th>
<th class="px-6 py-4">Stok</th>
<th class="px-6 py-4">Minimum</th>
<th class="px-6 py-4">Harga Jual</th>
<th class="px-6 py-4 text-right">Aksi</th>
</tr>
</thead>
<tbody>
@forelse ($products as $product)
<tr class="border-t border-surface-container-high hover:bg-slate-50/70 transition-colors">
<td class="px-6 py-4 font-semibold text-teal-900">{{ $product->name }}</td>
<td class="px-6 py-4 text-slate-700" data-product-stock="{{ $product->id }}" data-stock-value="{{ (int) ($product->stock ?? 0) }}">{{ number_format((int) ($product->stock ?? 0), 0, ',', '.') }}</td>
<td class="px-6 py-4 text-slate-700">{{ number_format((int) ($product->minimum_stock ?? 0), 0, ',', '.') }}</td>
<td class="px-6 py-4 text-slate-700">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                @if(auth()->user()->isAdmin())
                                    <button class="px-4 py-2 rounded-lg text-xs font-black text-[#005050] bg-[#005050]/10 hover:bg-[#005050]/20 transition-colors flex items-center gap-1 edit-product-btn"
                                            data-id="{{ $product->id }}"
                                            data-name="{{ $product->name }}"
                                            data-price="{{ (int)$product->selling_price }}">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                        <span>Edit</span>
                                    </button>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-4 py-2 rounded-lg text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 transition-colors flex items-center gap-1" type="submit">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-10 text-slate-500" colspan="5">
                            <div class="flex flex-col items-center justify-center gap-2 text-center">
                                <span class="material-symbols-outlined text-4xl text-slate-400">inventory_2</span>
                                <p class="font-semibold">Belum ada produk.</p>
                                <p class="text-xs text-slate-400">Tambahkan produk pertama agar modul Produksi dan Penjualan bisa dipakai.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- Pagination --}}
    <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/5">
        {{ $products->appends(request()->query())->links() }}
    </div>
</div>
</section>

<!-- Edit Product Modal -->
<div id="editProductModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl scale-95 opacity-0 transition-all duration-300 modal-content">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-bold text-slate-800">Edit Produk</h3>
            <button onclick="closeEditModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-colors">
                <span class="material-symbols-outlined text-slate-400">close</span>
            </button>
        </div>
        <form id="editProductForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Produk</label>
                    <input name="name" id="edit_name" required class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary/20 text-slate-700 font-semibold" type="text"/>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Harga Jual (Rp)</label>
                    <input name="selling_price" id="edit_price" required class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary/20 text-slate-700 font-semibold" type="number"/>
                </div>
                <button class="w-full py-4 rounded-xl shadow-lg shadow-teal-900/30 transition-all" 
                        style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;" 
                        type="submit">
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const editModal = document.getElementById('editProductModal');
    const editContent = editModal.querySelector('.modal-content');
    const editForm = document.getElementById('editProductForm');

    document.querySelectorAll('.edit-product-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const price = btn.dataset.price;

            document.getElementById('edit_name').value = name;
            document.getElementById('edit_price').value = price;
            editForm.action = `/products/${id}`;

            editModal.classList.remove('hidden');
            setTimeout(() => {
                editContent.classList.remove('scale-95', 'opacity-0');
                editContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        });
    });

    function closeEditModal() {
        editContent.classList.add('scale-95', 'opacity-0');
        editContent.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => editModal.classList.add('hidden'), 300);
    }
</script>
@endsection
