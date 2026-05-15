@extends('layouts.app')

@section('content')
<!-- Dashboard Container -->
<div class="px-4 py-6 sm:px-8 space-y-8 bg-slate-50/50 min-h-screen">
    
    <!-- HEADER & DATE FILTER -->
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <h1 class="text-3xl font-black text-slate-900 tracking-tight font-manrope">
                Selamat Datang, <span class="text-primary">{{ $companyName }}</span> 👋
            </h1>
            <p class="text-slate-500 font-medium text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">calendar_today</span>
                {{ now()->translatedFormat('l, d F Y') }}
            </p>
        </div>

        <div class="flex items-center gap-2 bg-white p-1.5 rounded-2xl shadow-sm border border-slate-100 w-fit">
            <a href="{{ route('dashboard', ['range' => '1']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentFilter == '1' ? 'bg-primary text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">
                Hari Ini
            </a>
            <a href="{{ route('dashboard', ['range' => '7']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentFilter == '7' ? 'bg-primary text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">
                7 Hari
            </a>
            <a href="{{ route('dashboard', ['range' => '30']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $currentFilter == '30' ? 'bg-primary text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">
                30 Hari
            </a>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('sales.index') }}" class="group flex flex-col items-center gap-3 p-4 bg-emerald-50 border border-emerald-100 rounded-3xl hover:bg-emerald-600 hover:text-white transition-all duration-300">
            <div class="w-12 h-12 bg-white text-emerald-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">add_shopping_cart</span>
            </div>
            <span class="text-xs font-black uppercase tracking-widest">Catat Penjualan</span>
        </a>
        <a href="{{ route('materials.index') }}" class="group flex flex-col items-center gap-3 p-4 bg-blue-50 border border-blue-100 rounded-3xl hover:bg-blue-600 hover:text-white transition-all duration-300">
            <div class="w-12 h-12 bg-white text-blue-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <span class="text-xs font-black uppercase tracking-widest">Input Stok</span>
        </a>
        <a href="{{ route('productions.index') }}" class="group flex flex-col items-center gap-3 p-4 bg-indigo-50 border border-indigo-100 rounded-3xl hover:bg-indigo-600 hover:text-white transition-all duration-300">
            <div class="w-12 h-12 bg-white text-indigo-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">precision_manufacturing</span>
            </div>
            <span class="text-xs font-black uppercase tracking-widest">Mulai Produksi</span>
        </a>
        <a href="{{ route('ai.index') }}" class="group flex flex-col items-center gap-3 p-4 bg-purple-50 border border-purple-100 rounded-3xl hover:bg-purple-600 hover:text-white transition-all duration-300">
            <div class="w-12 h-12 bg-white text-purple-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">psychology</span>
            </div>
            <span class="text-xs font-black uppercase tracking-widest">Tanya AI</span>
        </a>
    </div>

    <!-- AI INSIGHT BANNER -->
    <div class="relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent rounded-3xl"></div>
        <div class="relative bg-white/40 backdrop-blur-md border border-white/60 p-6 rounded-3xl flex flex-col md:flex-row items-center gap-6 shadow-xl shadow-primary/5">
            <div class="flex-shrink-0 w-16 h-16 bg-primary rounded-2xl flex items-center justify-center shadow-lg shadow-primary/30">
                <span class="material-symbols-outlined text-white text-3xl">lightbulb</span>
            </div>
            <div class="flex-1 space-y-2 text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-primary">SAHAYU AI Insight</span>
                    <span class="h-2 w-2 rounded-full bg-primary animate-ping"></span>
                </div>
                <p class="text-slate-700 font-bold text-lg leading-relaxed">
                    "{{ $aiInsight }}"
                </p>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('ai.index') }}" class="px-6 py-3 bg-white text-primary font-black text-xs rounded-xl shadow-sm hover:shadow-md transition-all border border-primary/10">
                    Detail Analisis
                </a>
            </div>
        </div>
    </div>

    <!-- METRICS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- Sales Card -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="relative z-10">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-xl">payments</span>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Penjualan</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($totalSales, 0, ',', '.') }}</h3>
                <div class="mt-4 flex items-center gap-2">
                    <span class="flex items-center gap-0.5 text-xs font-black {{ $salesGrowth >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                        <span class="material-symbols-outlined text-sm">{{ $salesGrowth >= 0 ? 'trending_up' : 'trending_down' }}</span>
                        {{ abs(round($salesGrowth, 1)) }}%
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">vs periode lalu</span>
                </div>
            </div>
        </div>

        <!-- Expense Card -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="relative z-10">
                <div class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-xl">shopping_cart</span>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Pengeluaran</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h3>
                <div class="mt-4 flex items-center gap-2">
                    <span class="flex items-center gap-0.5 text-xs font-black {{ $expenseGrowth <= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                        <span class="material-symbols-outlined text-sm">{{ $expenseGrowth <= 0 ? 'trending_down' : 'trending_up' }}</span>
                        {{ abs(round($expenseGrowth, 1)) }}%
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">vs periode lalu</span>
                </div>
            </div>
        </div>

        <!-- Production Card -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="relative z-10">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-xl">precision_manufacturing</span>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Produksi Selesai</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalProduction, 0, ',', '.') }} <span class="text-sm text-slate-400">Unit</span></h3>
                <p class="text-[10px] text-slate-400 font-medium mt-4">Selama periode ini</p>
            </div>
        </div>

        <!-- Stock Health Card -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group {{ $lowStock > 0 ? 'ring-2 ring-amber-500/20' : '' }}">
            <div class="relative z-10">
                <div class="w-10 h-10 {{ $lowStock > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-xl">{{ $lowStock > 0 ? 'inventory_2' : 'check_circle' }}</span>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Kesehatan Stok</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $stockSafePercent }}% <span class="text-sm text-slate-400">Aman</span></h3>
                <p class="text-[10px] {{ $lowStock > 0 ? 'text-amber-600 font-black' : 'text-emerald-500 font-medium' }} mt-4">
                    {{ $lowStock > 0 ? $lowStock . ' Bahan Baku Kritis' : 'Semua Stok Aman' }}
                </p>
            </div>
        </div>
    </div>

    <!-- MAIN CHART & INFO GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Line Chart -->
        <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
            <h4 class="text-xl font-black text-slate-900 mb-8 font-manrope">Tren Arus Kas</h4>
            <div class="h-[400px]">
                <canvas id="financialTrendChart"></canvas>
            </div>
        </div>

        <!-- Donut Chart -->
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col">
            <h4 class="text-xl font-black text-slate-900 mb-8 font-manrope text-center">Struktur Biaya</h4>
            <div class="flex-1 flex flex-col items-center justify-center relative">
                <div class="h-[250px] w-full">
                    <canvas id="costStructureChart"></canvas>
                </div>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-8">
                    <span class="text-xl font-black text-slate-900">100%</span>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Total HPP</span>
                </div>
            </div>
            <div class="mt-8 space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-500"></span><span class="font-bold text-slate-500">Bahan Baku</span></div>
                    <span class="font-black text-slate-900">Rp {{ number_format($costDist['material'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-500"></span><span class="font-bold text-slate-500">Tenaga Kerja</span></div>
                    <span class="font-black text-slate-900">Rp {{ number_format($costDist['labor'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-indigo-500"></span><span class="font-bold text-slate-500">Overhead</span></div>
                    <span class="font-black text-slate-900">Rp {{ number_format($costDist['overhead'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM SECTION: ACTIVITY & ALERTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pb-10">
        <!-- Recent Activities -->
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-8">
                <h4 class="text-xl font-black text-slate-900 font-manrope">Aktivitas Terbaru</h4>
                <span class="material-symbols-outlined text-slate-300">history</span>
            </div>
            <div class="space-y-6">
                @forelse($recentActivities as $activity)
                    <div class="flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-{{ $activity['color'] }}-50 text-{{ $activity['color'] }}-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined">{{ $activity['icon'] }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900">{{ $activity['title'] }}</p>
                                <p class="text-[10px] text-slate-400 font-medium">{{ $activity['time']->diffForHumans() }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-black text-slate-900 bg-slate-50 px-3 py-1 rounded-lg">{{ $activity['amount'] }}</span>
                    </div>
                @empty
                    <div class="py-10 text-center text-slate-300">
                        <span class="material-symbols-outlined text-4xl mb-2 opacity-20">hourglass_empty</span>
                        <p class="text-xs font-bold">Belum ada aktivitas tercatat</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Stock Alerts -->
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-8">
                <h4 class="text-xl font-black text-slate-900 font-manrope">Peringatan Stok</h4>
                <span class="material-symbols-outlined text-{{ $lowStock > 0 ? 'amber-500' : 'emerald-500' }}">
                    {{ $lowStock > 0 ? 'warning' : 'verified' }}
                </span>
            </div>
            <div class="space-y-4">
                @forelse($lowStockMaterials as $mat)
                    <div class="flex items-center justify-between p-4 bg-amber-50/50 border border-amber-100 rounded-2xl">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white text-amber-600 rounded-xl flex items-center justify-center shadow-sm">
                                <span class="material-symbols-outlined">inventory_2</span>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900">{{ $mat->name }}</p>
                                <p class="text-[10px] text-slate-400 font-medium italic">Sisa Stok: {{ $mat->stock }} {{ $mat->unit }}</p>
                            </div>
                        </div>
                        <a href="{{ route('materials.index') }}" class="text-[10px] font-black text-amber-700 bg-white px-3 py-1.5 rounded-lg border border-amber-200 hover:bg-amber-600 hover:text-white transition-all">
                            Restok
                        </a>
                    </div>
                @empty
                    <div class="h-full flex flex-col items-center justify-center py-10">
                        <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-3xl">done_all</span>
                        </div>
                        <p class="text-sm font-black text-slate-900">Semua stok aman</p>
                        <p class="text-xs text-slate-400 mt-1">Tidak ada bahan baku di bawah batas minimum</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS: Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctxFinancial = document.getElementById('financialTrendChart').getContext('2d');
        const gradSales = ctxFinancial.createLinearGradient(0, 0, 0, 400);
        gradSales.addColorStop(0, 'rgba(16, 185, 129, 0.1)');
        gradSales.addColorStop(1, 'rgba(16, 185, 129, 0)');
        const gradExp = ctxFinancial.createLinearGradient(0, 0, 0, 400);
        gradExp.addColorStop(0, 'rgba(244, 63, 94, 0.1)');
        gradExp.addColorStop(1, 'rgba(244, 63, 94, 0)');

        new Chart(ctxFinancial, {
            type: '{{ $chartType }}',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: @json($chartSales),
                        borderColor: '#10b981',
                        backgroundColor: '{{ $chartType == "bar" ? "#10b981" : "rgba(16, 185, 129, 0.1)" }}',
                        borderWidth: {{ $chartType == "bar" ? 0 : 4 }},
                        fill: true,
                        tension: 0.4,
                        pointRadius: {{ $chartType == "bar" ? 0 : 2 }},
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderRadius: 4, 
                        maxBarThickness: 40,
                    },
                    {
                        label: 'Pengeluaran',
                        data: @json($chartExpenses),
                        borderColor: '#f43f5e',
                        backgroundColor: '{{ $chartType == "bar" ? "#f43f5e" : "rgba(244, 63, 94, 0.1)" }}',
                        borderWidth: {{ $chartType == "bar" ? 0 : 4 }},
                        fill: true,
                        tension: 0.4,
                        pointRadius: {{ $chartType == "bar" ? 0 : 2 }},
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#f43f5e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderRadius: 4,
                        maxBarThickness: 40,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 16,
                        cornerRadius: 16,
                        titleFont: { size: 10, weight: '800' },
                        bodyFont: { size: 14, weight: 'bold' },
                        callbacks: {
                            label: (context) => context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y)
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false }, 
                        ticks: { 
                            font: { weight: 'bold', size: 10 }, 
                            color: '#94a3b8',
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: {{ $currentFilter == '1' ? 8 : 15 }}
                        } 
                    },
                    y: { 
                        grid: { color: 'rgba(226, 232, 240, 0.5)' }, // Subtle light gray (slate-200)
                        ticks: { 
                            font: { weight: 'bold', size: 10 }, 
                            color: '#94a3b8',
                            callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value)
                        },
                        beginAtZero: true
                    }
                }
            }
        });

        const ctxCost = document.getElementById('costStructureChart').getContext('2d');
        new Chart(ctxCost, {
            type: 'doughnut',
            data: {
                labels: ['Bahan Baku', 'Tenaga Kerja', 'Overhead'],
                datasets: [{
                    data: [{{ $costDist['material'] }}, {{ $costDist['labor'] }}, {{ $costDist['overhead'] }}],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#6366f1'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                plugins: { legend: { display: false } }
            }
        });
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap');
    body { font-family: 'Manrope', sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
@endsection
