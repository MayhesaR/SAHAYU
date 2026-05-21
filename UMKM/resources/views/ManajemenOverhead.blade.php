@extends('layouts.app')
@section('title', 'Biaya Operasional')
@section('page_title', 'Manajemen Biaya Operasional')

@section('content')
<div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1 w-full">
            <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold tracking-tight text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 break-words">Biaya Operasional (Overhead)</h2>
            <p class="text-sm sm:text-base text-on-surface-variant font-body">Pantau pengeluaran bulanan Anda untuk perhitungan HPP yang lebih akurat.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <form action="{{ route('overhead.index') }}" method="GET" class="flex-1 sm:flex-none flex items-center gap-2 bg-surface-container-low p-1.5 rounded-xl border border-gray-100 dark:border-zinc-800/50">
                <select name="month" class="bg-transparent border-none text-xs font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 focus:ring-0 cursor-pointer">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="year" class="bg-transparent border-none text-xs font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 focus:ring-0 cursor-pointer">
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 hover:bg-emerald-100 rounded-lg text-emerald-700 dark:text-emerald-400 transition-colors text-[10px] font-black uppercase flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm flex-shrink-0">refresh</span>
                    Terapkan
                </button>
            </form>

            <!-- Export Excel Button -->
            <a href="{{ route('overhead.export', request()->all()) }}" 
               class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/30 hover:scale-[1.02] active:scale-95 transition-all">
                <span class="material-symbols-outlined text-base flex-shrink-0">download</span>
                <span>Ekspor Excel</span>
            </a>

            @if(auth()->user()->isAdmin())
            <button class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-[#0b6e4f] dark:bg-emerald-600 text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/30 hover:bg-[#09523b] hover:scale-[1.02] active:scale-95 transition-all" id="open-overhead-form">
                <span class="material-symbols-outlined text-base flex-shrink-0">add_circle</span>
                <span>Catat Biaya</span>
            </button>
            @endif
        </div>
    </div>

    @if (session('success'))
    <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 dark:text-emerald-300 border border-emerald-100 px-4 py-3 text-sm font-medium animate-in fade-in slide-in-from-top-4">
        {{ session('success') }}
    </div>
    @endif

    <!-- Stats Bento -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="md:col-span-1 bg-surface-container-lowest p-6 rounded-xl border border-gray-100 dark:border-zinc-800/50 shadow-sm hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-zinc-400 mb-2">Total Biaya {{ $months[$selectedMonth] }}</p>
            <h3 class="text-2xl font-black text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 leading-none">Rp {{ number_format($totalOverhead, 0, ',', '.') }}</h3>
            <p class="text-[10px] text-slate-400 dark:text-zinc-400 mt-2 italic">*Digunakan untuk pembagi HPP</p>
        </div>
        <div class="md:col-span-3 bg-surface-container-lowest p-6 rounded-xl border border-gray-100 dark:border-zinc-800/50 shadow-sm hover:shadow-md transition-all duration-300">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-white mb-4 text-center md:text-left">Alokasi Biaya Berdasarkan Kategori</p>
            <div class="flex items-center gap-4 flex-wrap">
                @forelse($categoryBreakdown as $cat)
                <div class="flex-1 min-w-[140px] bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 p-3 rounded-lg border border-slate-100 dark:border-zinc-800/60">
                    <p class="text-[9px] font-black uppercase text-slate-500 dark:text-zinc-400 mb-1 truncate">{{ $cat->category }}</p>
                    <p class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($cat->total, 0, ',', '.') }}</p>
                </div>
                @empty
                <p class="text-xs text-slate-400 dark:text-zinc-400 italic">Belum ada data kategori.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Sidebar Form -->
    <aside id="overhead-sidebar" class="fixed right-0 top-0 h-screen w-96 bg-white dark:bg-zinc-900 shadow-2xl transform translate-x-full transition-transform duration-300 z-50 overflow-y-auto">
        <div class="p-6 bg-surface-container-low border-b border-outline-variant/5 flex justify-between items-center sticky top-0 z-10">
            <h3 class="font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 flex items-center gap-2">
                <span class="material-symbols-outlined">account_balance_wallet</span> Catat Biaya Operasional
            </h3>
            <button id="close-overhead-form" class="text-slate-400 dark:text-zinc-400 hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form action="{{ route('overhead.store') }}" method="POST" class="space-y-5" x-data="{
                rawCost: '',
                displayCost: '',
                updateCost(val) {
                    let raw = val.replace(/\D/g, '');
                    this.rawCost = raw ? parseInt(raw) : '';
                    this.displayCost = this.rawCost ? new Intl.NumberFormat('id-ID').format(this.rawCost) : '';
                }
            }">
                @csrf
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-zinc-400 ml-1">Kategori Biaya</label>
                    <select name="category" required class="w-full bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20">
                        <option value="Biaya Tetap (Sewa/Gaji)">Biaya Tetap (Sewa/Gaji)</option>
                        <option value="Utilitas (Listrik/Air/Gas)">Utilitas (Listrik/Air/Gas)</option>
                        <option value="Pemasaran/Iklan">Pemasaran/Iklan</option>
                        <option value="Pemeliharaan Alat">Pemeliharaan Alat</option>
                        <option value="Transportasi">Transportasi</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-zinc-400 ml-1">Nama Pengeluaran</label>
                    <input name="name" required class="w-full bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20" placeholder="Contoh: Listrik Ruko Januari"/>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-zinc-400 ml-1">Tanggal Pembayaran</label>
                    <input name="transaction_date" type="date" required value="{{ now()->toDateString() }}" class="w-full bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20"/>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-zinc-400 ml-1">Nominal Biaya (Rp)</label>
                    <input id="cost_display" 
                           x-model="displayCost"
                           @input="updateCost($event.target.value)"
                           type="text" 
                           required 
                           class="w-full bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 text-stone-850 dark:text-white font-bold font-mono" 
                           placeholder="0"/>
                    <input type="hidden" name="cost" :value="rawCost" />
                </div>
                <div class="pt-4">
                    <button class="w-full py-4 rounded-xl shadow-lg shadow-emerald-900/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" 
                             style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;" 
                             type="submit">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>Simpan Pengeluaran</span>
                    </button>
                </div>
            </form>
        </div>
    </aside>

    <!-- Table -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden border border-gray-100 dark:border-zinc-800/50 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="px-8 py-5 bg-surface-container-high/50 border-b border-outline-variant/5">
            <h4 class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Rincian Pengeluaran Periode {{ $months[$selectedMonth] }} {{ $selectedYear }}</h4>
        </div>
        <div class="w-full overflow-x-auto border border-gray-100 dark:border-zinc-800/50 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
            <table class="min-w-[800px] w-full text-xs text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant">
                        <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Tanggal</th>
                        <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Kategori</th>
                        <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Nama Biaya</th>
                        <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest">Nominal</th>
                        <th class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/40">
                    @forelse ($overheadCosts as $cost)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-zinc-850/80 dark:hover:bg-zinc-800/80 dark:hover:bg-zinc-950/80 transition-colors group">
                        <td class="px-8 py-5 text-xs font-bold text-slate-500 dark:text-zinc-400">{{ $cost->transaction_date->translatedFormat('d M Y') }}</td>
                        <td class="px-8 py-5">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-slate-600 dark:text-zinc-300">
                                {{ $cost->category }}
                            </span>
                        </td>
                        <td class="px-8 py-5 font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-250 text-sm">{{ $cost->name }}</td>
                        <td class="px-8 py-5 font-black text-sm">Rp {{ number_format($cost->cost, 0, ',', '.') }}</td>
                        <td class="px-8 py-5 text-right">
                            @if(auth()->user()->isAdmin())
                            <form action="{{ route('overhead.destroy', $cost) }}" method="POST" onsubmit="return confirm('Hapus data pengeluaran ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 hover:bg-red-100 transition-colors flex items-center gap-1" type="submit">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                    <span>Hapus</span>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                                <span class="material-symbols-outlined text-4xl text-stone-400 dark:text-zinc-500 font-light">account_balance_wallet</span>
                                <p class="font-bold text-stone-700 dark:text-zinc-200">Belum ada biaya operasional</p>
                                <p class="text-xs text-stone-400 dark:text-zinc-500">Tidak ada data biaya operasional untuk periode ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const sidebar = document.getElementById('overhead-sidebar');
    const openBtn = document.getElementById('open-overhead-form');
    const closeBtn = document.getElementById('close-overhead-form');

    if(openBtn) openBtn.addEventListener('click', () => sidebar.classList.remove('translate-x-full'));
    if(closeBtn) closeBtn.addEventListener('click', () => sidebar.classList.add('translate-x-full'));

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (sidebar && !sidebar.contains(e.target) && !openBtn.contains(e.target)) {
            sidebar.classList.add('translate-x-full');
        }
    });
</script>
@endsection
