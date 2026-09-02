@extends('layouts.app')
@section('title', 'Produksi')
@section('page_title', 'Input Produksi')
@section('search_placeholder', 'Cari batch...')

@section('content')
<!-- Canvas -->
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-10">
<!-- Page Header -->
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="w-full">
        <h2 class="text-3xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 tracking-tight break-words">Input Produksi</h2>
        <p class="text-on-surface-variant font-body mt-1 text-sm md:text-base">Catat batch produksi baru dan pantau penggunaan bahan baku secara real-time untuk akurasi HPP.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto justify-start sm:justify-end">
        <!-- Guided Tour Button -->
        <button type="button" id="btn-start-tour"
                class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 font-bold text-xs flex items-center justify-center gap-2 border border-emerald-200/50 shadow-sm transition-all">
            <span class="material-symbols-outlined text-[16px]">lightbulb</span>
            Panduan Produksi
        </button>

        <a href="{{ route('productions.export-pdf') }}" target="_blank" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-surface-container-highest text-on-surface font-semibold text-sm hover:bg-surface-container-high transition-colors flex items-center justify-center gap-2 border border-outline-variant/10" title="Ekspor ke PDF">
            <span class="material-symbols-outlined text-sm flex-shrink-0">picture_as_pdf</span> PDF
        </a>
        <a href="{{ route('productions.export', request()->all()) }}" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-[#0b6e4f] dark:bg-emerald-600 text-white font-semibold text-sm hover:bg-[#09523b] transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20" title="Ekspor ke Excel (.xlsx)">
            <span class="material-symbols-outlined text-sm flex-shrink-0">download</span> Ekspor Excel
        </a>
        @if(auth()->user()->isAdmin())
            <a class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-[#0b6e4f] dark:bg-emerald-600 text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/30 hover:bg-[#09523b] hover:scale-[1.02] active:scale-95 transition-all" 
               href="#production-form">
                <span class="material-symbols-outlined text-base flex-shrink-0">add_circle</span>
                <span>Batch Baru</span>
            </a>
        @else
            <button class="flex-1 sm:flex-none px-5 py-2.5 bg-slate-200 dark:bg-zinc-800 text-slate-400 dark:text-white text-sm font-semibold rounded-xl cursor-not-allowed flex items-center justify-center gap-2" type="button" disabled title="Hanya admin yang dapat membuat batch produksi">
                <span class="material-symbols-outlined text-base flex-shrink-0">lock</span>
                <span>Batch Baru</span>
            </button>
        @endif
    </div>
</div>
@if (session('success'))
<div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 dark:text-emerald-300 border border-emerald-100 px-4 py-3 text-sm font-medium">
{{ session('success') }}
</div>
@endif
@if ($errors->any())
<div class="rounded-xl bg-red-50 dark:bg-red-950/40 text-red-800 dark:text-red-300 border border-red-100 px-4 py-3 text-sm font-medium space-y-1">
@foreach ($errors->all() as $error)
<div>{{ $error }}</div>
@endforeach
</div>
@endif
<div id="tour-production-stats" class="grid grid-cols-1 md:grid-cols-4 gap-4">
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Batch Hari Ini</p>
<h3 class="mt-2 text-3xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ $batchesToday }}</h3>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Selesai Hari Ini</p>
<h3 class="mt-2 text-3xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ $doneBatchesToday }}</h3>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Yield Rata-rata</p>
<h3 class="mt-2 text-3xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ number_format($avgYieldToday, 1, ',', '.') }}%</h3>
</article>
<article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
<p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Rata-rata HPP</p>
<h3 class="mt-2 text-3xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($avgHpp, 0, ',', '.') }}</h3>
</article>
</div>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" x-data="productionForm()" x-init="init()">
@if(auth()->user()->isAdmin())
<!-- Production Form Card -->
<section class="lg:col-span-8 bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden" id="production-form">
<form action="{{ route('productions.store') }}" method="POST" @submit="submitForm($event)">
@csrf
<div class="px-8 py-6 bg-surface-container-low">
<h3 class="text-lg font-bold text-primary flex items-center">
<span class="material-symbols-outlined mr-2 text-primary">precision_manufacturing</span> Mulai Batch Produksi Baru
                        </h3>
</div>
<div class="p-8 space-y-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tanggal Produksi</label>
<input name="production_date" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" type="date" value="{{ now()->format('Y-m-d') }}"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nama Produk Jadi</label>
<select name="product_id" x-model="productId" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250">
<option value="">Pilih produk</option>
@forelse ($products as $product)
<option value="{{ $product->id }}">{{ $product->name }}</option>
@empty
<option>Tidak ada produk</option>
@endforelse
</select>
@if ($products->isEmpty())
<a class="text-xs text-primary font-semibold hover:underline" href="{{ route('products.index') }}">Tambah produk dulu</a>
@endif
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Target Kuantitas (Unit)</label>
<input name="quantity" x-model.number="quantity" @input="updateQuantityMultiplier()" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" placeholder="0" type="number" min="1"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Penanggung Jawab</label>
<input name="supervisor_name" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" placeholder="Nama Supervisor" type="text" value="{{ old('supervisor_name') }}"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Produk Reject (Unit)</label>
<input name="reject_quantity" x-model.number="rejectQuantity" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" placeholder="0" type="number" min="0"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Status Batch</label>
<select name="status" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250">
<option value="process" {{ old('status') === 'process' ? 'selected' : '' }}>Dalam Proses</option>
<option value="done" {{ old('status') === 'done' ? 'selected' : '' }}>Selesai</option>
<option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
</select>
</div>
</div>

<!-- Raw Materials List -->
<div class="space-y-4">
<div class="flex justify-between items-center">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Daftar Bahan Baku Digunakan</label>
<button class="px-4 py-2 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded-lg flex items-center border border-emerald-200/50 dark:border-emerald-800/40 shadow-sm transition-all"
        type="button"
        @click="addExtraIngredient()">
    <span class="material-symbols-outlined text-sm mr-1 text-emerald-700 dark:text-emerald-400">add_circle</span>
    <span>+ Tambah Bahan Ekstra</span>
</button>
</div>

<div class="space-y-3">
    <!-- Loading indicator -->
    <div x-show="isLoading" class="text-center py-4 text-emerald-600 dark:text-emerald-400 text-sm font-semibold flex items-center justify-center gap-2">
        <svg class="animate-spin h-5 w-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Memuat resep standar...</span>
    </div>

    <!-- Empty state -->
    <div x-show="!isLoading && ingredients.length === 0" class="text-center py-6 text-on-surface-variant/60 dark:text-zinc-500 text-sm border-2 border-dashed border-outline-variant/30 rounded-lg">
        Pilih produk untuk memuat daftar bahan baku standar atau tambahkan bahan ekstra.
    </div>

    <!-- Ingredient Rows -->
    <template x-for="(ing, index) in ingredients" :key="index">
        <div class="flex flex-col md:flex-row md:items-center gap-4 bg-surface-container-low p-4 rounded-xl border border-outline-variant/10 hover:border-emerald-100/50 dark:hover:border-emerald-950/50 transition-all duration-300 relative group">
            
            <!-- Ingredient Selector (Read-only for standard recipes, Dropdown for extra ingredients) -->
            <div class="flex-1 min-w-0">
                <!-- Standard Ingredient Name -->
                <template x-if="!ing.is_extra">
                    <div>
                        <input type="hidden" :name="'materials[' + index + '][material_id]'" :value="ing.material_id">
                        <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" x-text="ing.name"></p>
                        <span class="text-[9px] font-extrabold px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded border border-emerald-100/30">Resep Standar</span>
                    </div>
                </template>

                <!-- Extra Ingredient Dropdown Selector -->
                <template x-if="ing.is_extra">
                    <div class="space-y-1">
                        <label class="text-[9px] font-extrabold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Bahan Ekstra</label>
                        <select :name="'materials[' + index + '][material_id]'"
                                x-model="ing.material_id"
                                @change="onExtraMaterialChange(index)"
                                class="w-full bg-surface-container-highest border-none rounded-lg p-2.5 text-xs font-semibold focus:ring-2 focus:ring-primary/20 text-emerald-900 dark:text-emerald-300 dark:text-emerald-250">
                            <option value="">Pilih bahan baku...</option>
                            @foreach ($materials as $mat)
                                <option value="{{ $mat->id }}" :disabled="ingredients.some(i => i.material_id == {{ $mat->id }} && i !== ing)">
                                    {{ $mat->name }} ({{ $mat->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </template>
            </div>

            <!-- Stock & Cost Details -->
            <div class="flex flex-wrap items-center gap-4">
                <!-- Available Stock -->
                <div class="text-left w-24">
                    <span class="block text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase">Stok Gudang</span>
                    <span class="text-xs font-semibold" :class="ing.stock < ing.quantity ? 'text-red-500 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400'" x-text="new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(ing.stock) + ' ' + ing.unit"></span>
                </div>

                <!-- Input Quantity -->
                <div class="w-32 flex items-center space-x-2">
                    <div class="relative w-full">
                        <input :name="'materials[' + index + '][quantity]'"
                               type="number"
                               step="any"
                               min="0.0001"
                               x-model.number="ing.quantity"
                               placeholder="Qty"
                               class="w-full bg-surface-container-highest dark:bg-zinc-900 border-none rounded-lg p-2 text-xs font-semibold focus:ring-2 focus:ring-primary/20 text-emerald-900 dark:text-emerald-300 text-center"/>
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[9px] font-extrabold text-slate-400 dark:text-zinc-500 uppercase" x-text="ing.unit"></span>
                    </div>
                </div>

                <!-- Price/Unit -->
                <div class="text-left w-28">
                    <span class="block text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase">Harga/Unit</span>
                    <span class="text-xs font-semibold text-slate-600 dark:text-zinc-300" x-text="formatRupiah(ing.price)"></span>
                </div>

                <!-- Line Item HPP (Subtotal Cost) -->
                <div class="text-right w-28">
                    <span class="block text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase">Subtotal HPP</span>
                    <span class="text-xs font-black text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" x-text="formatRupiah((ing.quantity || 0) * ing.price)"></span>
                </div>

                <!-- Delete Button -->
                <button type="button" @click="removeIngredient(index)" class="text-slate-350 hover:text-red-500 dark:hover:text-red-400 transition-colors p-1.5 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-lg">
                    <span class="material-symbols-outlined text-lg">delete</span>
                </button>
            </div>
        </div>
    </template>
</div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Biaya Tenaga Kerja (Rp)</label>
<input name="labor_cost" x-model.number="laborCost" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" placeholder="0" type="number" min="0" step="0.01"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Biaya Overhead Batch (Rp)</label>
<input name="overhead_cost_snapshot" x-model.number="overheadCost" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" placeholder="0" type="number" min="0" step="0.01"/>
</div>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Catatan Batch</label>
<textarea name="notes" class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all text-emerald-900 dark:text-emerald-300 dark:text-emerald-250" rows="3" placeholder="Contoh: perubahan formula, kendala mesin, dll.">{{ old('notes') }}</textarea>
</div>
<div class="pt-4 flex justify-end space-x-4">
<button class="text-on-surface-variant text-sm font-bold px-6 py-2 hover:bg-slate-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 rounded-lg transition-colors" type="button" onclick="window.history.back()">Batal</button>
<button class="px-10 py-3 rounded-xl shadow-lg shadow-emerald-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2" 
        style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;" 
        type="submit">
    <span class="material-symbols-outlined text-base">save</span>
    <span>Simpan Batch Produksi</span>
</button>
</div>
</div>
</form>
</section>
@else
<!-- Staff View - Production List Only -->
<section class="lg:col-span-8 space-y-6">
<div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 rounded-xl p-6 flex items-start gap-4">
<div class="p-3 bg-blue-100 dark:bg-blue-950/50 rounded-lg text-blue-600 dark:text-blue-400 dark:text-blue-450">
<span class="material-symbols-outlined text-xl">info</span>
</div>
<div>
<p class="font-semibold text-blue-900">Akses View-Only</p>
<p class="text-sm text-blue-800 dark:text-blue-300 mt-1">Sebagai staff, Anda dapat melihat status produksi namun hanya admin yang dapat membuat batch produksi baru.</p>
</div>
</div>
</section>
@endif

<!-- Status & Insights Sidebar -->
<aside class="lg:col-span-4 space-y-6">
<!-- Estimasi HPP Real-Time (Alpine.js) -->
<div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-gray-100 dark:border-zinc-800/50 space-y-4 hover:shadow-md transition-all duration-300" x-show="productId">
    <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-250 border-b border-slate-100 dark:border-zinc-800/80 pb-3 flex items-center">
        <span class="material-symbols-outlined mr-1.5 text-emerald-600 dark:text-emerald-400 text-lg">monetization_on</span>
        Estimasi HPP Real-Time
    </h4>
    <div class="space-y-3">
        <div class="flex justify-between items-center text-xs">
            <span class="text-on-surface-variant font-medium text-slate-500 dark:text-zinc-400">Bahan Baku</span>
            <span class="font-bold text-emerald-950 dark:text-emerald-300" x-text="formatRupiah(totalMaterialCost)">Rp 0</span>
        </div>
        <div class="flex justify-between items-center text-xs">
            <span class="text-on-surface-variant font-medium text-slate-500 dark:text-zinc-400">Tenaga Kerja</span>
            <span class="font-bold text-emerald-950 dark:text-emerald-300" x-text="formatRupiah(laborCost)">Rp 0</span>
        </div>
        <div class="flex justify-between items-center text-xs">
            <span class="text-on-surface-variant font-medium text-slate-500 dark:text-zinc-400">Overhead Batch</span>
            <span class="font-bold text-emerald-950 dark:text-emerald-300" x-text="formatRupiah(overheadCost)">Rp 0</span>
        </div>
        <div class="pt-3 border-t border-dashed border-slate-200 dark:border-zinc-800 flex justify-between items-center">
            <span class="text-xs font-bold text-emerald-900 dark:text-emerald-350">Total Biaya</span>
            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400" x-text="formatRupiah(totalProductionCost)">Rp 0</span>
        </div>
        <div class="pt-3 border-t border-solid border-slate-100 dark:border-zinc-850 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold text-slate-600 dark:text-zinc-300">Estimasi HPP/Unit</span>
                <p class="text-[9px] text-slate-400 dark:text-zinc-500" x-show="goodQuantity > 0" x-text="'Untuk ' + goodQuantity + ' unit bagus'"></p>
            </div>
            <span class="text-base font-black text-emerald-700 dark:text-emerald-400" x-text="formatRupiah(hppPerUnit)">Rp 0</span>
        </div>
    </div>
</div>

<div class="bg-emerald-900 text-white rounded-xl p-8 relative overflow-hidden shadow-xl">
<div class="relative z-10">
<p class="text-emerald-300 text-[10px] font-bold uppercase tracking-widest mb-1">Stock Alert</p>
<h4 class="text-xl font-bold mb-4">{{ $stockAlertMaterial?->name ?? 'Belum ada data stok' }}</h4>
<p class="text-emerald-100 text-sm leading-relaxed mb-6">
{{ $stockAlertMaterial ? 'Tersisa ' . number_format($stockAlertMaterial->stock, 2, ',', '.') . ' ' . $stockAlertMaterial->unit . '. Segera lakukan restock.' : 'Data bahan baku belum tersedia.' }}
</p>
<a href="{{ route('materials.index') }}" class="block text-center w-full bg-emerald-800 text-emerald-100 text-xs font-bold py-3 rounded-lg hover:bg-emerald-700 transition-colors">Pesan Sekarang</a>
</div>
<div class="absolute -bottom-10 -right-10 opacity-10">
<span class="material-symbols-outlined text-[160px]" style="font-variation-settings: 'FILL' 1;">inventory_2</span>
</div>
</div>
<div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-gray-100 dark:border-zinc-800/50 space-y-4 hover:shadow-md transition-all duration-300">
<h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-250 border-b border-slate-50 dark:border-zinc-800/80 pb-3">Ringkasan Pengeluaran</h4>
<div class="space-y-3">
<div class="flex justify-between items-center text-xs">
<span class="text-on-surface-variant font-medium text-slate-500 dark:text-zinc-400">Total Biaya Bahan Baku</span>
<span class="font-bold text-emerald-900 dark:text-emerald-300">Rp {{ number_format($materialCostEstimate, 0, ',', '.') }}</span>
</div>
<div class="flex justify-between items-center text-xs">
<span class="text-on-surface-variant font-medium text-slate-500 dark:text-zinc-400">Overhead Workshop</span>
<span class="font-bold text-emerald-900 dark:text-emerald-300">Rp {{ number_format($overheadCostEstimate, 0, ',', '.') }}</span>
</div>
<div class="pt-3 border-t border-dashed border-slate-200 dark:border-zinc-800 flex justify-between items-center">
<span class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-400">Total Produksi</span>
<span class="text-sm font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalProductionEstimate, 0, ',', '.') }}</span>
</div>
</div>
</div>
</aside>
</div>
{{-- Search, Sort & Filter Controls --}}
<x-table-controls
    :action="route('productions.index')"
    searchPlaceholder="Cari batch code, produk, supervisor..."
    :sortOptions="[
        ['value' => 'production_date_desc', 'label' => 'Tanggal Terbaru'],
        ['value' => 'production_date_asc', 'label' => 'Tanggal Terlama'],
        ['value' => 'quantity_desc', 'label' => 'Kuantitas Tertinggi'],
        ['value' => 'quantity_asc', 'label' => 'Kuantitas Terendah'],
        ['value' => 'total_cost_snapshot_desc', 'label' => 'Biaya Tertinggi'],
        ['value' => 'unit_hpp_snapshot_asc', 'label' => 'HPP Terendah'],
        ['value' => 'unit_hpp_snapshot_desc', 'label' => 'HPP Tertinggi'],
    ]"
    :filterOptions="[
        ['name' => 'status', 'label' => 'Status', 'choices' => ['process' => 'Dalam Proses', 'done' => 'Selesai', 'cancelled' => 'Dibatalkan']],
    ]"
/>

<section class="space-y-6">
<div class="flex items-center justify-between">
<h3 class="text-xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 tracking-tight">Batch Produksi</h3>
<span class="text-[10px] font-bold text-slate-400 dark:text-zinc-400 bg-white dark:bg-zinc-900 px-3 py-1.5 rounded-full border border-outline-variant/5">
    {{ $runningProductions->total() }} batch ditemukan
</span>
</div>
<div id="tour-production-table" class="w-full overflow-x-auto border border-gray-100 dark:border-zinc-800/50 rounded-lg mb-4 bg-surface-container-lowest shadow-sm" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
<table class="min-w-[800px] w-full text-xs text-left border-collapse whitespace-nowrap">
<thead>
<tr class="bg-surface-container-high">
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">ID Batch</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Produk</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest text-center">Qty</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest text-center">Yield</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">HPP/Unit</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Mulai</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Status</th>
<th class="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-widest text-right">Aksi</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-50 dark:divide-zinc-800/40">
@forelse ($runningProductions as $production)
<tr class="hover:bg-primary-fixed/10 transition-all cursor-pointer group">
<td class="px-8 py-5">
<span class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ $production->batch_code ?: '#PRD-'.str_pad((string) $production->id, 4, '0', STR_PAD_LEFT) }}</span>
</td>
<td class="px-8 py-5">
<div class="flex items-center">
<div>
<p class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ $production->product?->name ?? '-' }}</p>
<p class="text-[10px] font-semibold text-slate-400 dark:text-zinc-400">{{ $production->supervisor_name ?: 'Tanpa supervisor' }}</p>
</div>
</div>
</td>
<td class="px-8 py-5 text-center">
<span class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">{{ number_format($production->quantity, 0, ',', '.') }} Unit</span>
</td>
<td class="px-8 py-5 text-center">
<span class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400" data-production-qty="{{ $production->id }}">{{ number_format((int) ($production->good_quantity ?? 0), 0, ',', '.') }}</span>
</td>
<td class="px-8 py-5">
<span class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($production->unit_hpp_snapshot ?? 0, 0, ',', '.') }}</span>
</td>
<td class="px-8 py-5">
<span class="text-sm font-medium text-on-surface-variant">{{ \Carbon\Carbon::parse($production->production_date)->translatedFormat('d M Y') }}</span>
</td>
<td class="px-8 py-5">
<span class="badge" data-production-status="{{ $production->id }}" data-status="{{ $production->status }}" style="display: inline-flex; align-items: center; padding: 0.5rem 0.75rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; {{ $production->status === 'done' ? 'background-color: #f3f4f6; color: #4b5563;' : ($production->status === 'cancelled' ? 'background-color: #fef2f2; color: #b91c1c;' : 'background-color: #f0fdfa; color: #0d9488;') }}">
{{ $production->status === 'done' ? 'Selesai' : ($production->status === 'cancelled' ? 'Dibatalkan' : 'Dalam Proses') }}
</span>
</td>
<td class="px-8 py-5 text-right">
<div class="inline-flex items-center gap-1">
@if ($production->status !== 'done')
    @if(auth()->user()->isAdmin())
    <form action="{{ route('productions.update-status', $production) }}" method="POST" data-realtime-submit="true" data-production-action="done" data-production-id="{{ $production->id }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="production_id" value="{{ $production->id }}"/>
    <input type="hidden" name="status" value="done"/>
    <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 transition-colors flex items-center gap-1" type="submit" title="Tandai selesai">
    <span class="material-symbols-outlined text-sm">check_circle</span>
    <span>Selesai</span>
    </button>
    </form>
    @endif
@endif

@if ($production->status !== 'cancelled')
    @if(auth()->user()->isAdmin())
    <form action="{{ route('productions.update-status', $production) }}" method="POST" data-realtime-submit="true" data-production-action="cancelled" data-production-id="{{ $production->id }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="production_id" value="{{ $production->id }}"/>
    <input type="hidden" name="status" value="cancelled"/>
    <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 transition-colors flex items-center gap-1" type="submit" title="Batalkan batch">
    <span class="material-symbols-outlined text-sm">cancel</span>
    <span>Batal</span>
    </button>
    </form>
    @endif
@endif

@if(auth()->user()->isAdmin())
<form action="{{ route('productions.destroy', $production) }}" method="POST" onsubmit="return confirm('Hapus batch produksi ini?')">
@csrf
@method('DELETE')
<button class="px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 hover:bg-red-100 transition-colors flex items-center gap-1" type="submit" title="Hapus batch">
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
<td class="px-8 py-6 text-sm text-on-surface-variant" colspan="8">Belum ada batch produksi.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-8 py-4 bg-surface-container-low border-t border-outline-variant/5 rounded-b-xl">
    {{ $runningProductions->appends(request()->query())->links() }}
</div>
</section>
</div>
@endsection

@section('scripts')
<script>
function productionForm() {
    return {
        productId: '',
        quantity: parseFloat('{{ old('quantity') }}') || '',
        rejectQuantity: parseFloat('{{ old('reject_quantity') }}') || 0,
        laborCost: parseFloat('{{ old('labor_cost') }}') || 0,
        overheadCost: parseFloat('{{ old('overhead_cost_snapshot') }}') || 0,
        ingredients: [],
        availableMaterials: {!! json_encode($materials->map(function($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'unit' => $m->unit,
                'price' => (float) $m->price,
                'stock' => (float) $m->stock
            ];
        })) !!},
        isLoading: false,

        init() {
            const oldProductId = '{{ old('product_id', '') }}';
            const oldMaterials = @json(old('materials', []));
            
            if (oldProductId) {
                this.productId = oldProductId;
                this.quantity = parseFloat('{{ old('quantity') }}') || 0;
                this.rejectQuantity = parseFloat('{{ old('reject_quantity') }}') || 0;
                this.laborCost = parseFloat('{{ old('labor_cost') }}') || 0;
                this.overheadCost = parseFloat('{{ old('overhead_cost_snapshot') }}') || 0;
                
                this.isLoading = true;
                fetch(`/produksi/resep/${oldProductId}`)
                    .then(res => res.json())
                    .then(data => {
                        const standardIngredients = data.map(item => {
                            const oldItem = oldMaterials.find(om => om.material_id == item.id);
                            return {
                                material_id: item.id,
                                name: item.name,
                                unit: item.unit,
                                price: parseFloat(item.price),
                                quantity: oldItem ? parseFloat(oldItem.quantity) : (parseFloat(item.default_quantity) * (parseFloat(this.quantity) || 1)),
                                stock: parseFloat(item.stock),
                                is_extra: false,
                                default_quantity: parseFloat(item.default_quantity)
                            };
                        });
                        
                        const extraIngredients = [];
                        oldMaterials.forEach(om => {
                            if (!data.some(item => item.id == om.material_id)) {
                                const mat = this.availableMaterials.find(m => m.id == om.material_id);
                                if (mat) {
                                    extraIngredients.push({
                                        material_id: om.material_id,
                                        name: mat.name,
                                        unit: mat.unit,
                                        price: parseFloat(mat.price),
                                        quantity: parseFloat(om.quantity) || 0,
                                        stock: parseFloat(mat.stock),
                                        is_extra: true,
                                        default_quantity: 0
                                    });
                                }
                            }
                        });
                        
                        this.ingredients = [...standardIngredients, ...extraIngredients];
                        this.isLoading = false;
                    })
                    .catch(err => {
                        console.error('Error restoring old inputs:', err);
                        this.isLoading = false;
                    });
            }

            this.$watch('productId', value => {
                if (value && value !== oldProductId) {
                    this.loadRecipe(value);
                } else if (!value) {
                    this.ingredients = [];
                }
            });
        },

        loadRecipe(productId) {
            if (!productId) {
                this.ingredients = [];
                return;
            }
            this.isLoading = true;
            fetch(`/produksi/resep/${productId}`)
                .then(res => res.json())
                .then(data => {
                    this.ingredients = data.map(item => ({
                        material_id: item.id,
                        name: item.name,
                        unit: item.unit,
                        price: parseFloat(item.price),
                        quantity: parseFloat(item.default_quantity) * (parseFloat(this.quantity) || 1),
                        stock: parseFloat(item.stock),
                        is_extra: false,
                        default_quantity: parseFloat(item.default_quantity)
                    }));
                    this.isLoading = false;
                })
                .catch(err => {
                    console.error('Error fetching recipe:', err);
                    this.isLoading = false;
                });
        },

        updateQuantityMultiplier() {
            if (this.ingredients.length > 0) {
                this.ingredients.forEach(item => {
                    if (!item.is_extra) {
                        item.quantity = item.default_quantity * (parseFloat(this.quantity) || 1);
                    }
                });
            }
        },

        addExtraIngredient() {
            this.ingredients.push({
                material_id: '',
                name: '',
                unit: 'SATUAN',
                price: 0,
                quantity: 1,
                stock: 0,
                is_extra: true,
                default_quantity: 0
            });
        },

        onExtraMaterialChange(index) {
            const ing = this.ingredients[index];
            const mat = this.availableMaterials.find(m => m.id == ing.material_id);
            if (mat) {
                ing.name = mat.name;
                ing.unit = mat.unit;
                ing.price = mat.price;
                ing.stock = mat.stock;
            } else {
                ing.name = '';
                ing.unit = 'SATUAN';
                ing.price = 0;
                ing.stock = 0;
            }
        },

        removeIngredient(index) {
            this.ingredients.splice(index, 1);
        },

        get totalMaterialCost() {
            return this.ingredients.reduce((sum, ing) => {
                const qty = parseFloat(ing.quantity) || 0;
                const price = parseFloat(ing.price) || 0;
                return sum + (qty * price);
            }, 0);
        },

        get totalProductionCost() {
            return this.totalMaterialCost + (parseFloat(this.laborCost) || 0) + (parseFloat(this.overheadCost) || 0);
        },

        get goodQuantity() {
            const qty = parseInt(this.quantity) || 0;
            const reject = parseInt(this.rejectQuantity) || 0;
            return Math.max(0, qty - reject);
        },

        get hppPerUnit() {
            const goodQty = this.goodQuantity;
            return goodQty > 0 ? this.totalProductionCost / goodQty : 0;
        },

        formatRupiah(val) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        },

        submitForm(e) {
            if (this.ingredients.length === 0) {
                alert('Minimal satu bahan baku harus diisi.');
                e.preventDefault();
                return false;
            }
            const hasUnselectedExtra = this.ingredients.some(ing => ing.is_extra && !ing.material_id);
            if (hasUnselectedExtra) {
                alert('Silakan pilih bahan baku untuk semua bahan ekstra yang ditambahkan.');
                e.preventDefault();
                return false;
            }
        }
    };
}

// Driver.js Guided Tour Initialization for Input Produksi
document.addEventListener('DOMContentLoaded', function () {
    const btnStartTour = document.getElementById('btn-start-tour');
    if (btnStartTour && window.driver) {
        const driver = window.driver.js.driver;
        
        const steps = [
            {
                element: '#tour-production-stats',
                popover: {
                    title: 'Indikator Dapur',
                    description: 'Pantau jumlah batch, tingkat keberhasilan produk jadi, dan estimasi biaya per unit Anda hari ini.',
                    side: 'bottom',
                    align: 'center'
                }
            }
        ];

        if (document.getElementById('production-form')) {
            steps.push({
                element: '#production-form',
                popover: {
                    title: 'Mulai Masak / Produksi',
                    description: 'Setiap kali Anda mulai memasak, masukkan resep dan jumlah porsi di sini. Sistem akan otomatis memotong stok bahan baku dan menghitung HPP-nya.',
                    side: 'top',
                    align: 'start'
                }
            });
        }

        steps.push({
            element: '#tour-production-table',
            popover: {
                title: 'Riwayat Batch',
                description: 'Pantau proses yang sedang berjalan di dapur. Setelah makanan siap dijual, pastikan untuk mengklik tombol "Selesai".',
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
