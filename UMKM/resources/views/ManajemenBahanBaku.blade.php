@extends('layouts.app')
@section('title', 'Bahan Baku')
@section('page_title', 'Manajemen Bahan Baku')
@section('search_placeholder', 'Cari bahan baku...')

@section('content')
<!-- Page Content -->
<div class="px-4 py-6 sm:px-8 max-w-full mx-auto space-y-8">
    <!-- Page Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div class="space-y-1 w-full">
            <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold tracking-tight text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 break-words leading-tight">Manajemen Bahan Baku</h2>
            <p class="text-sm sm:text-base text-on-surface-variant font-body">Pantau ketersediaan stok dan biaya unit inventaris Anda secara real-time.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto justify-start sm:justify-end">
            <a href="{{ route('materials.export-pdf') }}" target="_blank" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-surface-container-highest text-on-surface font-semibold text-sm hover:bg-surface-container-high transition-colors flex items-center justify-center gap-2 border border-outline-variant/10" title="Ekspor ke PDF">
                <span class="material-symbols-outlined text-sm flex-shrink-0">picture_as_pdf</span> PDF
            </a>
            <a href="{{ route('materials.export', request()->all()) }}" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-[#0b6e4f] dark:bg-emerald-600 text-white font-semibold text-sm hover:bg-[#09523b] transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20" title="Ekspor ke Excel (.xlsx)">
                <span class="material-symbols-outlined text-sm flex-shrink-0">download</span> Ekspor Excel
            </a>
            @if(auth()->user()->isAdmin())
            <button class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-[#0b6e4f] dark:bg-emerald-600 text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/30 hover:bg-[#09523b] hover:scale-[1.02] active:scale-95 transition-all" id="open-material-form" type="button">
                <span class="material-symbols-outlined text-base flex-shrink-0">add_circle</span>
                <span>Tambah Bahan</span>
            </button>
            @endif
        </div>
    </div>

    @if (session('success'))
    <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 dark:text-emerald-300 border border-emerald-100 px-4 py-3 text-sm font-medium flex items-center gap-3 animate-in fade-in slide-in-from-top-4">
        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="rounded-xl bg-red-50 dark:bg-red-950/40 text-red-800 dark:text-red-300 border border-red-100 px-4 py-3 text-sm font-medium space-y-1 animate-in fade-in slide-in-from-top-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="material-symbols-outlined text-red-600 dark:text-red-400">error</span>
            <span class="font-bold">Terjadi kesalahan:</span>
        </div>
        @foreach ($errors->all() as $error)
        <div class="ml-7">• {{ $error }}</div>
        @endforeach
    </div>
    @endif

    <!-- Bento Grid - Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-zinc-400 mb-2">Total Kategori</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 leading-none">{{ $totalCategories }}</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">kategori aktif</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            @if($lowStockCount > 0)
            <div class="absolute top-0 right-0 w-2 h-2 bg-error rounded-full m-3 animate-ping"></div>
            @endif
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-zinc-400 mb-2">Stok Menipis</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-error leading-none">{{ $lowStockCount }}</span>
                <span class="text-xs font-medium text-slate-400 dark:text-zinc-400">item butuh restock</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-zinc-400 mb-2">Valuasi Gudang</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 leading-none">Rp {{ number_format($inventoryValue, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-zinc-400 mb-2">Stok Masuk Hari Ini</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 leading-none">{{ number_format($stockInToday, 0, ',', '.') }}</span>
                <span class="text-xs font-medium text-slate-400 dark:text-zinc-400">unit</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-zinc-400 mb-2">Stok Keluar Hari Ini</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-error leading-none">{{ number_format($stockOutToday, 0, ',', '.') }}</span>
                <span class="text-xs font-medium text-slate-400 dark:text-zinc-400">unit</span>
            </div>
        </div>
    </div>

    <!-- Form Sidebar untuk Tambah/Edit Material -->
    <aside id="material-form-sidebar" class="fixed right-0 top-0 h-screen w-96 bg-white dark:bg-zinc-900 shadow-2xl transform translate-x-full transition-transform duration-300 z-50 overflow-y-auto">
        <div class="sticky top-0 bg-surface-container-low px-6 py-5 border-b border-surface-container-high flex justify-between items-center z-10">
            <h3 class="text-lg font-bold text-primary flex items-center" id="sidebar-title">
                <span class="material-symbols-outlined mr-2 text-primary">inventory_2</span> Tambah Material
            </h3>
            <button id="close-material-form" class="text-slate-400 dark:text-white hover:text-emerald-900 dark:hover:text-emerald-300 dark:hover:text-zinc-400 transition-colors" type="button">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
        <div class="p-6">
            @if(auth()->user()->isAdmin())
            <form id="material-form" action="{{ route('materials.store') }}" method="POST" class="space-y-5">
                @csrf
                <div id="method-container"></div>
                <div class="space-y-2 w-full">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider w-full">Nama Material</label>
                    <input class="block w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="name" id="form-name" placeholder="Contoh: Beras Premium" required type="text"/>
                </div>
                <div class="space-y-2 w-full">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider w-full">Kategori</label>
                    <div class="flex gap-2">
                        <select class="block flex-1 bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700 dark:text-zinc-50 dark:text-zinc-200" name="raw_material_category_id" id="form-category" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            @foreach($rawMaterialCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="openAddCategoryModal()" class="px-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 hover:bg-emerald-100 rounded-lg flex items-center justify-center transition-all" title="Tambah Kategori Baru">
                            <span class="material-symbols-outlined text-base">add</span>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                    <div class="space-y-2 w-full">
                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider w-full">Unit (Stok)</label>
                        <input class="block w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="unit" id="form-unit" placeholder="KG/PCS" required type="text"/>
                    </div>
                    <div class="space-y-2 w-full">
                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider w-full">Harga Unit (Rp)</label>
                        <input class="block w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="price" id="form-price" min="0" placeholder="0" required type="number"/>
                    </div>
                </div>
                <div id="initial-stock-section" class="space-y-2 w-full">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider w-full">Stok Awal</label>
                    <input class="block w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="stock" id="form-stock" min="0" placeholder="0" required type="number"/>
                </div>
                                <div class="space-y-2 w-full">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider w-full">Minimum Stok (Alert)</label>
                    <input class="block w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="minimum_stock" id="form-minimum_stock" min="0" placeholder="0" required type="number"/>
                </div>
                <div class="space-y-2 w-full">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider w-full">Supplier Utama (Opsional)</label>
                    <input class="block w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="default_supplier" id="form-supplier" placeholder="Contoh: PT Bogasari" type="text"/>
                </div>

                <div class="pt-4">
                    <button class="w-full px-6 py-4 rounded-xl shadow-lg shadow-emerald-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
                            style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;" 
                            type="submit">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span id="submit-button-text">Simpan Material Baru</span>
                    </button>
                </div>
            </form>
            @else
            <div class="py-8 text-center text-slate-500 dark:text-zinc-400">
                <span class="material-symbols-outlined text-4xl mb-2 opacity-20">lock</span>
                <p class="text-sm font-semibold">Hanya admin yang dapat menambah/mengubah material.</p>
            </div>
            @endif
        </div>
    </aside>

    {{-- Search, Sort & Filter Controls --}}
    <x-table-controls
        :action="route('materials.index')"
        searchPlaceholder="Cari nama bahan, kategori, supplier..."
        :showDates="false"
        :sortOptions="[
            ['value' => 'name_asc', 'label' => 'Nama (A-Z)'],
            ['value' => 'name_desc', 'label' => 'Nama (Z-A)'],
            ['value' => 'stock_asc', 'label' => 'Stok Terendah'],
            ['value' => 'stock_desc', 'label' => 'Stok Tertinggi'],
            ['value' => 'price_asc', 'label' => 'Harga Terendah'],
            ['value' => 'price_desc', 'label' => 'Harga Tertinggi'],
            ['value' => 'created_at_desc', 'label' => 'Terbaru'],
            ['value' => 'created_at_asc', 'label' => 'Terlama'],
        ]"
        :filterOptions="[
            ['name' => 'raw_material_category_id', 'label' => 'Kategori', 'choices' => $categoryChoices],
        ]"
    />

    <!-- Main Data Table Container -->
    <section class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-zinc-800/50">
        <div class="px-8 py-6 bg-surface-container-high/50 flex justify-between items-center border-b border-outline-variant/5">
            <h3 class="text-sm font-bold uppercase tracking-widest text-on-surface-variant">Daftar Inventaris Bahan</h3>
            <span class="text-[10px] font-bold text-slate-400 dark:text-zinc-400 bg-white dark:bg-zinc-900 px-3 py-1 rounded-full border border-outline-variant/5">
                {{ $materials->total() }} item ditemukan
            </span>
        </div>
        <div class="w-full overflow-x-auto border border-gray-100 dark:border-zinc-800/50 rounded-lg mb-4 pb-24" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
            <table class="min-w-[800px] w-full text-xs text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant">
                        <th class="px-2 sm:px-8 py-3 sm:py-4 text-[11px] md:text-xs font-bold uppercase tracking-widest">Nama Material</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4 text-[11px] md:text-xs font-bold uppercase tracking-widest text-center">Kategori</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4 text-[11px] md:text-xs font-bold uppercase tracking-widest">Stok Saat Ini</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4 text-[11px] md:text-xs font-bold uppercase tracking-widest">Min. Stok</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4 text-[11px] md:text-xs font-bold uppercase tracking-widest">Harga Satuan</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4 text-[11px] md:text-xs font-bold uppercase tracking-widest">Supplier Utama</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4 text-[11px] md:text-xs font-bold uppercase tracking-widest text-right">Kelola</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-sm text-on-surface divide-y divide-surface-container-low">
                    @forelse ($materials as $material)
                    <tr class="hover:bg-primary-fixed/5 transition-colors group">
                        <td class="px-2 sm:px-8 py-3 sm:py-5">
                            <div class="flex items-center gap-2 sm:gap-4">
                                <div class="w-8 sm:w-10 h-8 sm:h-10 flex-shrink-0 rounded-xl bg-surface-container-highest flex items-center justify-center text-primary border border-primary/5">
                                    <span class="material-symbols-outlined flex-shrink-0 text-sm sm:text-base">inventory_2</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold text-on-surface text-[11px] md:text-sm">{{ $material->name }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-zinc-400 uppercase tracking-tighter">{{ $material->unit }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 text-center">
                            @php
                                $catName = $material->rawMaterialCategory?->name ?? $material->category;
                            @endphp
                            <span class="px-2 sm:px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest border {{ $catName === 'Struktur' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : ($catName === 'Dasar' ? 'bg-emerald-50 text-emerald-700 dark:text-emerald-400 border-emerald-100' : ($catName === 'Finishing' ? 'bg-amber-50 text-amber-700 dark:text-amber-400 border-amber-100' : 'bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 text-slate-700 dark:text-zinc-50 dark:text-zinc-200 border-slate-100 dark:border-zinc-800/60')) }}">{{ $catName }}</span>
                        </td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-[11px] md:text-sm {{ $material->stock <= $material->minimum_stock ? 'text-error animate-pulse' : 'text-on-surface' }}">
                                        <span data-material-stock="{{ $material->id }}" data-stock-value="{{ (float) ($material->stock ?? 0) }}">{{ number_format($material->stock, 2, ',', '.') }}</span>
                                    </span>
                                    @if($material->stock <= $material->minimum_stock)
                                    <span class="material-symbols-outlined text-error text-sm flex-shrink-0" title="Stok kritis!">warning</span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400 dark:text-zinc-400">Tersedia di gudang</span>
                            </div>
                        </td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 font-bold text-[11px] md:text-sm text-on-surface-variant">{{ number_format($material->minimum_stock, 0, ',', '.') }}</td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 font-bold text-[11px] md:text-sm text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($material->price, 0, ',', '.') }}</td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5">
                            <div class="text-[11px] md:text-sm font-semibold text-on-surface">{{ $material->default_supplier ?: '-' }}</div>
                            <div class="text-[10px] text-slate-400 dark:text-zinc-400">Lead time: {{ $material->supplier_lead_time_days ?? '0' }} hr</div>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 text-right">
                            <button class="p-2 hover:bg-surface-container-highest rounded-full transition-all text-slate-400 dark:text-zinc-400 hover:text-primary open-quick-action" 
                                    data-id="{{ $material->id }}"
                                    data-name="{{ $material->name }}"
                                    data-category_id="{{ $material->raw_material_category_id }}"
                                    data-category="{{ $material->rawMaterialCategory?->name ?? $material->category }}"
                                    data-unit="{{ $material->unit }}"
                                    data-price="{{ (int)$material->price }}"
                                    data-minimum_stock="{{ $material->minimum_stock }}"
                                    data-default_supplier="{{ $material->default_supplier }}"
                                    title="Klik untuk opsi cepat">
                                <span class="material-symbols-outlined text-xl">more_vert</span>
                            </button>
                        </td>
                    </tr>
                     @empty
                     <tr>
                         <td class="px-8 py-16 text-center" colspan="7">
                             <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                                 <span class="material-symbols-outlined text-4xl text-stone-400 dark:text-zinc-500 font-light">inventory_2</span>
                                 <p class="font-bold text-stone-700 dark:text-zinc-200">Katalog bahan baku kosong</p>
                                 <p class="text-xs text-stone-400 dark:text-zinc-500">Klik tombol tambah di atas untuk memasukkan data baru.</p>
                             </div>
                         </td>
                     </tr>
                     @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="px-8 py-4 bg-surface-container-low border-t border-outline-variant/5">
            {{ $materials->appends(request()->query())->links() }}
        </div>
    </section>

    <!-- Stock History Card -->
    <section class="space-y-4">
        <x-table-controls
            :action="route('materials.index')"
            prefix="h"
            searchPlaceholder="Cari material, referensi, catatan..."
            :sortOptions="[
                ['value' => 'transaction_date_desc', 'label' => 'Tanggal Terbaru'],
                ['value' => 'transaction_date_asc', 'label' => 'Tanggal Terlama'],
                ['value' => 'quantity_desc', 'label' => 'Jumlah Terbesar'],
                ['value' => 'quantity_asc', 'label' => 'Jumlah Terkecil'],
            ]"
            :filterOptions="[
                ['name' => 'type', 'label' => 'Tipe Aktivitas', 'choices' => ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian']],
            ]"
        />

        <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-zinc-800/50">
            <div class="px-8 py-6 bg-surface-container-high/50 border-b border-outline-variant/5 flex justify-between items-center">
                <h3 class="text-sm font-bold uppercase tracking-widest text-on-surface-variant">Audit Trail: Log Pergerakan Stok</h3>
                <span class="text-[10px] font-bold text-slate-400 dark:text-zinc-400 bg-white dark:bg-zinc-900 px-3 py-1 rounded-full border border-outline-variant/5">{{ $recentMovements->total() }} log ditemukan</span>
            </div>
            <div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 dark:border-zinc-800/50 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
                <table class="min-w-[800px] w-full text-xs text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant">
                            <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Waktu & User</th>
                            <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Material</th>
                            <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Aktivitas</th>
                            <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest text-center">Jumlah</th>
                            <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Mutasi Saldo</th>
                            <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Ref / Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse ($recentMovements as $movement)
                        <tr class="hover:bg-slate-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 transition-colors">
                            <td class="px-8 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-on-surface">{{ \Carbon\Carbon::parse($movement->transaction_date)->translatedFormat('d M Y') }}</span>
                                    <span class="text-[10px] text-primary font-black uppercase tracking-tighter flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[10px]">person</span>
                                        {{ $movement->user?->name ?? 'System' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-4 font-bold text-sm text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ $movement->material?->name ?? '-' }}</td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest {{ $movement->type === 'in' ? 'bg-emerald-100 text-emerald-700 dark:text-emerald-400' : ($movement->type === 'out' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700 dark:text-zinc-200') }}">
                                    {{ $movement->type === 'in' ? 'Masuk' : ($movement->type === 'out' ? 'Keluar' : 'Adj') }}
                                </span>
                            </td>
                            <td class="px-8 py-4 text-center font-black text-sm {{ $movement->type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-error' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ number_format($movement->quantity, 0, ',', '.') }}
                            </td>
                            <td class="px-8 py-4 text-[11px] font-medium text-slate-500 dark:text-zinc-400">
                                {{ number_format($movement->stock_before, 0, ',', '.') }} <span class="mx-1">→</span> {{ number_format($movement->stock_after, 0, ',', '.') }}
                            </td>
                            <td class="px-8 py-4 text-xs text-on-surface-variant italic">
                                {{ $movement->reference ?: '-' }}
                                @if($movement->note)
                                <div class="text-[10px] text-slate-400 dark:text-zinc-400 not-italic">{{ $movement->note }}</div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="px-8 py-8 text-sm text-on-surface-variant text-center" colspan="6">Belum ada riwayat pergerakan stok.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
            <div class="px-8 py-4 bg-surface-container-low border-t border-outline-variant/5">
                {{ $recentMovements->appends(request()->query())->links('pagination::tailwind', ['pageName' => 'h_page']) }}
            </div>
        </div>
    </section>

    <!-- Modal Opsi Cepat (Quick Action Modal) -->
    <div id="quick-action-modal" class="fixed inset-0 bg-slate-900/60 dark:bg-zinc-900/60 backdrop-blur-sm z-[60] flex items-center justify-center hidden">
        <div class="bg-white dark:bg-zinc-900 rounded-[2rem] w-full max-w-md shadow-2xl p-8 transform scale-95 opacity-0 transition-all duration-300 modal-content relative">
            <button class="absolute top-6 right-6 text-slate-400 dark:text-zinc-400 hover:text-error transition-colors" onclick="closeQuickAction()">
                <span class="material-symbols-outlined">close</span>
            </button>

            <div class="mb-8">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400 mb-1 block">Opsi Cepat Material</span>
                <h3 class="text-xl font-black text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 leading-tight" id="modal-material-name">-</h3>
                <p class="text-xs text-slate-400 dark:text-zinc-400 mt-1" id="modal-material-unit">-</p>
            </div>

            <div class="space-y-6">
                @if(auth()->user()->isAdmin())
                <!-- Edit Action -->
                <button class="w-full group flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 hover:bg-primary hover:text-white transition-all text-left" onclick="handleQuickEdit()">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-white dark:bg-zinc-900 rounded-xl text-primary group-hover:bg-primary-container">
                            <span class="material-symbols-outlined">edit_note</span>
                        </div>
                        <div>
                            <span class="font-bold text-sm block">Ubah Detail</span>
                            <span class="text-[10px] opacity-60">Ganti nama, kategori, atau harga</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-sm opacity-20 group-hover:opacity-100">chevron_right</span>
                </button>

                <!-- Stock In Form -->
                <div class="p-5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/50 border border-emerald-100 space-y-3">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">add_circle</span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Stok Masuk (Supply)</span>
                    </div>
                    <form id="modal-stock-in-form" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <div class="space-y-1">
                                <label class="block text-[9px] font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Jumlah Stok Masuk</label>
                                <input name="quantity" min="1" required class="w-full bg-white dark:bg-zinc-900 border-2 border-slate-100/80 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-all" type="number" placeholder="Jumlah..."/>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[9px] font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Total Belanja (Rp)</label>
                                <input name="total_spent" min="0" value="0" class="w-full bg-white dark:bg-zinc-900 border-2 border-slate-100/80 dark:border-zinc-800/80 rounded-xl px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-all" type="number" placeholder="Default: 0"/>
                            </div>
                        </div>
                        <input type="hidden" name="unit_price" id="modal-stock-in-price"/>
                        <button class="w-full py-2.5 rounded-xl bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] text-white text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 shadow-md shadow-emerald-900/10" type="submit">
                            <span class="material-symbols-outlined text-sm">save</span>
                            Simpan Stok & Belanja
                        </button>
                    </form>
                </div>

                <!-- Stock Out Form -->
                <div class="p-5 rounded-2xl bg-amber-50/50 dark:bg-amber-950/50 border border-amber-100 space-y-3">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-sm">remove_circle</span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">Pakai Stok (Usage)</span>
                    </div>
                    <form id="modal-stock-out-form" method="POST" class="flex gap-2">
                        @csrf
                        <input name="quantity" min="1" required class="flex-1 bg-white dark:bg-zinc-900 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-amber-500" type="number" placeholder="Jumlah..."/>
                        <button class="px-6 py-3 rounded-xl bg-amber-500 text-white text-xs font-black uppercase tracking-wider hover:bg-amber-600 transition-all" type="submit">Update</button>
                    </form>
                </div>

                <!-- Delete Action -->
                <form id="modal-delete-form" method="POST" class="pt-4" onsubmit="return confirm('Hapus material ini secara permanen?')">
                    @csrf
                    @method('DELETE')
                    <button class="w-full py-4 text-red-600 dark:text-red-400 text-xs font-black uppercase tracking-[0.2em] hover:bg-red-50 dark:hover:bg-red-950/40 rounded-2xl transition-colors flex items-center justify-center gap-2" type="submit">
                        <span class="material-symbols-outlined text-sm">delete</span>
                        <span>Hapus Permanen</span>
                    </button>
                </form>
                @else
                <div class="py-12 text-center text-slate-400 dark:text-zinc-400">
                    <span class="material-symbols-outlined text-4xl mb-2 opacity-20">lock</span>
                    <p class="text-sm italic">Hanya Admin yang dapat mengelola stok.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ADD RAW MATERIAL CATEGORY MODAL -->
    <div id="add-category-modal" class="fixed inset-0 bg-slate-900/60 dark:bg-zinc-900/60 backdrop-blur-sm z-[70] flex items-center justify-center hidden animate-fade-in">
        <div class="bg-white dark:bg-zinc-900 rounded-[2rem] w-full max-w-md shadow-2xl p-8 transform scale-95 opacity-0 transition-all duration-300 modal-content relative">
            <button class="absolute top-6 right-6 text-slate-400 dark:text-zinc-400 hover:text-error transition-colors" type="button" onclick="closeAddCategoryModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
            
            <div class="mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">category</span>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400 block">Kategori Baru</span>
                    <h3 class="text-lg font-black text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 leading-tight">Tambah Kategori Bahan</h3>
                </div>
            </div>

            <div class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Nama Kategori</label>
                    <input type="text" id="new-category-name" placeholder="Contoh: Tepung & Pati" class="block w-full bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl p-3 text-sm font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"/>
                </div>
                
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" onclick="closeAddCategoryModal()" class="px-5 py-2.5 bg-white dark:bg-zinc-900 text-slate-600 dark:text-white font-bold text-xs rounded-xl shadow-sm border border-slate-200 dark:border-zinc-800 hover:shadow-md transition-all">
                        Batal
                    </button>
                    <button type="button" onclick="submitNewCategory()" class="px-5 py-2.5 bg-[#0b6e4f] dark:bg-emerald-600 text-white font-black text-xs rounded-xl shadow-md hover:bg-[#09523b] transition-all">
                        Simpan Kategori
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Prevent table container from clipping while maintaining horizontal scroll */
    .overflow-x-auto {
        overflow-y: visible !important;
    }
    
    .modal-content.active {
        transform: scale(1) !important;
        opacity: 1 !important;
    }
</style>
@endsection

@section('scripts')
<script>
(() => {
  const modal = document.getElementById('quick-action-modal');
  const modalContent = modal.querySelector('.modal-content');
  const modalName = document.getElementById('modal-material-name');
  const modalUnit = document.getElementById('modal-material-unit');
  const stockInForm = document.getElementById('modal-stock-in-form');
  const stockInPrice = document.getElementById('modal-stock-in-price');
  const stockOutForm = document.getElementById('modal-stock-out-form');
  const deleteForm = document.getElementById('modal-delete-form');
  
  let currentMaterialData = null;

  window.closeQuickAction = () => {
    modalContent.classList.remove('active');
    setTimeout(() => modal.classList.add('hidden'), 300);
  };

  window.handleQuickEdit = () => {
    closeQuickAction();
    setTimeout(() => {
        openSidebar('edit', currentMaterialData);
    }, 350);
  };

  document.querySelectorAll('.open-quick-action').forEach(btn => {
    btn.addEventListener('click', () => {
      const data = btn.dataset;
      currentMaterialData = data;
      
      modalName.innerText = data.name;
      modalUnit.innerText = `Kategori: ${data.category} | Satuan: ${data.unit}`;
      
      if (stockInForm) stockInForm.action = `/bahan-baku/${data.id}/stok-masuk`;
      if (stockInPrice) stockInPrice.value = data.price;
      if (stockOutForm) stockOutForm.action = `/bahan-baku/${data.id}/stok-keluar`;
      if (deleteForm) deleteForm.action = `/bahan-baku/${data.id}`;
      
      modal.classList.remove('hidden');
      setTimeout(() => modalContent.classList.add('active'), 10);
    });
  });

  // Close on backdrop click
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeQuickAction();
  });

  const sidebar = document.getElementById('material-form-sidebar');
  const openButton = document.getElementById('open-material-form');
  const closeButton = document.getElementById('close-material-form');
  const materialForm = document.getElementById('material-form');
  const sidebarTitle = document.getElementById('sidebar-title');
  const submitButtonText = document.getElementById('submit-button-text');
  const methodContainer = document.getElementById('method-container');
  const initialStockSection = document.getElementById('initial-stock-section');

  const openSidebar = (mode = 'add', data = {}) => {
    if (mode === 'edit') {
        sidebarTitle.innerHTML = '<span class="material-symbols-outlined mr-2 text-primary">edit_note</span> Edit Material';
        submitButtonText.innerText = 'Simpan Perubahan';
        materialForm.action = `/bahan-baku/${data.id}`;
        methodContainer.innerHTML = '@method("PUT")';
        initialStockSection.classList.add('hidden');
        document.getElementById('form-stock').required = false;

        // Fill data
        document.getElementById('form-name').value = data.name;
        document.getElementById('form-category').value = data.category_id || '';
        document.getElementById('form-unit').value = data.unit;
        document.getElementById('form-price').value = data.price;
        document.getElementById('form-minimum_stock').value = data.minimum_stock;
        document.getElementById('form-supplier').value = data.default_supplier || '';
    } else {
        sidebarTitle.innerHTML = '<span class="material-symbols-outlined mr-2 text-emerald-600 dark:text-emerald-400">inventory_2</span> Tambah Material';
        submitButtonText.innerText = 'Simpan Material Baru';
        materialForm.action = "{{ route('materials.store') }}";
        methodContainer.innerHTML = '';
        initialStockSection.classList.remove('hidden');
        document.getElementById('form-stock').required = true;
        materialForm.reset();
    }
    sidebar.classList.remove('translate-x-full');
  };

  const closeSidebar = () => {
    sidebar.classList.add('translate-x-full');
  };

  if (openButton) openButton.addEventListener('click', () => openSidebar('add'));
  if (closeButton) closeButton.addEventListener('click', closeSidebar);

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (sidebar && !sidebar.contains(e.target) && !openButton?.contains(e.target) && !e.target.closest('.open-quick-action') && !e.target.closest('#add-category-modal')) {
      closeSidebar();
    }
  });

  // Dynamic Categories Addition Script
  window.openAddCategoryModal = () => {
    const modal = document.getElementById('add-category-modal');
    const modalContent = modal.querySelector('.modal-content');
    modal.classList.remove('hidden');
    setTimeout(() => modalContent.classList.add('active'), 10);
  };

  window.closeAddCategoryModal = () => {
    const modal = document.getElementById('add-category-modal');
    const modalContent = modal.querySelector('.modal-content');
    modalContent.classList.remove('active');
    setTimeout(() => modal.classList.add('hidden'), 300);
  };

  window.submitNewCategory = () => {
    const nameInput = document.getElementById('new-category-name');
    const name = nameInput.value.trim();
    if (!name) {
        alert('Nama kategori tidak boleh kosong.');
        return;
    }

    fetch('{{ route("materials.categories.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ name: name })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Append to selection dropdown
            const select = document.getElementById('form-category');
            
            // Check if already exists in options
            let exists = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == data.category.id) {
                    exists = true;
                    break;
                }
            }
            
            if (!exists) {
                const opt = document.createElement('option');
                opt.value = data.category.id;
                opt.innerHTML = data.category.name;
                select.appendChild(opt);
            }
            
            // Auto select
            select.value = data.category.id;
            
            // Clean up and close modal
            nameInput.value = '';
            closeAddCategoryModal();
            
            // Show alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'fixed bottom-5 right-5 z-[100] p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-xl';
            alertDiv.innerHTML = '<span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span><span class="font-bold text-sm">Kategori ' + data.category.name + ' berhasil ditambahkan!</span>';
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
        } else {
            alert('Gagal menambahkan kategori.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
    });
  };
})();
</script>
@endsection
