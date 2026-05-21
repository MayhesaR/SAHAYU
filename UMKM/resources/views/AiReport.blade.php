@extends('layouts.app')
@section('title', 'SAHAYU Assistant')
@section('page_title', 'SAHAYU AI Assistant')

@section('content')
<div class="px-4 py-6 sm:px-8 max-w-full mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="w-full">
            <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 tracking-tight flex items-center gap-3 break-words">
                <span class="material-symbols-outlined text-2xl sm:text-3xl flex-shrink-0" style="color: #0b6e4f;">smart_toy</span>
                SAHAYU Assistant
            </h2>
            <p class="text-on-surface-variant font-body mt-1 max-w-xl text-sm sm:text-base">
                Analisis kesehatan bisnis, deteksi anomali, dan prediksi performa menggunakan kecerdasan buatan.
            </p>
        </div>
        <span class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-200 flex-shrink-0">
            AI-Powered
        </span>
    </div>

    {{-- Month Filter Form --}}
    <form action="{{ route('ai.index') }}" method="GET" class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high p-6">
        <div class="flex items-end gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <label for="filter_month" class="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                    Pilih Periode Analisis
                </label>
                <input type="month"
                       id="filter_month"
                       name="filter_month"
                       value="{{ $filterMonth }}"
                       max="{{ \Carbon\Carbon::now()->format('Y-m') }}"
                       class="w-full bg-surface-container-highest border-none rounded-lg p-3 text-sm font-semibold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 focus:ring-2 focus:ring-emerald-500/20 transition-all" />
            </div>
            <button type="submit"
                    class="px-6 py-3 rounded-lg shadow-sm hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2"
                    style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;">
                <span class="material-symbols-outlined text-base">filter_alt</span>
                <span>Filter Analisis</span>
            </button>
        </div>
    </form>

    {{-- Anomaly Warning Banner (hidden by default, shown by JS) --}}
    <div id="anomaly-banner" class="hidden">
        <div class="rounded-xl border-2 border-red-300 bg-gradient-to-r from-red-50 to-amber-50 p-5 flex items-start gap-4 shadow-sm">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-2xl text-red-600 dark:text-red-400">warning</span>
            </div>
            <div>
                <h3 class="text-base font-black text-red-800 dark:text-red-300 uppercase tracking-wider flex items-center gap-2">
                    <span>⚠️ Anomali Terdeteksi</span>
                </h3>
                <p id="anomaly-reason-text" class="text-sm text-red-700 dark:text-red-400 mt-1 leading-relaxed"></p>
            </div>
        </div>
    </div>

    {{-- Monthly Data Summary Cards --}}
    <div>
        <h3 class="text-xs font-bold text-slate-400 dark:text-zinc-400 uppercase tracking-widest mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">calendar_month</span>
            Data Periode: {{ $monthlyData['period'] }}
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
                <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Pendapatan</p>
                <h3 class="mt-2 text-2xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($monthlyData['total_revenue'], 0, ',', '.') }}</h3>
            </article>
            <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
                <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Total HPP</p>
                <h3 class="mt-2 text-2xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($monthlyData['total_hpp'], 0, ',', '.') }}</h3>
            </article>
            <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
                <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Margin Laba</p>
                <h3 class="mt-2 text-2xl font-extrabold {{ $monthlyData['profit_margin'] >= 20 ? 'text-emerald-700 dark:text-emerald-400' : ($monthlyData['profit_margin'] >= 10 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ number_format($monthlyData['profit_margin'], 1) }}%
                </h3>
            </article>
            <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
                <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Reject Rate</p>
                <h3 class="mt-2 text-2xl font-extrabold {{ $monthlyData['reject_rate'] <= 5 ? 'text-emerald-700 dark:text-emerald-400' : ($monthlyData['reject_rate'] <= 10 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ number_format($monthlyData['reject_rate'], 2) }}%
                </h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">dari {{ number_format($monthlyData['total_produced_units'], 0, ',', '.') }} unit</p>
            </article>
        </div>
    </div>

    {{-- Historical Trend Mini-Table --}}
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800/50 overflow-hidden">
        <div class="px-6 py-4 bg-surface-container-low border-b border-gray-100 dark:border-zinc-800/50 flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-lg flex-shrink-0">timeline</span>
            <h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 uppercase tracking-wider">Tren 3 Bulan Sebelumnya</h3>
        </div>
        <div class="w-full overflow-x-auto overflow-y-hidden border border-gray-100 dark:border-zinc-800/50 rounded-lg mb-4" style="-webkit-overflow-scrolling: touch; display: block; clear: both; touch-action: pan-x pan-y;">
            <table class="min-w-[800px] w-full text-xs text-left whitespace-nowrap">
                <thead>
                    <tr class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-zinc-400 bg-slate-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 dark:bg-transparent whitespace-nowrap">
                        <th class="px-6 py-3 text-left font-bold">Periode</th>
                        <th class="px-6 py-3 text-right font-bold">Revenue</th>
                        <th class="px-6 py-3 text-right font-bold">HPP</th>
                        <th class="px-6 py-3 text-right font-bold">Margin</th>
                        <th class="px-6 py-3 text-right font-bold">Produksi</th>
                        <th class="px-6 py-3 text-right font-bold">Reject Rate</th>
                    </tr>
                </thead>
                <tbody class="text-xs md:text-sm">
                    @foreach (array_reverse($historicalData) as $index => $history)
                    <tr class="border-t border-slate-100 dark:border-zinc-800/60 hover:bg-slate-50/50 dark:hover:bg-zinc-850/30 dark:hover:bg-zinc-800/30 dark:hover:bg-transparent transition-colors whitespace-nowrap">
                        <td class="px-6 py-3 font-semibold text-slate-700 dark:text-zinc-50 dark:text-zinc-200">{{ $history['period'] }}</td>
                        <td class="px-6 py-3 text-right text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 font-medium">Rp {{ number_format($history['revenue'], 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right text-slate-600 dark:text-zinc-300">Rp {{ number_format($history['hpp'], 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold {{ $history['profit_margin'] >= 20 ? 'bg-emerald-50 text-emerald-700 dark:text-emerald-400' : ($history['profit_margin'] >= 10 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                                {{ number_format($history['profit_margin'], 1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right text-slate-700 dark:text-zinc-50 dark:text-zinc-200">{{ number_format($history['produced_units'], 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right font-bold {{ $history['reject_rate'] <= 5 ? 'text-emerald-700 dark:text-emerald-400' : ($history['reject_rate'] <= 10 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ number_format($history['reject_rate'], 2) }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detail Breakdown --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Bahan Baku</p>
            <h3 class="mt-2 text-xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($monthlyData['material_cost'], 0, ',', '.') }}</h3>
        </article>
        <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Overhead</p>
            <h3 class="mt-2 text-xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($monthlyData['overhead_cost'], 0, ',', '.') }}</h3>
        </article>
        <article class="bg-surface-container-lowest rounded-xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800/50 hover:shadow-md transition-all duration-300">
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-zinc-400 font-semibold">Tenaga Kerja</p>
            <h3 class="mt-2 text-xl font-extrabold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">Rp {{ number_format($monthlyData['labor_cost'], 0, ',', '.') }}</h3>
        </article>
    </div>

    {{-- Analysis Action --}}
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high p-8 text-center" id="analysis-trigger-section">
        <div class="mb-6">
            <span class="material-symbols-outlined text-5xl text-emerald-300 mb-4 block">psychology</span>
            <h3 class="text-xl font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 mb-2">Siap untuk Analisis Lanjutan?</h3>
            <p class="text-sm text-slate-500 dark:text-zinc-400 max-w-lg mx-auto">
                AI akan mengaudit data periode <strong>{{ $monthlyData['period'] }}</strong> untuk deteksi anomali,
                mengklasifikasi kesehatan bisnis, dan memprediksi performa bulan berikutnya berdasarkan tren historis.
            </p>
        </div>
        <button id="btn-analyze"
                class="px-8 py-4 rounded-xl shadow-lg hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-3 mx-auto"
                style="background-color: #0b6e4f !important; color: #ffffff !important; font-weight: 900;"
                onclick="runAnalysis()">
            <span class="material-symbols-outlined text-xl">auto_awesome</span>
            <span class="text-base">Analisis Kinerja & Prediksi</span>
        </button>
    </div>

    {{-- Loading State --}}
    <div id="analysis-loading" class="hidden">
        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high p-10 text-center">
            <div class="space-y-6">
                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center">
                        <span class="material-symbols-outlined text-3xl text-emerald-500 animate-spin">progress_activity</span>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 mb-2">AI sedang menganalisis data...</h3>
                    <p class="text-sm text-slate-500 dark:text-zinc-400">Mengaudit anomali, mengklasifikasi kesehatan, dan membangun prediksi.</p>
                </div>
                <div class="max-w-md mx-auto space-y-3">
                    <div class="h-4 bg-slate-200 dark:bg-zinc-800 rounded-full animate-pulse"></div>
                    <div class="h-4 bg-slate-200 dark:bg-zinc-800 rounded-full animate-pulse w-3/4"></div>
                    <div class="h-4 bg-slate-200 dark:bg-zinc-800 rounded-full animate-pulse w-1/2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Error State --}}
    <div id="analysis-error" class="hidden">
        <div class="bg-red-50 dark:bg-red-950/40 rounded-xl border border-red-200 p-6 text-center">
            <span class="material-symbols-outlined text-4xl text-red-400 mb-3 block">error</span>
            <h3 class="text-lg font-bold text-red-800 dark:text-red-300 mb-2">Gagal Menganalisis</h3>
            <p class="text-sm text-red-600 dark:text-red-400" id="error-message">Terjadi kesalahan saat menghubungi AI.</p>
            <button class="mt-4 px-6 py-2 bg-red-100 text-red-700 dark:text-red-400 rounded-lg font-bold text-sm hover:bg-red-200 transition-colors"
                    onclick="resetAnalysis()">
                <span class="material-symbols-outlined text-sm align-middle mr-1">refresh</span> Coba Lagi
            </button>
        </div>
    </div>

    {{-- Result Section --}}
    <div id="analysis-result" class="hidden space-y-6">

        {{-- Classification Badge --}}
        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high p-8" id="classification-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 flex items-center gap-2">
                    <span class="material-symbols-outlined">monitoring</span>
                    Klasifikasi Kesehatan Bisnis
                </h3>
                <div id="classification-badge" class="px-5 py-2 rounded-full text-sm font-black uppercase tracking-widest"></div>
            </div>
        </div>

        {{-- Summary --}}
        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high p-8">
            <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined">summarize</span>
                Ringkasan Performa — {{ $monthlyData['period'] }}
            </h3>
            <div id="summary-text"></div>
        </div>

        {{-- Prediction Card --}}
        <div class="rounded-xl shadow-sm overflow-hidden border-2" style="border-color: #0b6e4f;">
            <div class="px-8 py-4 flex items-center gap-3" style="background-color: #0b6e4f;">
                <span class="material-symbols-outlined text-xl text-white">trending_up</span>
                <h3 class="text-base font-black text-white uppercase tracking-wider">Prediksi Bulan Depan</h3>
            </div>
            <div class="p-8 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-950/20 dark:to-zinc-900/40">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/40 flex items-center justify-center flex-shrink-0 mt-1">
                        <span class="material-symbols-outlined text-2xl text-[#0b6e4f] dark:text-emerald-400">query_stats</span>
                    </div>
                    <div id="prediction-text" class="flex-1"></div>
                </div>
            </div>
        </div>

        {{-- Advice Card --}}
        <div class="rounded-xl p-6 border-l-4 bg-amber-50 dark:bg-amber-950/40 border-amber-400">
            <h4 class="text-sm font-black text-amber-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">lightbulb</span>
                Saran Strategis
            </h4>
            <div id="advice-text"></div>
        </div>

        {{-- Re-analyze Button --}}
        <div class="text-center pt-4">
            <button class="px-6 py-3 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-50 dark:text-white rounded-xl font-bold text-sm hover:bg-slate-200 dark:hover:bg-zinc-800 transition-all flex items-center gap-2 mx-auto"
                    onclick="resetAnalysis()">
                <span class="material-symbols-outlined text-base">refresh</span>
                Analisis Ulang
            </button>
        </div>
    </div>

    {{-- ═══ Interactive Chatbot Section ═══ --}}
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high overflow-hidden" id="chatbot-section">
        {{-- Chat Header --}}
        <div class="px-6 py-4 flex items-center gap-3 border-b border-slate-100 dark:border-zinc-800/60" style="background: linear-gradient(135deg, #0b6e4f 0%, #007070 100%);">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-white text-lg">chat</span>
            </div>
            <div>
                <h3 class="text-sm font-black text-white uppercase tracking-wider">Tanya SAHAYU Assistant</h3>
                <p class="text-[10px] text-emerald-100 font-medium">Tanyakan detail lebih lanjut tentang data {{ $monthlyData['period'] }}</p>
            </div>
            <div class="ml-auto flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] text-emerald-100 font-bold uppercase tracking-wider">Online</span>
            </div>
        </div>

        {{-- Chat Messages Area --}}
        <div id="chatbot-messages" class="p-6 space-y-4 max-h-[420px] overflow-y-auto scroll-smooth min-h-[180px]">
            {{-- Welcome message --}}
            <div class="flex items-start gap-3" id="chatbot-welcome">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #0b6e4f;">
                    <span class="material-symbols-outlined text-white text-sm">smart_toy</span>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-2xl rounded-tl-md px-4 py-3 shadow-sm border border-slate-100 dark:border-zinc-800/60 max-w-[85%]">
                    <p class="text-sm text-slate-700 dark:text-zinc-50 dark:text-zinc-200 leading-relaxed">
                        Halo! 👋 Saya SAHAYU Assistant. Saya siap menjawab pertanyaan Anda seputar data operasional
                        <strong class="text-emerald-800 dark:text-emerald-400">{{ $monthlyData['period'] }}</strong>.
                        Silakan ketik pertanyaan Anda di bawah.
                    </p>
                    <p class="text-[10px] text-slate-400 dark:text-zinc-400 mt-2 font-medium">SAHAYU Assistant</p>
                </div>
            </div>
        </div>

        {{-- Chat Input Area --}}
        <div class="px-6 py-4 border-t border-slate-100 dark:border-zinc-800/60 bg-white dark:bg-zinc-900">
            <form id="chatbot-form" class="flex items-center gap-3" onsubmit="sendChatMessage(event)">
                <div class="flex-1 relative">
                    <input type="text"
                           id="chatbot-input"
                           placeholder="Ketik pertanyaan Anda di sini..."
                           autocomplete="off"
                           maxlength="1000"
                           class="w-full bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl px-4 py-3 pr-12 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition-all" />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-300 font-mono" id="chatbot-char-count">0/1000</span>
                </div>
                <button type="submit"
                        id="chatbot-send-btn"
                        class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm hover:scale-105 active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                        style="background-color: #0b6e4f; color: white;">
                    <span class="material-symbols-outlined text-lg">send</span>
                </button>
            </form>
            <p class="text-[10px] text-slate-400 dark:text-white mt-2 text-center">AI dapat membuat kesalahan. Verifikasi informasi penting secara mandiri.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
    /* Styling khusus untuk format Markdown di dalam chat bubble AI */
    .markdown-body {
        font-size: 0.875rem;
        line-height: 1.6;
    }
    .markdown-body p {
        margin-bottom: 0.5rem;
    }
    .markdown-body p:last-child {
        margin-bottom: 0;
    }
    .markdown-body strong {
        font-weight: 800;
        color: #0b6e4f;
    }
    .dark .markdown-body strong {
        color: #34d399;
    }
    .markdown-body ul {
        list-style-type: disc;
        padding-left: 1.5rem;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .markdown-body li {
        margin-bottom: 0.25rem;
    }
    .markdown-body h3 {
        font-size: 1rem;
        font-weight: 800;
        color: #0b6e4f;
        margin-top: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .dark .markdown-body h3 {
        color: #34d399;
    }
    #chatbot-messages {
        background: linear-gradient(180deg, #f8fffe 0%, #f0fdfa 100%);
    }
    .dark #chatbot-messages {
        background: linear-gradient(180deg, #18181b 0%, #09090b 100%) !important;
    }
</style>
<script>
    // ═══════════════════════════════════════
    // ANALYSIS FUNCTIONS (existing)
    // ═══════════════════════════════════════
    function runAnalysis() {
        document.getElementById('analysis-trigger-section').classList.add('hidden');
        document.getElementById('analysis-loading').classList.remove('hidden');
        document.getElementById('analysis-error').classList.add('hidden');
        document.getElementById('analysis-result').classList.add('hidden');
        document.getElementById('anomaly-banner').classList.add('hidden');

        const filterMonth = document.getElementById('filter_month').value;

        fetch('{{ route("ai.analyze") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ filter_month: filterMonth }),
        })
        .then(response => {
            if (!response.ok) throw response;
            return response.json();
        })
        .then(result => {
            if (!result.success) throw new Error(result.error || 'Unknown error');

            const data = result.data;

            if (data.is_anomaly && data.anomaly_reason) {
                document.getElementById('anomaly-reason-text').textContent = data.anomaly_reason;
                document.getElementById('anomaly-banner').classList.remove('hidden');
            }

            const badge = document.getElementById('classification-badge');
            const status = (data.health_status || '').toLowerCase();
            badge.textContent = data.health_status;

            if (status.includes('sehat') || status.includes('healthy')) {
                badge.className = 'px-5 py-2 rounded-full text-sm font-black uppercase tracking-widest bg-emerald-100 text-emerald-800';
            } else if (status.includes('waspada') || status.includes('warning')) {
                badge.className = 'px-5 py-2 rounded-full text-sm font-black uppercase tracking-widest bg-amber-100 text-amber-800';
            } else if (status.includes('kritis') || status.includes('critical')) {
                badge.className = 'px-5 py-2 rounded-full text-sm font-black uppercase tracking-widest bg-red-100 text-red-800';
            } else {
                badge.className = 'px-5 py-2 rounded-full text-sm font-black uppercase tracking-widest bg-slate-100 text-slate-800';
            }

            const insights = result.calculated_insights;
            const formatRp = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

            // 1. Ringkasan Performa + BEP
            document.getElementById('summary-text').innerHTML = `
                <div class="markdown-body text-slate-700 dark:text-zinc-50 dark:text-zinc-200 leading-relaxed">${marked.parse(data.summary || '')}</div>
                <div class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-zinc-850 dark:bg-zinc-800 rounded-xl text-xs font-bold text-slate-700 dark:text-zinc-50 dark:text-zinc-200 border border-slate-200 dark:border-zinc-800 shadow-sm">
                    <span class="material-symbols-outlined text-base text-emerald-600 dark:text-emerald-400">point_of_sale</span>
                    Target Minimum BEP Harian: <span class="text-emerald-700 dark:text-emerald-400 font-black text-sm ml-1">${formatRp(insights.daily_bep)}</span>
                </div>
            `;

            // 2. Prediksi + Growth Rate
            document.getElementById('prediction-text').innerHTML = `
                <div class="markdown-body text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400 leading-relaxed">${marked.parse(data.prediction || '')}</div>
                <div class="flex items-center gap-3 mt-5 pt-4 border-t border-emerald-100/60 flex-wrap">
                    <div class="px-4 py-2 bg-white dark:bg-zinc-900 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-400 border border-emerald-100 shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-emerald-500">monitoring</span>
                        Prediksi Matematis: <span class="text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">${formatRp(insights.prediction.predicted_revenue)}</span>
                    </div>
                    <div class="px-4 py-2 bg-white dark:bg-zinc-900 rounded-lg text-xs font-bold border border-emerald-100 shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm ${insights.prediction.growth_rate >= 0 ? 'text-emerald-500' : 'text-red-500'}">
                            ${insights.prediction.growth_rate >= 0 ? 'trending_up' : 'trending_down'}
                        </span>
                        Tren: <span class="${insights.prediction.growth_rate >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'}">${insights.prediction.growth_rate}%</span>
                    </div>
                    <div class="px-4 py-2 bg-white dark:bg-zinc-900 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-400 border border-emerald-100 shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-emerald-500">verified</span>
                        Confidence: <span class="text-emerald-900 dark:text-emerald-300 dark:text-emerald-200 dark:text-emerald-400">${insights.prediction.confidence}</span>
                    </div>
                </div>
            `;

            // 3. Saran Strategis + Reject Insight (If Any)
            let adviceHtml = `<div class="markdown-body text-amber-900 dark:text-amber-200 leading-relaxed">${marked.parse(data.advice || '')}</div>`;
            if (insights.reject_insight) {
                const ri = insights.reject_insight;
                adviceHtml += `
                    <div class="mt-5 p-5 bg-white/70 dark:bg-zinc-900/60 border border-amber-200 dark:border-amber-900/40 rounded-xl shadow-sm">
                        <h5 class="text-[10px] font-black text-amber-900 dark:text-amber-200 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-red-500">warning</span> Action Required: Evaluasi Produksi
                        </h5>
                        <p class="text-sm text-amber-800 dark:text-amber-300 mb-4">Produk <strong class="bg-amber-100 dark:bg-amber-950/60 px-1.5 py-0.5 rounded text-amber-900 dark:text-amber-200">${ri.product_name}</strong> menyumbang Reject Rate tertinggi sebesar <strong class="text-red-600 dark:text-red-400">${ri.reject_rate}%</strong>.</p>
                        <div class="flex items-center gap-6 text-xs font-bold bg-amber-50 dark:bg-amber-950/40 p-4 rounded-xl border border-amber-100/60 dark:border-amber-900/40">
                            <div class="flex flex-col gap-1">
                                <span class="text-amber-700/60 dark:text-amber-400/60 uppercase text-[9px] tracking-wider">Estimasi Total Kerugian</span>
                                <span class="text-red-600 dark:text-red-400 font-black text-base">${formatRp(ri.lost_value)}</span>
                            </div>
                            <div class="w-px h-10 bg-amber-200/50"></div>
                            <div class="flex flex-col gap-1">
                                <span class="text-amber-700/60 dark:text-amber-400/60 uppercase text-[9px] tracking-wider">Potensi Penyelamatan Margin (Target 3%)</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-black text-base flex items-center gap-1">
                                    ${formatRp(ri.potential_savings)}
                                    <span class="material-symbols-outlined text-sm">savings</span>
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }
            document.getElementById('advice-text').innerHTML = adviceHtml;

            document.getElementById('analysis-loading').classList.add('hidden');
            document.getElementById('analysis-result').classList.remove('hidden');
        })
        .catch(async (err) => {
            let msg = 'Terjadi kesalahan saat menghubungi AI.';
            if (err instanceof Response) {
                try {
                    const errData = await err.json();
                    msg = errData.error || msg;
                } catch (e) {}
            } else if (err.message) {
                msg = err.message;
            }

            document.getElementById('error-message').textContent = msg;
            document.getElementById('analysis-loading').classList.add('hidden');
            document.getElementById('analysis-error').classList.remove('hidden');
        });
    }

    function resetAnalysis() {
        document.getElementById('analysis-trigger-section').classList.remove('hidden');
        document.getElementById('analysis-loading').classList.add('hidden');
        document.getElementById('analysis-error').classList.add('hidden');
        document.getElementById('analysis-result').classList.add('hidden');
        document.getElementById('anomaly-banner').classList.add('hidden');
    }

    // ═══════════════════════════════════════
    // CHATBOT FUNCTIONS
    // ═══════════════════════════════════════
    const chatMessages = document.getElementById('chatbot-messages');
    const chatInput = document.getElementById('chatbot-input');
    const chatSendBtn = document.getElementById('chatbot-send-btn');
    const chatCharCount = document.getElementById('chatbot-char-count');
    let chatBusy = false;

    // Character counter
    chatInput.addEventListener('input', () => {
        chatCharCount.textContent = `${chatInput.value.length}/1000`;
    });

    function sendChatMessage(event) {
        event.preventDefault();
        const message = chatInput.value.trim();
        if (!message || chatBusy) return;

        chatBusy = true;
        chatSendBtn.disabled = true;
        chatInput.disabled = true;

        // Append user message bubble
        appendMessage('user', message);
        chatInput.value = '';
        chatCharCount.textContent = '0/1000';

        // Show typing indicator
        const typingId = showTypingIndicator();

        // Send to backend
        const filterMonth = document.getElementById('filter_month').value;

        fetch('{{ route("ai.chatbot") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                filter_month: filterMonth,
            }),
        })
        .then(response => response.json())
        .then(data => {
            removeTypingIndicator(typingId);
            appendMessage('ai', data.reply || 'Maaf, tidak ada respons dari AI.');
        })
        .catch(err => {
            removeTypingIndicator(typingId);
            appendMessage('ai', 'Maaf, terjadi kesalahan saat menghubungi AI. Silakan coba lagi.', true);
        })
        .finally(() => {
            chatBusy = false;
            chatSendBtn.disabled = false;
            chatInput.disabled = false;
            chatInput.focus();
        });
    }

    function appendMessage(type, text, isError = false) {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-start gap-3' + (type === 'user' ? ' flex-row-reverse' : '');
        wrapper.style.opacity = '0';
        wrapper.style.transform = 'translateY(12px)';
        wrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';

        if (type === 'user') {
            wrapper.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-slate-600 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-white text-sm">person</span>
                </div>
                <div class="bg-slate-700 text-white dark:bg-zinc-800 dark:text-zinc-200 dark:border dark:border-zinc-700 rounded-2xl rounded-tr-md px-4 py-3 shadow-sm max-w-[85%]">
                    <p class="text-sm leading-relaxed">${escapeHtml(text)}</p>
                    <p class="text-[10px] text-slate-300 mt-2 font-medium text-right">Anda</p>
                </div>
            `;
        } else {
            const bgClass = isError
                ? 'bg-red-50 border-red-200 dark:bg-red-950/20 dark:border-red-900/50'
                : 'bg-white border-slate-100 dark:bg-zinc-900 dark:border-zinc-800/60';
            const textClass = isError ? 'text-red-700 dark:text-red-400' : 'text-slate-700 dark:text-zinc-200';
            
            // Parse teks menggunakan marked.js (jika error, hindari markdown agar pesan error raw tampil jelas)
            const parsedText = isError ? escapeHtml(text) : marked.parse(text);

            wrapper.innerHTML = `
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #0b6e4f;">
                    <span class="material-symbols-outlined text-white text-sm">smart_toy</span>
                </div>
                <div class="${bgClass} rounded-2xl rounded-tl-md px-4 py-3 shadow-sm border max-w-[85%] overflow-hidden">
                    <div class="${textClass} markdown-body">${parsedText}</div>
                    <p class="text-[10px] text-slate-400 dark:text-zinc-400 mt-2 font-medium">SAHAYU Assistant</p>
                </div>
            `;
        }

        chatMessages.appendChild(wrapper);
        // Trigger animation
        requestAnimationFrame(() => {
            wrapper.style.opacity = '1';
            wrapper.style.transform = 'translateY(0)';
        });
        scrollChatToBottom();
    }

    function showTypingIndicator() {
        const id = 'typing-' + Date.now();
        const wrapper = document.createElement('div');
        wrapper.id = id;
        wrapper.className = 'flex items-start gap-3';
        wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #0b6e4f;">
                <span class="material-symbols-outlined text-white text-sm animate-pulse">smart_toy</span>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl rounded-tl-md px-5 py-3 shadow-sm border border-slate-100 dark:border-zinc-800/60">
                <div class="flex items-center gap-2">
                    <div class="flex gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-bounce" style="animation-delay: 0ms;"></span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-bounce" style="animation-delay: 150ms;"></span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-bounce" style="animation-delay: 300ms;"></span>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-zinc-400 font-medium italic">SAHAYU Assistant sedang mengetik...</span>
                </div>
            </div>
        `;
        chatMessages.appendChild(wrapper);
        scrollChatToBottom();
        return id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function scrollChatToBottom() {
        requestAnimationFrame(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Allow Enter key to submit (Shift+Enter for newline is not needed for single-line input)
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatbot-form').dispatchEvent(new Event('submit'));
        }
    });
</script>
@endsection
