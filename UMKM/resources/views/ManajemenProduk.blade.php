@extends('layouts.app')
@section('title', 'Produk Jadi')
@section('page_title', 'Manajemen Produk Jadi')
@section('search_placeholder', 'Cari produk...')

@section('content')
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="w-full">
            <h2 class="text-xl sm:text-2xl lg:text-3xl font-black text-stone-800 dark:text-white tracking-tight break-words font-manrope">
                Manajemen Produk Jadi
            </h2>
            <p class="text-stone-500 dark:text-white font-medium mt-1 max-w-xl text-xs sm:text-sm">
                Kelola daftar produk jadi agar proses Produksi, Penjualan, dan perhitungan HPP tetap sinkron.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
            <!-- Guided Tour Button -->
            <button type="button" id="btn-start-tour"
                    class="bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-2.5 text-xs font-bold transition-all flex items-center justify-center gap-2 border border-emerald-200/50 shadow-sm w-full sm:w-auto">
                <span class="material-symbols-outlined text-[16px]">lightbulb</span>
                Panduan Produk
            </button>
            @if(auth()->user()->isAdmin())
                <a class="w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl py-3 px-5 shadow-md shadow-emerald-500/20 font-semibold hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center justify-center gap-2 flex-shrink-0 text-xs sm:text-sm" 
                   href="#form-produk">
                    <span class="material-symbols-outlined text-base flex-shrink-0 font-bold">add_circle</span>
                    <span>Tambah Produk Baru</span>
                </a>
            @else
                <button class="w-full sm:w-auto px-5 py-3 bg-stone-200 dark:bg-zinc-800 text-stone-400 dark:text-white text-xs font-bold rounded-xl cursor-not-allowed flex items-center justify-center gap-2 flex-shrink-0" type="button" disabled title="Hanya admin yang dapat menambah produk">
                    <span class="material-symbols-outlined text-base flex-shrink-0">lock</span>
                    <span>Tambah Produk Baru</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Sessions and Errors Alert -->
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 text-emerald-800 dark:text-emerald-400 dark:text-emerald-300 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in text-xs font-bold">
            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 text-rose-800 dark:text-rose-300 rounded-2xl space-y-1 shadow-sm text-xs font-bold">
            @foreach ($errors->all() as $error)
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-600 dark:text-rose-400 text-sm">error</span>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Stat Grid -->
    <div id="tour-product-stats" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <article class="bg-white dark:bg-zinc-900 rounded-3xl p-5 shadow-lg shadow-emerald-900/5 border border-stone-100 dark:border-zinc-800/60 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-200 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-2xl font-bold">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-widest text-stone-400 dark:text-zinc-400 font-bold truncate">Total Produk</p>
                <h3 class="mt-0.5 text-2xl font-black text-stone-800 dark:text-white tracking-tight">{{ $products->total() }}</h3>
                <p class="text-[10px] text-stone-500 dark:text-zinc-400 font-medium">produk terdaftar</p>
            </div>
        </article>

        <article class="bg-white dark:bg-zinc-900 rounded-3xl p-5 shadow-lg shadow-emerald-900/5 border border-stone-100 dark:border-zinc-800/60 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-200 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-2xl font-bold">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-widest text-stone-400 dark:text-zinc-400 font-bold truncate">Harga Rata-rata</p>
                <h3 class="mt-0.5 text-2xl font-black text-stone-800 dark:text-white tracking-tight">Rp {{ number_format((float) $products->avg('selling_price'), 0, ',', '.') }}</h3>
                <p class="text-[10px] text-stone-500 dark:text-zinc-400 font-medium">nilai jual per produk</p>
            </div>
        </article>

        <article class="bg-white dark:bg-zinc-900 rounded-3xl p-5 shadow-lg shadow-emerald-900/5 border border-stone-100 dark:border-zinc-800/60 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-200 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 dark:text-blue-450 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-2xl font-bold">fact_check</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-widest text-stone-400 dark:text-zinc-400 font-bold truncate">Stok Barang Jadi</p>
                <h3 class="mt-0.5 text-2xl font-black text-stone-800 dark:text-white tracking-tight">{{ number_format((int) $products->sum('stock'), 0, ',', '.') }}</h3>
                <p class="text-[10px] text-stone-500 dark:text-zinc-400 font-medium">unit siap jual</p>
            </div>
        </article>
    </div>

    <!-- Main Workspace Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left Side: Add Product Form (Admins Only) -->
        @if(auth()->user()->isAdmin())
            <section class="lg:col-span-4 bg-white dark:bg-zinc-900 rounded-3xl shadow-lg shadow-emerald-900/5 border border-stone-100 dark:border-zinc-800/60 overflow-hidden hover:shadow-xl transition-all duration-300" id="form-produk">
                <div class="px-6 py-5 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 dark:bg-transparent border-b border-stone-100 dark:border-zinc-800/60">
                    <h3 class="text-base font-bold text-stone-800 dark:text-white flex items-center">
                        <span class="material-symbols-outlined mr-2 text-emerald-600 dark:text-emerald-400 flex-shrink-0 font-bold">inventory</span> Tambah Produk Baru
                    </h3>
                </div>
                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                    @csrf
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Nama Produk</label>
                        <input class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl p-3 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-800 dark:text-white font-semibold transition-all" name="name" placeholder="Contoh: Kue Kering Premium" required type="text"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Foto Produk</label>
                        <div class="relative bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 rounded-xl p-3 flex items-center gap-3 border-2 border-dashed border-stone-200 dark:border-zinc-800">
                            <span class="material-symbols-outlined text-stone-400 dark:text-zinc-400">image</span>
                            <input class="w-full text-xs text-stone-500 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 dark:file:bg-emerald-950/40 file:text-emerald-700 dark:file:text-zinc-400 hover:file:bg-emerald-100 cursor-pointer font-bold" name="image" accept="image/*" type="file"/>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Kategori Produk</label>
                        <select name="category_id" class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl p-3 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-700 dark:text-zinc-50 dark:text-zinc-200 font-semibold transition-all">
                            <option value="">-- Pilih Kategori (Opsional) --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <input class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl p-3 text-xs focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-600 dark:text-zinc-300 mt-1 transition-all" name="new_category_name" placeholder="Atau ketik kategori baru..." type="text"/>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Harga Jual (Rp)</label>
                        <input class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl p-3 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-800 dark:text-white font-bold transition-all font-mono" min="0" name="selling_price" placeholder="0" required step="0.01" type="number"/>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Stok Awal</label>
                            <input class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl p-3 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-800 dark:text-white font-bold transition-all font-mono" min="0" name="stock" placeholder="0" required type="number"/>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Min. Stok</label>
                            <input class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl p-3 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-800 dark:text-white font-bold transition-all font-mono" min="0" name="minimum_stock" placeholder="0" required type="number"/>
                        </div>
                    </div>
                    
                    <!-- Resep Standar (Bahan Baku) -->
                    <div class="space-y-3" x-data="{
                        rows: [{ material_id: '', quantity: '' }]
                    }">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Resep Standar (Bahan Baku)</label>
                            <button class="px-2 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold rounded-lg border border-emerald-200/50 dark:border-emerald-800/40 flex items-center hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-all flex-shrink-0" type="button" @click="rows.push({ material_id: '', quantity: '' })">
                                <span class="material-symbols-outlined text-xs mr-0.5 font-bold">add_circle</span> Tambah Bahan
                            </button>
                        </div>
                        
                        <div class="space-y-2">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <div class="flex items-center gap-2 bg-stone-50/50 dark:bg-zinc-850/20 p-2 rounded-xl border border-stone-150 dark:border-zinc-800/40 min-w-0">
                                    <select :name="'ingredients['+idx+'][material_id]'" x-model="row.material_id" required class="flex-1 min-w-0 bg-transparent border-none text-xs font-semibold focus:ring-0 text-stone-700 dark:text-zinc-350 bg-stone-50 dark:bg-zinc-800">
                                        <option value="">Pilih bahan...</option>
                                        @foreach($materials as $mat)
                                            <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->unit }})</option>
                                        @endforeach
                                    </select>
                                    <input type="number" step="any" min="0.0001" :name="'ingredients['+idx+'][quantity]'" x-model="row.quantity" placeholder="Qty" required class="w-20 flex-shrink-0 bg-white dark:bg-zinc-900 border-none rounded-lg p-1.5 text-xs text-center font-bold text-stone-850 dark:text-white focus:ring-1 focus:ring-emerald-500/30"/>
                                    <button type="button" @click="if(rows.length > 1) rows.splice(idx,1)" class="text-stone-350 hover:text-red-500 transition-colors p-1 flex-shrink-0">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <button class="w-full px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full shadow-lg shadow-emerald-500/20 hover:scale-[1.01] active:scale-95 transition-all flex items-center justify-center gap-2 font-bold text-xs" 
                            type="submit">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>SIMPAN PRODUK BARU</span>
                    </button>
                </form>
            </section>
        @endif

        <!-- Right Side: Catalog Card Grid -->
        <section class="{{ auth()->user()->isAdmin() ? 'lg:col-span-8' : 'lg:col-span-12' }} space-y-6">
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

            <!-- Card Grid Layout -->
            <div id="tour-product-catalog" class="grid grid-cols-1 sm:grid-cols-2 {{ auth()->user()->isAdmin() ? 'xl:grid-cols-3' : 'lg:grid-cols-4' }} gap-6">
                @forelse ($products as $product)
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl p-5 shadow-lg shadow-emerald-900/5 border border-stone-100 dark:border-zinc-800/60 transition-all duration-200 hover:-translate-y-1 hover:shadow-xl flex flex-col justify-between relative overflow-hidden group">
                        
                        <div>
                            <!-- TOP (Image Area) -->
                            <div class="h-40 w-full bg-stone-100 dark:bg-zinc-800 rounded-2xl overflow-hidden mb-4 relative flex items-center justify-center">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="{{ $product->name }}">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-stone-400 dark:text-zinc-400">
                                        <span class="material-symbols-outlined text-4xl mb-1 text-stone-300">image_not_supported</span>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">No Image</span>
                                    </div>
                                @endif

                                <!-- Actions Overlay for Admin -->
                                @if(auth()->user()->isAdmin())
                                    <div class="absolute top-3 right-3 flex items-center gap-1.5 z-10 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200">
                                        <button class="w-8 h-8 rounded-full bg-white/95 hover:bg-emerald-500 hover:text-white text-[#0b6e4f] dark:text-emerald-400 shadow-md flex items-center justify-center transition-all edit-product-btn"
                                                data-id="{{ $product->id }}"
                                                data-name="{{ $product->name }}"
                                                data-price="{{ (int)$product->selling_price }}"
                                                data-category-id="{{ $product->category_id }}"
                                                data-ingredients="{{ json_encode($product->ingredients->map(fn($ing) => ['material_id' => $ing->id, 'quantity' => (float) $ing->pivot->quantity])) }}">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                        </button>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="w-8 h-8 rounded-full bg-white/95 hover:bg-rose-500 hover:text-white text-rose-600 dark:text-rose-400 shadow-md flex items-center justify-center transition-all" type="submit">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <!-- MIDDLE (Product Info) -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    @if($product->category)
                                        <span class="text-[9px] font-bold uppercase bg-emerald-50 dark:bg-emerald-950/40 text-[#0b6e4f] dark:text-emerald-400 px-2 py-0.5 rounded-md border border-emerald-100/50 tracking-wider">
                                            {{ $product->category->name }}
                                        </span>
                                    @else
                                        <span class="text-[9px] font-bold uppercase bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 text-stone-400 dark:text-zinc-400 px-2 py-0.5 rounded-md border border-stone-100 dark:border-zinc-800/60 tracking-wider">
                                            Umum
                                        </span>
                                    @endif
                                </div>
                                
                                <h4 class="text-base font-bold text-stone-800 dark:text-white tracking-tight leading-snug line-clamp-2 mt-1 min-h-[2.5rem]">
                                    {{ $product->name }}
                                </h4>
                                
                                <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                </div>
                            </div>

                            <!-- STOCK BADGE STATUS -->
                            <div class="mt-3 flex items-center justify-between">
                                @if($product->stock > 10)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-100/50">
                                        Stok: {{ number_format((int)$product->stock, 0, ',', '.') }} pcs
                                    </span>
                                @elseif($product->stock <= 10 && $product->stock > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-100/50">
                                        Stok Menipis: {{ number_format((int)$product->stock, 0, ',', '.') }} pcs
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-100/50">
                                        Stok Habis
                                    </span>
                                @endif
                                
                                <span class="text-[9px] text-stone-400 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                                    Min: {{ number_format((int)$product->minimum_stock, 0, ',', '.') }} pcs
                                </span>
                            </div>
                        </div>

                        <!-- BOTTOM (Quick Add Stock Form or spacing) -->
                        @if(auth()->user()->isAdmin())
                            <div x-data="{ showInput: false, amount: 1 }" class="mt-4 pt-4 border-t border-stone-100 dark:border-zinc-800/60">
                                <!-- Default State Button -->
                                <button x-show="!showInput" @click="showInput = true" type="button"
                                        class="w-full bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600 dark:hover:text-emerald-400 text-stone-700 dark:text-zinc-50 dark:text-white font-bold py-2 rounded-xl transition-all text-xs flex items-center justify-center gap-1.5 border border-stone-200/40 dark:border-zinc-800/40 shadow-sm">
                                    <span class="material-symbols-outlined text-sm font-bold">add</span>
                                    <span>Tambah Stok</span>
                                </button>
                                
                                <!-- Active State Form -->
                                <form x-show="showInput" x-cloak action="{{ route('products.add-stock', $product) }}" method="POST"
                                      class="flex items-center justify-between gap-1.5 transition-all">
                                    @csrf
                                    <div class="flex items-center bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl px-1.5 py-0.5 max-w-[120px]">
                                        <button type="button" @click="amount = Math.max(1, amount - 1)"
                                                class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-stone-200/50 text-stone-600 dark:text-white font-bold text-sm">
                                            -
                                        </button>
                                        <input type="number" name="amount" x-model.number="amount" min="1"
                                               class="w-8 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-xs font-bold text-stone-800 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                        <button type="button" @click="amount = amount + 1"
                                                class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-stone-200/50 text-stone-600 dark:text-white font-bold text-sm">
                                            +
                                        </button>
                                    </div>
                                    
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="showInput = false; amount = 1"
                                                class="w-8 h-8 rounded-lg bg-stone-100 dark:bg-zinc-800 hover:bg-stone-200 dark:hover:bg-zinc-800 text-stone-500 dark:text-white hover:text-stone-700 dark:hover:text-zinc-50 dark:hover:text-zinc-400 transition-colors flex items-center justify-center">
                                            <span class="material-symbols-outlined text-base">close</span>
                                        </button>
                                        <button type="submit"
                                                class="w-8 h-8 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white transition-colors flex items-center justify-center shadow-sm">
                                            <span class="material-symbols-outlined text-base">check</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-zinc-900 rounded-3xl p-12 text-stone-500 dark:text-zinc-300 border border-stone-150 dark:border-zinc-800/60 shadow-lg shadow-emerald-900/5 text-center">
                        <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                            <span class="material-symbols-outlined text-4xl text-stone-400 dark:text-zinc-500 font-light">inventory_2</span>
                            <p class="font-bold text-stone-700 dark:text-zinc-200">Katalog masih kosong</p>
                            <p class="text-xs text-stone-400 dark:text-zinc-500">Klik tombol tambah di atas untuk memasukkan data baru.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $products->appends(request()->query())->links() }}
            </div>
        </section>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="fixed inset-0 bg-slate-900/60 dark:bg-zinc-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white dark:bg-zinc-900 rounded-3xl p-8 w-full max-w-md shadow-2xl scale-95 opacity-0 transition-all duration-300 modal-content">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-black text-stone-800 dark:text-white font-manrope">Edit Produk</h3>
            <button onclick="closeEditModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 transition-colors">
                <span class="material-symbols-outlined text-stone-400 dark:text-zinc-400">close</span>
            </button>
        </div>
        <form id="editProductForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Nama Produk</label>
                    <input name="name" id="edit_name" required class="w-full px-4 py-3 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-800 dark:text-white font-semibold" type="text"/>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Foto Produk</label>
                    <div class="relative bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 rounded-xl p-3 flex flex-col gap-2 border-2 border-dashed border-stone-200 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-stone-400 dark:text-zinc-400">image</span>
                            <input class="w-full text-xs text-stone-500 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 dark:file:bg-emerald-950/40 file:text-emerald-700 dark:file:text-zinc-400 hover:file:bg-emerald-100 cursor-pointer font-bold" name="image" accept="image/*" type="file"/>
                        </div>
                        <p class="text-[10px] text-stone-400 dark:text-zinc-400 font-semibold">*Biarkan kosong jika tidak ingin mengubah foto</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Kategori Produk</label>
                    <select name="category_id" id="edit_category_id" class="w-full px-4 py-3 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-700 dark:text-zinc-50 dark:text-zinc-200 font-semibold">
                        <option value="">-- Pilih Kategori (Opsional) --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <input class="w-full px-4 py-2.5 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-600 dark:text-white text-xs mt-1" name="new_category_name" placeholder="Atau ketik kategori baru..." type="text"/>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Harga Jual (Rp)</label>
                    <input name="selling_price" id="edit_price" required class="w-full px-4 py-3 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-stone-800 dark:text-white font-bold font-mono" type="number"/>
                </div>
                
                <!-- Resep Standar (Bahan Baku) -->
                <div class="space-y-3" id="edit-recipe-container" x-data="{
                    rows: []
                }" @edit-product-loaded.window="rows = $event.detail.ingredients.length > 0 ? $event.detail.ingredients : [{ material_id: '', quantity: '' }]">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Resep Standar (Bahan Baku)</label>
                        <button class="px-2 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold rounded-lg border border-emerald-200/50 dark:border-emerald-800/40 flex items-center hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-all flex-shrink-0" type="button" @click="rows.push({ material_id: '', quantity: '' })">
                            <span class="material-symbols-outlined text-xs mr-0.5 font-bold">add_circle</span> Tambah Bahan
                        </button>
                    </div>
                    
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <template x-for="(row, idx) in rows" :key="idx">
                            <div class="flex items-center gap-2 bg-stone-50/50 dark:bg-zinc-850/20 p-2 rounded-xl border border-stone-150 dark:border-zinc-800/40 min-w-0">
                                <select :name="'ingredients['+idx+'][material_id]'" x-model="row.material_id" required class="flex-1 min-w-0 bg-transparent border-none text-xs font-semibold focus:ring-0 text-stone-700 dark:text-zinc-350 bg-stone-50 dark:bg-zinc-800">
                                    <option value="">Pilih bahan...</option>
                                    @foreach($materials as $mat)
                                        <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->unit }})</option>
                                    @endforeach
                                </select>
                                <input type="number" step="any" min="0.0001" :name="'ingredients['+idx+'][quantity]'" x-model="row.quantity" placeholder="Qty" required class="w-20 flex-shrink-0 bg-white dark:bg-zinc-900 border-none rounded-lg p-1.5 text-xs text-center font-bold text-stone-850 dark:text-white focus:ring-1 focus:ring-emerald-500/30"/>
                                <button type="button" @click="if(rows.length > 1) rows.splice(idx,1)" class="text-stone-350 hover:text-red-500 transition-colors p-1 flex-shrink-0">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
                <button class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-500/20 transition-all font-bold text-xs uppercase tracking-widest" 
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
            const categoryId = btn.dataset.categoryId;
            const ingredients = JSON.parse(btn.dataset.ingredients || '[]');

            document.getElementById('edit_name').value = name;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_category_id').value = categoryId || '';
            editForm.action = `/products/${id}`;

            // Dispatch event to load ingredients in the Alpine.js component inside the modal
            window.dispatchEvent(new CustomEvent('edit-product-loaded', { detail: { ingredients: ingredients } }));

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

    // Driver.js Guided Tour Initialization for Manajemen Produk
    document.addEventListener('DOMContentLoaded', function () {
        const btnStartTour = document.getElementById('btn-start-tour');
        if (btnStartTour && window.driver) {
            const driver = window.driver.js.driver;
            
            const steps = [];
            
            if (document.getElementById('form-produk')) {
                steps.push({
                    element: '#form-produk',
                    popover: {
                        title: 'Buat Menu Baru',
                        description: 'Masukkan nama kue/hidangan Anda dan tentukan harga jualnya. Form ini membangun katalog kasir Anda.',
                        side: 'right',
                        align: 'start'
                    }
                });
            }
            
            steps.push({
                element: '#tour-product-stats',
                popover: {
                    title: 'Ringkasan Produk',
                    description: 'Lihat total nilai jual dari semua produk yang siap dijual di etalase Anda.',
                    side: 'bottom',
                    align: 'center'
                }
            });
            
            steps.push({
                element: '#tour-product-catalog',
                popover: {
                    title: 'Katalog Kasir',
                    description: 'Kumpulan kartu produk Anda. Admin dapat mengklik tanda edit untuk mengubah harga atau memperbarui foto produk kapan saja.',
                    side: 'top',
                    align: 'start'
                }
            });

            const tour = driver({
                showProgress: true,
                animate: true,
                nextBtnText: 'Lanjut →',
                prevBtnText: '← Kembali',
                doneBtnText: 'Selesai ✓',
                popoverClass: 'driverjs-theme-emerald',
                steps: steps
            });

            btnStartTour.addEventListener('click', () => {
                tour.drive();
            });
        }
    });
</script>
@endsection
