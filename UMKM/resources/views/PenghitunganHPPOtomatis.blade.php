@extends('layouts.app')
@section('title', 'HPP Otomatis')
@section('page_title', 'Penghitungan HPP Otomatis')

@section('content')
<section class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-10">
<!-- Header Section -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-12">
<div>
<h1 class="text-4xl font-extrabold text-on-surface tracking-tight mb-2">Penghitungan HPP Otomatis</h1>
<p class="text-on-surface-variant max-w-xl leading-relaxed">Analisis HPP berbasis batch produksi selesai untuk <span class="font-bold text-primary">{{ $selectedProductName }}</span> pada periode <span class="font-semibold">{{ $periodLabel }}</span>.</p>
</div>
<div class="flex flex-wrap gap-2 w-full sm:w-auto">
<a class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold bg-surface-container-highest text-on-surface rounded-xl hover:bg-surface-container-high transition-colors text-center" href="{{ route('reports.index') }}">Lihat Laporan</a>
</div>
</div>
@if (session('success'))
<div class="mb-6 rounded-xl bg-teal-50 text-teal-800 border border-teal-100 px-4 py-3 text-sm font-medium">
{{ session('success') }}
</div>
@endif
@unless ($hasProductionData)
<div class="mb-6 rounded-xl bg-amber-50 text-amber-800 border border-amber-100 px-4 py-3 text-sm font-medium">
Belum ada data produksi selesai untuk filter yang dipilih. Ubah produk atau periode untuk melihat perhitungan HPP.
</div>
@endunless
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
<div class="rounded-xl bg-white p-4 border border-slate-100">
<div class="text-xs uppercase tracking-wider text-slate-500">Batch Selesai</div>
<div class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($doneBatches, 0, ',', '.') }}</div>
</div>
<div class="rounded-xl bg-white p-4 border border-slate-100">
<div class="text-xs uppercase tracking-wider text-slate-500">Unit Diproduksi</div>
<div class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($producedUnits, 0, ',', '.') }}</div>
</div>
<div class="rounded-xl bg-white p-4 border border-slate-100">
<div class="text-xs uppercase tracking-wider text-slate-500">Reject Rate</div>
<div class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($rejectRate, 2, ',', '.') }}%</div>
</div>
</div>
<!-- Bento Grid Layout -->
<div class="grid grid-cols-12 gap-8">
<!-- Summary Card -->
<div class="col-span-12 lg:col-span-4 space-y-8">
<!-- Distribution Chart (Simulated with CSS) -->
<div class="bg-surface-container-lowest p-8 rounded-xl shadow-[0_12px_40px_rgba(0,80,80,0.06)] relative overflow-hidden">
<h3 class="text-xs uppercase tracking-widest font-bold text-on-surface-variant mb-8">Distribusi Biaya</h3>
<div class="flex justify-center mb-8">
<!-- Custom CSS Pie Chart Placeholder -->
<div class="relative w-48 h-48 rounded-full flex items-center justify-center" style="background: conic-gradient(#005050 0% {{ $materialStopPercent }}%, #4a6363 {{ $materialStopPercent }}% {{ $laborStopPercent }}%, #70371a {{ $laborStopPercent }}% 100%);">
<div class="absolute inset-4 bg-white rounded-full flex flex-col items-center justify-center">
<span class="text-xs font-bold text-slate-400">Total HPP</span>
<span class="text-2xl font-black text-primary">Rp {{ number_format($hppPerUnit, 0, ',', '.') }}</span>
</div>
</div>
</div>
<div class="space-y-4">
<div class="flex items-center justify-between text-sm">
<div class="flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-primary"></div>
<span class="font-medium">Bahan Baku</span>
</div>
<span class="font-bold">{{ $materialPercent }}%</span>
</div>
<div class="flex items-center justify-between text-sm">
<div class="flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-secondary"></div>
<span class="font-medium">Tenaga Kerja</span>
</div>
<span class="font-bold">{{ $laborPercent }}%</span>
</div>
<div class="flex items-center justify-between text-sm">
<div class="flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-tertiary"></div>
<span class="font-medium">Overhead</span>
</div>
<span class="font-bold">{{ $overheadPercent }}%</span>
</div>
</div>
</div>
<!-- Market Comparison Card -->
<div class="p-8 rounded-xl shadow-lg relative overflow-hidden" 
     style="background-color: #005050 !important; color: #ffffff !important;">
<div class="absolute top-0 right-0 p-4 opacity-20">
<span class="material-symbols-outlined text-6xl" style="font-variation-settings: 'FILL' 1;">trending_up</span>
</div>
@php
    $hasData = $totalHpp > 0;
    $currentPrice = $selectedProduct ? (float)$selectedProduct->selling_price : ($hppPerUnit > 0 ? $hppPerUnit * 1.2 : 0);
    $margin = ($currentPrice > 0 && $hasData) ? (($currentPrice - $hppPerUnit) / $currentPrice) * 100 : 0;
@endphp

<h3 class="text-xs uppercase tracking-widest font-bold text-primary-fixed mb-4">Analisis Margin {{ $selectedProduct ? 'Riil' : '(Simulasi)' }}</h3>

@if($hasData)
    <div class="text-3xl font-black mb-1 {{ $margin < 15 ? 'text-error-container' : '' }}">{{ number_format($margin, 1) }}%</div>
    <p class="text-primary-fixed text-sm">
        @if($selectedProduct)
            Berdasarkan harga jual saat ini (Rp {{ number_format($currentPrice, 0, ',', '.') }}). 
            {!! $margin < 15 ? '<b>Sangat Tipis!</b>' : 'Kesehatan margin cukup baik.' !!}
        @else
            Simulasi margin dihitung dengan asumsi markup 20% dari HPP periode terpilih.
        @endif
    </p>
    <div class="mt-6 pt-6 border-t border-white/10 flex justify-between items-center">
        <span class="text-xs opacity-70">{{ $selectedProduct ? 'Harga Jual Saat Ini' : 'Harga Jual Rekomendasi' }}</span>
        <span class="font-bold text-lg">Rp {{ number_format($currentPrice, 0, ',', '.') }}</span>
    </div>
@else
    <div class="py-4 text-center">
        <span class="material-symbols-outlined text-4xl opacity-20 block mb-2">Query_Stats</span>
        <p class="text-xs font-bold text-primary-fixed opacity-70">Data produksi tidak ditemukan pada periode ini. Silakan pilih rentang tanggal lain.</p>
    </div>
@endif
</div>
</div>
<!-- Detailed Ledger Content -->
<div class="col-span-12 lg:col-span-8 space-y-8">
<!-- Automatic Calculation Input Section -->
<div class="bg-surface-container-low p-8 rounded-xl">
<form action="{{ route('hpp.index') }}" method="GET">
<div class="flex items-center justify-between mb-8">
<h3 class="text-lg font-bold text-on-surface">Input Variabel Produksi</h3>
<span class="px-3 py-1 bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wider rounded-full">Auto-Update On</span>
</div>
<div class="grid grid-cols-1 md:grid-cols-5 gap-6">
<div class="space-y-2 md:col-span-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-tighter">Produk</label>
<select name="product_id" class="w-full bg-surface-container-lowest border-none rounded-lg py-3 px-4 focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold">
<option value="">Semua Produk</option>
@foreach ($products as $product)
<option value="{{ $product->id }}" @selected((string) $selectedProductId === (string) $product->id)>
{{ $product->name }}
</option>
@endforeach
</select>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-tighter">Dari Tanggal</label>
<input name="from_date" class="w-full bg-surface-container-lowest border-none rounded-lg py-3 px-4 focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold" type="date" value="{{ $fromDate }}"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-tighter">Sampai Tanggal</label>
<input name="to_date" class="w-full bg-surface-container-lowest border-none rounded-lg py-3 px-4 focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold" type="date" value="{{ $toDate }}"/>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-tighter">Volume Batch</label>
<div class="relative">
<input name="volume_batch" class="w-full bg-surface-container-lowest border-none rounded-lg py-3 px-4 focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold" type="number" value="{{ $volumeBatch }}"/>
<span class="absolute right-4 top-3 text-slate-400 text-xs">Pcs</span>
</div>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-tighter">Waste Factor</label>
<div class="relative">
<input name="waste_factor" class="w-full bg-surface-container-lowest border-none rounded-lg py-3 px-4 focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold" type="number" value="{{ $wasteFactor }}"/>
<span class="absolute right-4 top-3 text-slate-400 text-xs">%</span>
</div>
</div>
<div class="space-y-2">
<label class="text-xs font-bold text-on-surface-variant uppercase tracking-tighter">Durasi Produksi</label>
<div class="relative">
<input name="duration_hours" class="w-full bg-surface-container-lowest border-none rounded-lg py-3 px-4 focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold" type="number" value="{{ $durationHours }}"/>
<span class="absolute right-4 top-3 text-slate-400 text-xs">Jam</span>
</div>
</div>
</div>
<div class="mt-6 flex justify-end">
<button class="px-5 py-2.5 rounded-xl shadow-sm" 
        style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
        type="submit">Hitung Ulang</button>
</div>
</form>
</div>
<!-- Detailed Breakdown Tables -->
<div class="bg-surface-container-lowest rounded-xl shadow-[0_12px_40px_rgba(0,80,80,0.06)] overflow-hidden">
<div class="p-6 bg-surface-container-high flex justify-between items-center">
<h3 class="text-xs uppercase tracking-widest font-black text-on-surface">Rincian Komponen Biaya</h3>
<span class="text-[10px] font-medium text-on-surface-variant italic">Periode: {{ $periodLabel }} | Produk: {{ $selectedProductName }}</span>
</div>
<!-- Table 1: Raw Materials -->
<div class="p-4 sm:p-8">
<div class="flex items-center gap-2 mb-4">
<span class="material-symbols-outlined text-primary flex-shrink-0" style="font-variation-settings: 'FILL' 1;">inventory_2</span>
<h4 class="font-bold text-sm">Bahan Baku (Raw Materials)</h4>
</div>
<div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
<table class="w-full text-left min-w-[800px]">
<thead class="bg-slate-50/50">
<tr>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold">Item</th>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold">Kuantitas</th>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold">Harga Satuan</th>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold text-right">Subtotal</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-50">
@forelse ($materialBreakdown as $item)
<tr class="hover:bg-primary/5 transition-colors group">
<td class="px-2 py-3 text-[11px] whitespace-nowrap font-medium">{{ $item->name }}</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap">{{ number_format($item->qty_used, 0, ',', '.') }} {{ $item->unit }}</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
</tr>
@empty
<tr>
<td class="px-2 py-3 text-[11px] whitespace-nowrap text-on-surface-variant" colspan="4">Belum ada data komponen bahan baku.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
<!-- Table 2: Labor & Overhead -->
<div class="p-4 sm:p-8 pt-0">
<div class="flex items-center gap-2 mb-4">
<span class="material-symbols-outlined text-secondary flex-shrink-0" style="font-variation-settings: 'FILL' 1;">groups</span>
<h4 class="font-bold text-sm">Operasional &amp; Tenaga Kerja</h4>
</div>
<div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
<table class="w-full text-left min-w-[800px]">
<thead class="bg-slate-50/50">
<tr>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold">Kategori</th>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold">Keterangan</th>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold">Rate</th>
<th class="px-2 py-3 text-[11px] whitespace-nowrap text-slate-400 uppercase tracking-wider font-bold text-right">Subtotal</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-50">
@forelse ($overheadItems as $cost)
<tr class="hover:bg-secondary/5 transition-colors">
<td class="px-2 py-3 text-[11px] whitespace-nowrap font-medium">{{ $cost->name }}</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap">Biaya overhead</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap">Tetap</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap text-right font-bold">Rp {{ number_format($cost->cost, 0, ',', '.') }}</td>
</tr>
@empty
<tr>
<td class="px-2 py-3 text-[11px] whitespace-nowrap text-on-surface-variant" colspan="4">Belum ada data biaya overhead.</td>
</tr>
@endforelse
<tr class="bg-surface-container-low hover:bg-secondary/5 transition-colors">
<td class="px-2 py-3 text-[11px] whitespace-nowrap font-medium">Tenaga Kerja Estimasi</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap">Turunan dari overhead</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap">20% overhead</td>
<td class="px-2 py-3 text-[11px] whitespace-nowrap text-right font-bold">Rp {{ number_format($laborCost, 0, ',', '.') }}</td>
</tr>
</tbody>
</table>
</div>
</div>
<!-- Final Total Section -->
<div class="p-8 bg-slate-50 flex flex-col items-end gap-2">
<div class="text-xs font-bold text-slate-400 uppercase">Estimasi Akhir HPP per Unit</div>
<div class="text-4xl font-black text-primary">Rp {{ number_format($simulatedHppPerUnit, 0, ',', '.') }}</div>
<div class="text-[10px] text-tertiary font-bold bg-tertiary/10 px-2 py-1 rounded">Total HPP Batch: Rp {{ number_format($totalHpp, 0, ',', '.') }}</div>
</div>
</div>
</div>
</div>
</section>
<!-- Signature Contextual FAB (Suppressed on Ledger/Details as per rules, but shown here for navigation context only if relevant) -->
<!-- Suppressing FAB for this specific transactional/ledger screen to prioritize content canvas focus -->
@endsection
