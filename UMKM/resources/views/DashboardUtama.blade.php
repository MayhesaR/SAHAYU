@extends('layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard Utama')

@section('content')
<!-- Dashboard Content -->
<div class="px-4 py-6 sm:px-8 space-y-8">
    <!-- Welcome Header -->
    <div class="flex flex-col gap-4 items-start sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <h2 class="text-3xl font-extrabold text-on-surface font-manrope tracking-tight">Dashboard Utama</h2>
            <p class="text-on-surface-variant font-body mt-1">Performa operasional bisnis Anda bulan ini.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <a class="w-full sm:w-auto px-5 py-2.5 bg-surface-container-highest text-on-surface font-manrope font-semibold text-sm rounded-xl hover:bg-slate-200 hover:shadow-md transition-all flex items-center justify-center gap-2" href="{{ route('materials.index') }}">
                <span class="material-symbols-outlined text-sm flex-shrink-0">inventory_2</span>
                Cek Stok
            </a>
            <a class="w-full sm:w-auto px-5 py-2.5 rounded-xl shadow-lg shadow-teal-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
               style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
               href="{{ route('sales.index') }}">
                <span class="material-symbols-outlined text-sm flex-shrink-0">point_of_sale</span>
                <span>Catat Penjualan</span>
            </a>
        </div>
    </div>

    <!-- Bento Grid Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Sales -->
        <div class="bg-surface-container-lowest p-6 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <div class="p-3 bg-teal-50 text-teal-700 rounded-lg">
                    <span class="material-symbols-outlined flex-shrink-0">payments</span>
                </div>
                <span class="px-2 py-1 rounded text-[10px] font-bold {{ $salesGrowth >= 0 ? 'text-teal-600 bg-teal-50' : 'text-error bg-red-50' }}">
                    {{ $salesGrowth >= 0 ? '+' : '' }}{{ number_format($salesGrowth, 1) }}%
                </span>
            </div>
            <div class="mt-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant font-label">Penjualan Bulan Ini</p>
                <h3 class="text-2xl font-black text-on-surface mt-1">Rp {{ number_format($totalSales, 0, ',', '.') }}</h3>
            </div>
        </div>
        <!-- Production -->
        <div class="bg-surface-container-lowest p-6 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <div class="p-3 bg-slate-50 text-slate-700 rounded-lg">
                    <span class="material-symbols-outlined flex-shrink-0">precision_manufacturing</span>
                </div>
                <span class="px-2 py-1 rounded text-[10px] font-bold {{ $prodGrowth >= 0 ? 'text-teal-600 bg-teal-50' : 'text-error bg-red-50' }}">
                    {{ $prodGrowth >= 0 ? '+' : '' }}{{ number_format($prodGrowth, 1) }}%
                </span>
            </div>
            <div class="mt-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant font-label">Produksi Bulan Ini</p>
                <h3 class="text-2xl font-black text-on-surface mt-1">{{ number_format($totalProduction, 0, ',', '.') }} Unit</h3>
            </div>
        </div>
        <!-- Stock Status -->
        <div class="bg-surface-container-lowest p-6 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <div class="p-3 bg-tertiary-fixed text-tertiary rounded-lg">
                    <span class="material-symbols-outlined flex-shrink-0">inventory_2</span>
                </div>
                <span class="text-tertiary font-bold text-[10px] bg-tertiary-fixed px-2 py-1 rounded">{{ $lowStock }} Krisis</span>
            </div>
            <div class="mt-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant font-label">Kesehatan Stok</p>
                <h3 class="text-2xl font-black text-on-surface mt-1">{{ $stockSafePercent }}% Aman</h3>
            </div>
        </div>
    </div>

    <!-- Visual Data Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sales Trend (Chart) -->
        <div class="lg:col-span-2 bg-surface-container-lowest p-8 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <h4 class="text-lg font-bold text-on-surface font-manrope">Tren Penjualan (30 Hari Terakhir)</h4>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total: Rp {{ number_format($overallSales, 0, ',', '.') }}</div>
            </div>
            
            <div class="h-[250px] md:h-[400px] w-full flex items-end justify-between gap-1 group">
                @php
                    // Pastikan data numeric
                    $maxVal = (float) $chartData->max('total') ?: 1;
                    $days = 30;
                    $chartPoints = [];
                    for($i = $days; $i >= 0; $i--) {
                        $date = now()->subDays($i)->toDateString();
                        // Bandingkan dengan format string DATE() dari SQLite/MySQL
                        $item = $chartData->first(fn($c) => $c->date == $date);
                        $val = $item ? (float) $item->total : 0;
                        $chartPoints[$date] = $val;
                    }
                @endphp

                @foreach($chartPoints as $date => $val)
                    <div class="flex-1 flex flex-col items-center gap-2 group/bar h-full justify-end">
                        <div class="w-full bg-primary/20 rounded-t-sm group-hover/bar:bg-primary transition-all duration-300 relative min-h-[{{ $val > 0 ? '2px' : '0' }}]" 
                             style="height: {{ max(($val / $maxVal) * 100, ($val > 0 ? 2 : 0)) }}%">
                             <!-- Tooltip -->
                             <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-on-surface text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover/bar:opacity-100 transition-opacity whitespace-nowrap z-20 pointer-events-none shadow-xl">
                                {{ \Carbon\Carbon::parse($date)->translatedFormat('d M') }}: Rp {{ number_format($val, 0, ',', '.') }}
                             </div>
                        </div>
                        @if($loop->iteration % 7 == 0 || $loop->last)
                            <span class="text-[8px] text-slate-400 font-bold whitespace-nowrap">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-surface-container-lowest p-8 rounded-xl border border-outline-variant/10 shadow-sm">
            <h4 class="text-lg font-bold text-on-surface font-manrope mb-8">Batch Produksi Terbaru</h4>
            <div class="space-y-6">
                @forelse ($recentProductions as $production)
                <div class="flex items-center gap-4 group cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-primary/10 group-hover:text-primary transition-colors flex-shrink-0">
                        <span class="material-symbols-outlined flex-shrink-0">inventory_2</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h5 class="text-sm font-bold text-on-surface truncate">{{ $production->product?->name ?? 'Produk Terhapus' }}</h5>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-on-surface-variant font-medium">{{ number_format($production->quantity, 0, ',', '.') }} Unit</span>
                            <span class="w-1 h-1 bg-slate-300 rounded-full flex-shrink-0"></span>
                            <span class="text-[10px] text-slate-400 font-medium truncate">{{ \Carbon\Carbon::parse($production->production_date)->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest {{ $production->status === 'done' ? 'bg-teal-50 text-teal-600' : 'bg-amber-50 text-amber-600' }}">
                            {{ $production->status === 'done' ? 'Selesai' : 'Proses' }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-on-surface-variant">Belum ada aktivitas produksi.</p>
                @endforelse
            </div>
            <a class="mt-10 block text-center py-3 border border-dashed border-outline-variant/30 rounded-xl text-xs font-bold text-primary hover:bg-primary/5 transition-colors" href="{{ route('productions.index') }}">
                Kelola Semua Produksi
            </a>
        </div>
    </div>
</div>
@endsection
