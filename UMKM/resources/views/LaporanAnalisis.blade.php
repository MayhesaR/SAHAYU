@extends('layouts.app')
@section('title', 'Laporan & Analisis')
@section('page_title', 'Laporan & Analisis')

@section('content')
<!-- Content Canvas -->
<div class="px-4 py-6 sm:px-8 max-w-full mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-12">
            <div class="w-full lg:w-auto">
                <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-on-surface tracking-tight mb-2 break-words">Laporan &amp; Analisis</h2>
                <p class="text-on-surface-variant max-w-md leading-relaxed text-sm">Pantau kesehatan finansial bisnis Anda dengan metrik real-time dan analisis mendalam.</p>
            </div>
            <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                {{-- Quick Period Buttons --}}
                <div class="flex flex-wrap items-center gap-2 bg-surface-container-low p-1.5 rounded-xl w-full sm:w-auto">
                    <a href="{{ route('reports.index', ['view_mode' => 'mingguan', 'specific_month' => $specificMonth]) }}"
                       class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $activePeriod === 'mingguan' ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-lowest' }}">
                        Mingguan
                    </a>
                    <a href="{{ route('reports.index', ['view_mode' => 'bulanan', 'specific_month' => $specificMonth]) }}"
                       class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $activePeriod === 'bulanan' ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-lowest' }}">
                        Bulanan
                    </a>
                    <a href="{{ route('reports.index', ['view_mode' => 'tahunan', 'specific_month' => $specificMonth]) }}"
                       class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $activePeriod === 'tahunan' ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-lowest' }}">
                        Tahunan
                    </a>
                </div>

                {{-- Specific Month Picker & Filter Form --}}
                <form action="{{ route('reports.index') }}" method="GET" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <input type="hidden" name="view_mode" value="{{ $activePeriod }}">
                    
                    @if($activePeriod === 'mingguan')
                    <div class="relative w-full sm:w-auto">
                        <select name="week_number" onchange="this.form.submit()"
                                class="w-full pl-3 pr-8 py-2 bg-surface-container-highest border-none rounded-lg text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer appearance-none">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ $weekNumber == $i ? 'selected' : '' }}>Minggu {{ $i }}</option>
                            @endfor
                        </select>
                        <span class="material-symbols-outlined text-sm text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                    </div>
                    @endif

                    <!-- Month Picker -->
                    <div class="relative w-full sm:w-auto" title="Filter Bulan">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none z-10 text-slate-400">
                            <span class="material-symbols-outlined text-sm">calendar_month</span>
                        </span>
                        <input type="month"
                               name="specific_month"
                               value="{{ $specificMonth }}"
                               onchange="this.form.submit()"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-teal-500 focus:border-teal-500 bg-surface-container-highest text-sm font-semibold text-teal-900 transition-all cursor-pointer" />
                    </div>

                    <!-- Daily Date Picker -->
                    <div class="relative w-full sm:w-auto" title="Filter Tanggal Spesifik">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none z-10 text-slate-400">
                            <span class="material-symbols-outlined text-sm">calendar_today</span>
                        </span>
                        <input type="date"
                               name="filter_date"
                               value="{{ $filterDate }}"
                               onchange="this.form.submit()"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-teal-500 focus:border-teal-500 bg-surface-container-highest text-sm font-semibold text-teal-900 transition-all cursor-pointer" />
                    </div>
                    
                    <button type="submit"
                            class="flex-shrink-0 w-full sm:w-auto px-3 py-2 sm:px-4 rounded-lg text-sm shadow-sm transition-all flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95"
                            style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;">
                        <span class="material-symbols-outlined text-sm flex-shrink-0">filter_alt</span>
                        <span class="hidden sm:inline">Filter</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Active Period Label --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 px-3 sm:px-4 py-2 bg-teal-50 rounded-lg border border-teal-200">
                <span class="material-symbols-outlined text-sm text-teal-600 hidden sm:inline">date_range</span>
                <span class="text-xs sm:text-sm font-bold text-teal-800 break-words">Menampilkan Laporan: {{ $periodLabel }}</span>
            </div>
            
            @if($filterDate)
            <a href="{{ route('reports.index') }}"
               class="flex items-center gap-1.5 px-3 py-2 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-xs font-bold hover:bg-rose-100 transition-all">
                <span class="material-symbols-outlined text-sm">close</span>
                Hapus Filter Harian
            </a>
            @elseif($activePeriod !== 'bulanan')
            <a href="{{ route('reports.index') }}"
               class="flex items-center gap-1.5 px-3 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-200 transition-all">
                <span class="material-symbols-outlined text-sm">restart_alt</span>
                Reset ke Bulan Ini
            </a>
            @endif
        </div>
    </div>

    <!-- Bento Grid - Key Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-12">
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 border-l-4 border-l-primary">
            <span class="text-on-surface-variant text-[10px] font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <span>Total Pendapatan</span>
                <span class="relative inline-block group/tooltip">
                    <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors focus:outline-none flex items-center">
                        <span class="material-symbols-outlined text-[14px]">info</span>
                    </button>
                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-900 text-white text-[10px] rounded-lg shadow-lg opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-200 z-50 text-center font-medium font-sans normal-case tracking-normal">
                        Nilai murni omzet penjualan barang khusus hari ini (Tunai + Nota Utang Baru). Tidak mencakup uang tagihan/cicilan masa lalu.
                        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900"></span>
                    </span>
                </span>
            </span>
            <span class="text-[10px] text-slate-400 font-medium block mb-3">Penjualan Riil (Tunai + Piutang)</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-on-surface">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
            <span class="text-on-surface-variant text-[10px] font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <span>Laba Bersih</span>
                <span class="relative inline-block group/tooltip">
                    <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors focus:outline-none flex items-center">
                        <span class="material-symbols-outlined text-[14px]">info</span>
                    </button>
                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-900 text-white text-[10px] rounded-lg shadow-lg opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-200 z-50 text-center font-medium font-sans normal-case tracking-normal">
                        Performa keuntungan bisnis riil hari ini (Total Pendapatan Omzet - Total Modal HPP & Operasional).
                        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900"></span>
                    </span>
                </span>
            </span>
            <span class="text-[10px] text-slate-400 font-medium block mb-3">Margin Akrual Bisnis</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-on-surface">Rp {{ number_format($netProfit, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
            <span class="text-on-surface-variant text-[10px] font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                <span>Total Pengeluaran</span>
                <span class="relative inline-block group/tooltip">
                    <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors focus:outline-none flex items-center">
                        <span class="material-symbols-outlined text-[14px]">info</span>
                    </button>
                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-900 text-white text-[10px] rounded-lg shadow-lg opacity-0 pointer-events-none group-hover/tooltip:opacity-100 group-focus/tooltip:opacity-100 transition-opacity duration-200 z-50 text-center font-medium font-sans normal-case tracking-normal">
                        Nilai total Modal (HPP) dari barang yang laku terjual hari ini + Biaya Operasional harian.
                        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900"></span>
                    </span>
                </span>
            </span>
            <span class="text-[10px] text-slate-400 font-medium block mb-3">HPP Terjual + Operasional</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-on-surface">Rp {{ number_format($totalExpense, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
            <span class="text-on-surface-variant text-[10px] font-bold uppercase tracking-widest block mb-4">Margin Laba</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-on-surface">{{ number_format($profitMargin, 1) }}%</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 {{ $rejectRate > 5 ? 'border-l-4 border-l-error' : '' }}">
            <span class="text-on-surface-variant text-[10px] font-bold uppercase tracking-widest block mb-4">Reject Rate</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black {{ $rejectRate > 5 ? 'text-error' : 'text-on-surface' }}">{{ number_format($rejectRate, 1) }}%</span>
            </div>
            @if($rejectRate > 5)
            <p class="text-[10px] text-error mt-2 font-semibold">Melebihi batas aman 5%!</p>
            @endif
        </div>
    </div>

    <!-- Analysis Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <!-- Growth Chart -->
        <div class="lg:col-span-2 bg-surface-container-lowest p-8 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
                <h3 class="text-xl font-bold text-on-surface">Tren Penjualan ({{ ucfirst($activePeriod) }})</h3>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-primary flex-shrink-0"></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Realisasi (Rp)</span>
                </div>
            </div>

            <div class="relative w-full h-[300px] md:h-[450px]">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Right Column (Expense & Products) -->
        <div class="space-y-8">
            <!-- Expense Breakdown Chart -->
            <div class="bg-surface-container-lowest p-8 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="text-xl font-bold text-on-surface mb-6">Rincian Biaya (HPP)</h3>
                <div class="relative w-full h-[300px] md:h-[450px]">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>

            <!-- Popular Products -->
            <div class="bg-surface-container-lowest p-8 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="text-xl font-bold text-on-surface mb-8">Produk Terpopuler</h3>
                <div class="space-y-6">
                    @forelse ($popularProducts as $product)
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-surface-container-low flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-primary flex-shrink-0">inventory_2</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-on-surface truncate">{{ $product->name }}</p>
                                <p class="text-[10px] text-on-surface-variant truncate">{{ number_format($product->total_qty, 0, ',', '.') }} Unit Terjual</p>
                            </div>
                        </div>
                        <span class="text-xs font-black text-teal-700 flex-shrink-0">Rp {{ number_format($product->total_revenue, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-on-surface-variant">Belum ada data produk terpopuler.</p>
                    @endforelse
                    <div class="pt-4 border-t border-surface-container-low">
                        <button class="w-full py-2.5 rounded-lg transition-all hover:scale-[1.02] active:scale-95" 
                                style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900; font-size: 0.75rem;">
                            Lihat Semua Produk
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Table Section -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 overflow-hidden w-full">
        <div class="px-4 sm:px-8 py-6 flex flex-col sm:flex-row items-start sm:items-center justify-between bg-surface-container-high/50 border-b border-outline-variant/5 gap-4">
            <h3 class="text-lg font-bold text-on-surface">Rincian Performa ({{ ucfirst($activePeriod) }})</h3>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('reports.export-pdf') }}" target="_blank" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-surface-container-highest text-on-surface rounded-lg text-sm font-semibold hover:bg-surface-dim transition-all border border-outline-variant/20" title="Ekspor ke PDF">
                    <span class="material-symbols-outlined text-sm flex-shrink-0">picture_as_pdf</span> PDF
                </a>
                <a href="{{ route('reports.export-sheets') }}" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-teal-50 text-teal-700 rounded-lg text-sm font-semibold hover:bg-teal-100 transition-all border border-teal-200" title="Unduh Excel (XLSX)">
                    <span class="material-symbols-outlined text-sm flex-shrink-0">table</span> Spreadsheet
                </a>
                <a href="{{ route('reports.export-csv') }}" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm shadow-sm transition-all" 
                   style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;" 
                   title="Unduh CSV Native">
                    <span class="material-symbols-outlined text-sm flex-shrink-0">description</span> CSV
                </a>
            </div>
        </div>
        <div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
            <table class="min-w-[800px] w-full text-left whitespace-nowrap">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-surface-container-high text-on-surface-variant text-[11px] md:text-xs font-bold uppercase tracking-widest whitespace-nowrap">
                        <th class="px-2 sm:px-8 py-3 sm:py-4">Periode</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4">Target Penjualan</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4">Realisasi</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4">Capaian (%)</th>
                        <th class="px-2 sm:px-8 py-3 sm:py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-sm text-on-surface">
                    @forelse (array_reverse($trendData) as $index => $row)
                    <tr class="{{ $index % 2 === 1 ? 'bg-surface-container-low/30' : '' }} hover:bg-primary/5 transition-colors group border-b border-outline-variant/5 whitespace-nowrap">
                        <td class="px-2 sm:px-8 py-3 sm:py-4 font-semibold">{{ $row['label'] }}</td>
                        <td class="px-2 sm:px-8 py-3 sm:py-4">Rp {{ number_format($row['target'], 0, ',', '.') }}</td>
                        <td class="px-2 sm:px-8 py-3 sm:py-4 font-bold text-teal-800">Rp {{ number_format($row['realization'], 0, ',', '.') }}</td>
                        <td class="px-2 sm:px-8 py-3 sm:py-4 {{ $row['growth'] < 0 ? 'text-error' : 'text-teal-600' }} font-bold">
                            {{ $row['growth'] >= 0 ? '+' : '' }}{{ number_format($row['growth'], 1) }}%
                        </td>
                        <td class="px-2 sm:px-8 py-3 sm:py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{
                                $row['status'] === 'Exceeded' ? 'bg-teal-50 text-teal-700' :
                                ($row['status'] === 'Near Target' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700')
                            }}">
                                {{ $row['status'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td class="px-8 py-10 text-sm text-on-surface-variant text-center" colspan="5">Belum ada data performa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const expenseData = @json(array_values($expenseBreakdown));
        const expenseLabels = @json(array_keys($expenseBreakdown));
        
        // Only render if there's data to show
        const hasData = expenseData.some(val => parseFloat(val) > 0);
        
        if (hasData) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: expenseLabels,
                    datasets: [{
                        data: expenseData,
                        backgroundColor: [
                            '#0f766e', // Teal 700 (Bahan Baku)
                            '#0ea5e9', // Sky Blue 500 (Tenaga Kerja)
                            '#f59e0b'  // Amber 500 (Overhead)
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 11, family: "ui-sans-serif, system-ui, sans-serif" }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // Show placeholder if no data
            const container = document.getElementById('expenseChart').parentElement;
            container.innerHTML = '<div class="flex items-center justify-center h-full text-sm text-slate-400">Belum ada data biaya.</div>';
        }

        // ============================================
        // TREND PENJUALAN (MIXED CHART: BAR + LINE)
        // ============================================
        const trendDataRaw = @json(array_values($trendData));
        const activePeriod = '{{ $activePeriod }}';
        
        // Prepare datasets
        const trendLabels = trendDataRaw.map(item => {
            return activePeriod === 'tahunan' ? item.label.substring(0, 3).toUpperCase() : item.label;
        });
        const realizationData = trendDataRaw.map(item => item.realization || 0);
        const hppData = trendDataRaw.map(item => item.hpp || 0);
        
        const canvasTrend = document.getElementById('trendChart');
        if (canvasTrend) {
            const ctxTrend = canvasTrend.getContext('2d');
            new Chart(ctxTrend, {
                type: 'bar', // Base mixed chart type
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Pendapatan (Realisasi)',
                            data: realizationData,
                            backgroundColor: '#0f766e', // SAHAYU Teal 700
                            borderRadius: 4,
                            order: 2 // Render behind the line
                        },
                        {
                            type: 'line',
                            label: 'HPP (Pengeluaran)',
                            data: hppData,
                            borderColor: '#f59e0b', // SAHAYU Amber 500
                            backgroundColor: '#ffffff',
                            borderWidth: 3,
                            tension: 0.4, // Smooth curve
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#f59e0b',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            order: 1 // Render above the bars
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { family: "ui-sans-serif, system-ui, sans-serif", size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'Jt';
                                    if (value >= 1000) return 'Rp ' + (value / 1000) + 'Rb';
                                    return 'Rp ' + value;
                                },
                                font: { size: 10 }
                            },
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false,
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                font: { size: 10, weight: 'bold' },
                                color: '#64748b' // slate-500
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
