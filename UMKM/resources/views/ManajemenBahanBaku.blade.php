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
            <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold tracking-tight text-teal-900 break-words leading-tight">Manajemen Bahan Baku</h2>
            <p class="text-sm sm:text-base text-on-surface-variant font-body">Pantau ketersediaan stok dan biaya unit inventaris Anda secara real-time.</p>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
            <div class="flex flex-row gap-2 w-full sm:w-auto">
                <a href="{{ route('materials.export-pdf') }}" target="_blank" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-surface-container-highest text-on-surface font-semibold text-sm hover:bg-surface-container-high transition-colors flex items-center justify-center gap-2" title="Ekspor ke PDF">
                    <span class="material-symbols-outlined text-sm flex-shrink-0">picture_as_pdf</span> PDF
                </a>
                <a href="{{ route('materials.export-sheets') }}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-teal-50 text-teal-700 font-semibold text-sm hover:bg-teal-100 transition-colors flex items-center justify-center gap-2 border border-teal-200" title="Unduh Excel (XLSX)">
                    <span class="material-symbols-outlined text-sm flex-shrink-0">table</span> Spreadsheet
                </a>
            </div>
            @if(auth()->user()->isAdmin())
            <button class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#005050] text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-teal-900/30 hover:bg-[#006a6a] hover:scale-[1.02] active:scale-95 transition-all" id="open-material-form" type="button">
                <span class="material-symbols-outlined text-base flex-shrink-0">add_circle</span>
                <span>Tambah Bahan</span>
            </button>
            @endif
        </div>
    </div>

    @if (session('success'))
    <div class="rounded-xl bg-teal-50 text-teal-800 border border-teal-100 px-4 py-3 text-sm font-medium flex items-center gap-3 animate-in fade-in slide-in-from-top-4">
        <span class="material-symbols-outlined text-teal-600">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="rounded-xl bg-red-50 text-red-800 border border-red-100 px-4 py-3 text-sm font-medium space-y-1 animate-in fade-in slide-in-from-top-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="material-symbols-outlined text-red-600">error</span>
            <span class="font-bold">Terjadi kesalahan:</span>
        </div>
        @foreach ($errors->all() as $error)
        <div class="ml-7">• {{ $error }}</div>
        @endforeach
    </div>
    @endif

    <!-- Bento Grid - Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Total Kategori</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-teal-900 leading-none">{{ $totalCategories }}</span>
                <span class="text-xs font-medium text-teal-600">kategori aktif</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            @if($lowStockCount > 0)
            <div class="absolute top-0 right-0 w-2 h-2 bg-error rounded-full m-3 animate-ping"></div>
            @endif
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Stok Menipis</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-error leading-none">{{ $lowStockCount }}</span>
                <span class="text-xs font-medium text-slate-400">item butuh restock</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Valuasi Gudang</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-teal-900 leading-none">Rp {{ number_format($inventoryValue, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Stok Masuk Hari Ini</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-teal-900 leading-none">{{ number_format($stockInToday, 0, ',', '.') }}</span>
                <span class="text-xs font-medium text-slate-400">unit</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Stok Keluar Hari Ini</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-error leading-none">{{ number_format($stockOutToday, 0, ',', '.') }}</span>
                <span class="text-xs font-medium text-slate-400">unit</span>
            </div>
        </div>
    </div>

    <!-- Form Sidebar untuk Tambah/Edit Material -->
    <aside id="material-form-sidebar" class="fixed right-0 top-0 h-screen w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50 overflow-y-auto">
        <div class="sticky top-0 bg-surface-container-low px-6 py-5 border-b border-surface-container-high flex justify-between items-center z-10">
            <h3 class="text-lg font-bold text-primary flex items-center" id="sidebar-title">
                <span class="material-symbols-outlined mr-2 text-primary">inventory_2</span> Tambah Material
            </h3>
            <button id="close-material-form" class="text-slate-400 hover:text-teal-900 transition-colors" type="button">
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
                    <select class="block w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all" name="category" id="form-category" required>
                        <option value="Struktur">Struktur</option>
                        <option value="Dasar">Dasar</option>
                        <option value="Finishing">Finishing</option>
                    </select>
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
                    <button class="w-full px-6 py-4 rounded-xl shadow-lg shadow-teal-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
                            style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
                            type="submit">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span id="submit-button-text">Simpan Material Baru</span>
                    </button>
                </div>
            </form>
            @else
            <div class="py-8 text-center text-slate-500">
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
            ['name' => 'category', 'label' => 'Kategori', 'choices' => ['Struktur' => 'Struktur', 'Dasar' => 'Dasar', 'Finishing' => 'Finishing']],
        ]"
    />

    <!-- Main Data Table Container -->
    <section class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-gray-100">
        <div class="px-8 py-6 bg-surface-container-high/50 flex justify-between items-center border-b border-outline-variant/5">
            <h3 class="text-sm font-bold uppercase tracking-widest text-on-surface-variant">Daftar Inventaris Bahan</h3>
            <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-1 rounded-full border border-outline-variant/5">
                {{ $materials->total() }} item ditemukan
            </span>
        </div>
        <div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
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
                                    <span class="text-[10px] text-slate-400 uppercase tracking-tighter">{{ $material->unit }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 text-center">
                            <span class="px-2 sm:px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest border {{
                                $material->category === 'Struktur' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' :
                                ($material->category === 'Dasar' ? 'bg-teal-50 text-teal-700 border-teal-100' : 'bg-amber-50 text-amber-700 border-amber-100')
                            }}">{{ $material->category }}</span>
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
                                <span class="text-[10px] text-slate-400">Tersedia di gudang</span>
                            </div>
                        </td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 font-bold text-[11px] md:text-sm text-on-surface-variant">{{ number_format($material->minimum_stock, 0, ',', '.') }}</td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 font-bold text-[11px] md:text-sm text-teal-900">Rp {{ number_format($material->price, 0, ',', '.') }}</td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5">
                            <div class="text-[11px] md:text-sm font-semibold text-on-surface">{{ $material->default_supplier ?: '-' }}</div>
                            <div class="text-[10px] text-slate-400">Lead time: {{ $material->supplier_lead_time_days ?? '0' }} hr</div>
                        </td>
                        <td class="px-2 sm:px-8 py-3 sm:py-5 text-right relative">
                            <details class="inline-block text-left dropdown-action">
                                <summary class="list-none cursor-pointer p-1 sm:p-2 hover:bg-surface-container-highest rounded-full transition-all text-slate-400 group-hover:text-primary">
                                    <span class="material-symbols-outlined text-xl">more_vert</span>
                                </summary>
                                <div class="absolute right-0 mt-2 w-72 bg-white border border-outline-variant/10 rounded-2xl shadow-2xl p-4 space-y-4 z-40 animate-in fade-in zoom-in-95 duration-200">
                                    <div class="flex items-center justify-between border-b border-outline-variant/5 pb-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Opsi Cepat</span>
                                        @if(auth()->user()->isAdmin())
                                        <button class="text-primary text-[10px] font-black uppercase hover:underline edit-material-btn"
                                                data-id="{{ $material->id }}"
                                                data-name="{{ $material->name }}"
                                                data-category="{{ $material->category }}"
                                                data-unit="{{ $material->unit }}"
                                                data-price="{{ (int)$material->price }}"
                                                data-minimum_stock="{{ $material->minimum_stock }}"
                                                data-default_supplier="{{ $material->default_supplier }}">Edit Data</button>
                                        @endif
                                    </div>

                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('materials.stock-in', $material) }}" method="POST" class="grid grid-cols-2 gap-2">
                                        @csrf
                                        <div class="col-span-1">
                                            <input name="quantity" min="1" required class="w-full bg-slate-50 border-none rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-teal-500" type="number" placeholder="Qty In"/>
                                        </div>
                                        <button class="py-2 rounded-lg bg-teal-600 text-white text-[10px] font-bold uppercase tracking-wider" type="submit">Stok Masuk</button>
                                        <input type="hidden" name="unit_price" value="{{ $material->price }}"/>
                                    </form>

                                    <form action="{{ route('materials.stock-out', $material) }}" method="POST" class="grid grid-cols-2 gap-2">
                                        @csrf
                                        <div class="col-span-1">
                                            <input name="quantity" min="1" required class="w-full bg-slate-50 border-none rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500" type="number" placeholder="Qty Out"/>
                                        </div>
                                        <button class="py-2 rounded-lg bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider" type="submit">Pakai Stok</button>
                                    </form>

                                    <div class="pt-2 border-t border-outline-variant/5">
                                        <form action="{{ route('materials.destroy', $material) }}" method="POST" onsubmit="return confirm('Hapus material ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="w-full py-2.5 text-red-600 text-xs font-bold uppercase tracking-widest hover:bg-red-50 rounded-lg transition-colors flex items-center justify-center gap-2" type="submit">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                <span>Hapus Permanen</span>
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                    <p class="text-[10px] text-center text-slate-400 italic">Hanya Admin yang dapat mengelola stok.</p>
                                    @endif
                                </div>
                            </details>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td class="px-8 py-10 text-center text-on-surface-variant" colspan="7">
                            <span class="material-symbols-outlined text-4xl opacity-10 mb-2 block">inventory_2</span>
                            Belum ada data bahan baku.
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

        <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-gray-100">
            <div class="px-8 py-6 bg-surface-container-high/50 border-b border-outline-variant/5 flex justify-between items-center">
                <h3 class="text-sm font-bold uppercase tracking-widest text-on-surface-variant">Audit Trail: Log Pergerakan Stok</h3>
                <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-1 rounded-full border border-outline-variant/5">{{ $recentMovements->total() }} log ditemukan</span>
            </div>
            <div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
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
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-on-surface">{{ \Carbon\Carbon::parse($movement->transaction_date)->translatedFormat('d M Y') }}</span>
                                    <span class="text-[10px] text-primary font-black uppercase tracking-tighter flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[10px]">person</span>
                                        {{ $movement->user?->name ?? 'System' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-4 font-bold text-sm text-teal-900">{{ $movement->material?->name ?? '-' }}</td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest {{
                                    $movement->type === 'in' ? 'bg-teal-100 text-teal-700' :
                                    ($movement->type === 'out' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700')
                                }}">
                                    {{ $movement->type === 'in' ? 'Masuk' : ($movement->type === 'out' ? 'Keluar' : 'Adj') }}
                                </span>
                            </td>
                            <td class="px-8 py-4 text-center font-black text-sm {{ $movement->type === 'in' ? 'text-teal-600' : 'text-error' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ number_format($movement->quantity, 0, ',', '.') }}
                            </td>
                            <td class="px-8 py-4 text-[11px] font-medium text-slate-500">
                                {{ number_format($movement->stock_before, 0, ',', '.') }} <span class="mx-1">→</span> {{ number_format($movement->stock_after, 0, ',', '.') }}
                            </td>
                            <td class="px-8 py-4 text-xs text-on-surface-variant italic">
                                {{ $movement->reference ?: '-' }}
                                @if($movement->note)
                                <div class="text-[10px] text-slate-400 not-italic">{{ $movement->note }}</div>
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
                {{ $recentMovements->appends(request()->query())->links() }}
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
(() => {
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
        document.getElementById('form-category').value = data.category;
        document.getElementById('form-unit').value = data.unit;
        document.getElementById('form-price').value = data.price;
        document.getElementById('form-minimum_stock').value = data.minimum_stock;
        document.getElementById('form-supplier').value = data.default_supplier || '';
    } else {
        sidebarTitle.innerHTML = '<span class="material-symbols-outlined mr-2 text-teal-600">inventory_2</span> Tambah Material';
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

  // Edit Button Logic
  document.querySelectorAll('.edit-material-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
          e.preventDefault();
          const data = btn.dataset;
          openSidebar('edit', data);
          // Close dropdown
          btn.closest('details').removeAttribute('open');
      });
  });

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !openButton?.contains(e.target) && !e.target.closest('.edit-material-btn')) {
      closeSidebar();
    }
  });
})();
</script>
@endsection
