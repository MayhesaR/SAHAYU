<!DOCTYPE html>
<html lang="id" class="scroll-smooth" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', val => { localStorage.setItem('theme', val ? 'dark' : 'light'); if (val) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); } })">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SAHAYU - Sistem Kasir & Analisis HPP Pintar UMKM Kuliner</title>

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        h1, h2, h3, h4 {
            font-family: 'Manrope', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>

    @vite(['resources/css/app.css'])
</head>
<body class="bg-stone-50 dark:bg-zinc-900 text-stone-800 dark:text-zinc-100 antialiased selection:bg-emerald-500/20 selection:text-emerald-950 dark:selection:text-emerald-300">

    <!-- PREMIUM NAVIGATION HEADER -->
    <header class="sticky top-0 z-50 backdrop-blur-md bg-stone-50/80 dark:bg-zinc-900/80 border-b border-stone-200/40 dark:border-zinc-850/40 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <!-- Logo -->
            <a href="#" class="flex items-center gap-2 group">
                <img src="{{ asset('logo.png') }}" alt="SAHAYU Logo" class="w-10 h-10 rounded-xl shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-all flex-shrink-0 object-cover" />
                <span class="text-xl font-extrabold tracking-tight text-stone-800 dark:text-zinc-100">SAHAYU</span>
            </a>

            <!-- Navigation Links (Mocked) -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-stone-600 dark:text-zinc-400">
                <a href="#fitur" class="hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors">Fitur</a>
                <a href="#solusi" class="hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors">Solusi</a>
                <a href="#harga" class="hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors">Harga</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-4">
                <!-- Theme Toggle Button -->
                <button @click="darkMode = !darkMode"
                        class="p-2.5 rounded-xl bg-stone-200/50 dark:bg-zinc-800/60 text-stone-600 dark:text-zinc-300 hover:scale-105 active:scale-95 transition-all shadow-sm border border-stone-200/20 dark:border-zinc-800/40"
                        title="Ubah Tema">
                    <span x-show="!darkMode" class="material-symbols-outlined block text-xl">dark_mode</span>
                    <span x-show="darkMode" class="material-symbols-outlined block text-xl">light_mode</span>
                </button>

                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 px-5 rounded-xl shadow-md shadow-emerald-500/20 transition-all duration-200 hover:-translate-y-0.5 active:scale-95 text-sm">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 px-5 rounded-xl shadow-md shadow-emerald-500/20 transition-all duration-200 hover:-translate-y-0.5 active:scale-95 text-sm">
                            Masuk ke Aplikasi
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- HERO SECTION (The Hook) -->
    <section class="max-w-7xl mx-auto px-6 py-16 md:py-24 grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <!-- Left Side: Hero Content -->
        <div class="space-y-8 text-left">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
                <span class="material-symbols-outlined text-sm">verified</span>
                Solusi Kasir & HPP Kuliner Terpercaya
            </div>

            <h1 class="text-stone-800 dark:text-zinc-100 text-5xl font-extrabold leading-tight line-height-tight tracking-tight">
                Sistem Kasir & Analisis HPP Pintar untuk UMKM Kuliner Tangguh.
            </h1>

            <p class="text-base md:text-lg text-stone-500 dark:text-zinc-400 leading-relaxed max-w-xl">
                Lacak setiap rupiah arus kas, sederhanakan perhitungan yield otomatis untuk bahan baku adonan, dan hasilkan laporan laba-rugi siap audit tanpa beban teknis yang membingungkan.
            </p>

            <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 bg-emerald-500 shadow-md shadow-emerald-500/20 rounded-2xl text-lg font-bold py-4 px-8 text-white hover:bg-emerald-600 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                        Masuk ke Dashboard <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 bg-emerald-500 shadow-md shadow-emerald-500/20 rounded-2xl text-lg font-bold py-4 px-8 text-white hover:bg-emerald-600 hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                        Mulai Kelola Bisnis Gratis <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </a>
                @endauth
                <a href="#fitur"
                   class="inline-flex items-center justify-center gap-2 border border-stone-200 dark:border-zinc-800 bg-white dark:bg-zinc-850 hover:bg-stone-50 dark:hover:bg-zinc-800 text-stone-700 dark:text-zinc-200 font-bold py-4 px-8 rounded-2xl shadow-sm hover:shadow transition-all duration-200 active:scale-95">
                    Pelajari Fitur
                </a>
            </div>

            <!-- Trust Badges -->
            <div class="pt-6 border-t border-stone-200/60 dark:border-zinc-800/60 flex flex-wrap items-center gap-6 text-stone-400 dark:text-zinc-500 text-xs font-semibold">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check_circle</span> 100% Data Aman Terenkripsi
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check_circle</span> Ekspor Laporan PDF & Excel
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check_circle</span> Ramah Layar Sentuh HP & Tablet
                </div>
            </div>
        </div>

        <!-- Right Side: Simulated Dashboard Mockup -->
        <div class="relative w-full max-w-lg mx-auto bg-stone-100 dark:bg-zinc-850 rounded-3xl p-4 shadow-2xl border border-stone-200/50 dark:border-zinc-800/80 transition-all">
            <!-- Browser Header -->
            <div class="flex items-center justify-between pb-3 border-b border-stone-200/60 dark:border-zinc-800/50 mb-4">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-rose-400"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                </div>
                <div class="bg-white dark:bg-zinc-900 px-4 py-1 rounded-full text-[10px] text-stone-400 dark:text-zinc-500 font-mono select-none w-1/2 text-center truncate">
                    app.sahayu.com/dashboard
                </div>
                <div class="w-10"></div>
            </div>

            <!-- Dashboard Mockup UI Content -->
            <div class="space-y-4">
                <!-- Shop Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[9px] text-stone-400 dark:text-zinc-500 uppercase font-extrabold tracking-wider leading-none">Dashboard Ringkasan</p>
                        <h4 class="text-xs font-bold text-stone-800 dark:text-zinc-100 mt-1">SAHAYU Bakery & Co</h4>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 text-[9px] font-bold flex items-center gap-1 shadow-sm">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping"></span>
                        Sistem Aktif
                    </span>
                </div>

                <!-- Big Cards Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white dark:bg-zinc-900 p-3.5 rounded-2xl border border-stone-200/20 dark:border-zinc-800/40 shadow-sm">
                        <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-500 uppercase tracking-wide">Omzet Penjualan</p>
                        <p class="text-sm font-extrabold text-stone-800 dark:text-zinc-100 mt-1">Rp 2.450.000</p>
                        <span class="text-[8px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5 mt-1">
                            ▲ +12.4% vs kemarin
                        </span>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 p-3.5 rounded-2xl border border-stone-200/20 dark:border-zinc-800/40 shadow-sm">
                        <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-500 uppercase tracking-wide">Bahan Baku Menipis</p>
                        <p class="text-sm font-extrabold text-stone-800 dark:text-zinc-100 mt-1">0 Item</p>
                        <span class="text-[8px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5 mt-1">
                            ✓ Semua stok aman
                        </span>
                    </div>
                </div>

                <!-- Yield and Recipe Margins -->
                <div class="bg-white dark:bg-zinc-900 p-3.5 rounded-2xl border border-stone-200/20 dark:border-zinc-800/40 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-500 uppercase tracking-wide">Margin HPP Terkalkulasi</p>
                        <span class="text-[8px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-950 px-1.5 py-0.5 rounded">Sehat (62%)</span>
                    </div>
                    <!-- Bars -->
                    <div class="space-y-2.5">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[8.5px] text-stone-600 dark:text-zinc-300 font-semibold">
                                <span>Roti Tawar Bandung</span>
                                <span class="font-bold text-stone-800 dark:text-zinc-100">Rp 12.000 / Box (65% Margin)</span>
                            </div>
                            <div class="w-full bg-stone-100 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full" style="width: 65%;"></div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[8.5px] text-stone-600 dark:text-zinc-300 font-semibold">
                                <span>Donat Kentang Gula</span>
                                <span class="font-bold text-stone-800 dark:text-zinc-100">Rp 4.500 / Pcs (58% Margin)</span>
                            </div>
                            <div class="w-full bg-stone-100 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full" style="width: 58%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Transaction feed -->
                <div class="bg-white dark:bg-zinc-900 p-3 rounded-2xl border border-stone-200/20 dark:border-zinc-800/40 shadow-sm">
                    <p class="text-[9px] font-bold text-stone-400 dark:text-zinc-500 uppercase tracking-wide mb-2">Penjualan Terbaru</p>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-[9px] py-1 border-b border-stone-50 dark:border-zinc-800/20">
                            <span class="font-bold text-stone-700 dark:text-zinc-200">#TRX-0091</span>
                            <span class="text-stone-500 dark:text-zinc-400">Roti Tawar x3</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">Rp 36.000</span>
                        </div>
                        <div class="flex items-center justify-between text-[9px] py-1">
                            <span class="font-bold text-stone-700 dark:text-zinc-200">#TRX-0090</span>
                            <span class="text-stone-500 dark:text-zinc-400">Donat Kentang x10</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">Rp 45.000</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CORE FEATURES GRID (The Proof) -->
    <section id="fitur" class="bg-stone-100/60 dark:bg-zinc-900/40 py-20 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-6 text-center space-y-16">
            <!-- Header -->
            <div class="max-w-2xl mx-auto space-y-4">
                <span class="text-xs font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-widest">Kekuatan Utama SAHAYU</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-stone-800 dark:text-zinc-100 tracking-tight">
                    Fitur Unggulan untuk UMKM Kuliner Tangguh
                </h2>
                <p class="text-sm md:text-base text-stone-500 dark:text-zinc-400 max-w-lg mx-auto">
                    Kembangkan bisnis kuliner Anda dengan alat pembukuan modern yang dirancang khusus untuk kenyamanan dapur dan efisiensi kasir.
                </p>
            </div>

            <!-- Alternating Z-Pattern Features Sections -->
            <div class="space-y-24 text-left">
                <!-- ROW 1 (Fitur Kasir POS) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
                            <span class="material-symbols-outlined text-sm">point_of_sale</span>
                            Fitur Kasir POS
                        </div>
                        <h3 class="text-3xl font-bold text-stone-800 dark:text-zinc-100 font-manrope leading-tight">
                            Kasir POS Kuliner Pintar
                        </h3>
                        <p class="text-stone-500 dark:text-zinc-400 leading-relaxed text-sm md:text-base">
                            Pencatatan transaksi penjualan secara real-time yang didesain khusus untuk kecepatan operasional toko kuliner. Dilengkapi dengan sistem input masking Rupiah otomatis untuk mencegah kesalahan input nominal kasir.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Mendukung Multi-Metode Pembayaran (Tunai, Transfer, QRIS)
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Pencatatan Pesanan Instan & Responsif
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <!-- Browser Frame -->
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl border border-stone-200/60 dark:border-zinc-700/50 overflow-hidden transition-all duration-500 hover:scale-[1.02] hover:shadow-emerald-950/10">
                            <div class="bg-stone-100 dark:bg-zinc-800 px-4 py-2.5 border-b border-stone-200/60 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#f87171]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#fbbf24]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#34d399]"></span>
                            </div>
                            <img src="{{ asset('images/landing/pos-screen.jpg') }}" alt="Kasir POS Kuliner Pintar" class="w-full h-auto object-cover" />
                        </div>
                    </div>
                </div>

                <!-- ROW 2 (Dashboard Bisnis Utama) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-7 order-last lg:order-first">
                        <!-- Browser Frame -->
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl border border-stone-200/60 dark:border-zinc-700/50 overflow-hidden transition-all duration-500 hover:scale-[1.02] hover:shadow-emerald-950/10">
                            <div class="bg-stone-100 dark:bg-zinc-800 px-4 py-2.5 border-b border-stone-200/60 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#f87171]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#fbbf24]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#34d399]"></span>
                            </div>
                            <img src="{{ asset('images/landing/dashboard-sceen.jpg') }}" alt="Dashboard Analitik & Ringkasan Performa" class="w-full h-auto object-cover" />
                        </div>
                    </div>
                    <div class="lg:col-span-5 space-y-6">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
                            <span class="material-symbols-outlined text-sm">dashboard</span>
                            Analitik Bisnis
                        </div>
                        <h3 class="text-3xl font-bold text-stone-800 dark:text-zinc-100 font-manrope leading-tight">
                            Dashboard Analitik & Ringkasan Performa
                        </h3>
                        <p class="text-stone-500 dark:text-zinc-400 leading-relaxed text-sm md:text-base">
                            Pantau kesehatan keuangan usaha Anda dalam satu layar. Analisis grafik pendapatan harian, pengeluaran operasional, serta kalkulasi laba bersih otomatis secara akurat tanpa perlu rekap manual.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Metrik Ringkasan Finansial Real-time
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Grafik Tren Penjualan Interaktif
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Navigasi Menu yang Bersih dan Modern
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ROW 3 (Buku Kas & Piutang Digital) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
                            <span class="material-symbols-outlined text-sm">receipt_long</span>
                            Buku Kas & Piutang
                        </div>
                        <h3 class="text-3xl font-bold text-stone-800 dark:text-zinc-100 font-manrope leading-tight">
                            Manajemen Kasbon & Pengingat Jatuh Tempo
                        </h3>
                        <p class="text-stone-500 dark:text-zinc-400 leading-relaxed text-sm md:text-base">
                            Kelola piutang pelanggan dengan antarmuka split-screen ala mesin kasir modern. Lacak sisa tagihan, catat cicilan kasbon tanpa reload halaman, dan pantau tanggal jatuh tempo secara otomatis.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Notifikasi Otomatis Tagihan Jatuh Tempo
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Pilih Invoice Belum Lunas Secara Spesifik
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-stone-700 dark:text-zinc-300 font-medium">
                                <span class="material-symbols-outlined text-emerald-500 text-lg font-bold">check_circle</span>
                                Pencatatan Riwayat Cicilan yang Akurat
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <!-- Browser Frame -->
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl border border-stone-200/60 dark:border-zinc-700/50 overflow-hidden transition-all duration-500 hover:scale-[1.02] hover:shadow-emerald-950/10">
                            <div class="bg-stone-100 dark:bg-zinc-800 px-4 py-2.5 border-b border-stone-200/60 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#f87171]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#fbbf24]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#34d399]"></span>
                            </div>
                            <img src="{{ asset('images/landing/piutang-screen.jpg') }}" alt="Manajemen Kasbon & Pengingat Jatuh Tempo" class="w-full h-auto object-cover" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SOLUSI SECTION (How It Works) -->
    <section id="solusi" class="max-w-7xl mx-auto px-6 py-20 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div class="space-y-6">
            <span class="text-xs font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-widest">Alur Kerja Praktis</span>
            <h2 class="text-3xl font-extrabold text-stone-800 dark:text-zinc-100 leading-tight">
                Bagaimana SAHAYU Mengoptimalkan Dapur Produksi Anda?
            </h2>
            <p class="text-sm text-stone-500 dark:text-zinc-400 leading-relaxed">
                Mulai dari pencatatan bahan baku mentah di dapur hingga hidangan siap disajikan, SAHAYU menyederhanakan pembukuan usaha kuliner Anda menjadi serba otomatis.
            </p>
            <div class="space-y-4 pt-2">
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5 shadow-sm">1</div>
                    <div>
                        <h4 class="text-xs font-bold text-stone-800 dark:text-white">Atur Resep & Hitung Modal Otomatis</h4>
                        <p class="text-[11px] text-stone-400 dark:text-zinc-400">Masukkan takaran resep atau porsi menu Anda. Sistem akan otomatis menghitung modal asli (HPP) secara presisi setelah memperhitungkan penyusutan bahan baku saat diolah.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5 shadow-sm">2</div>
                    <div>
                        <h4 class="text-xs font-bold text-stone-800 dark:text-white">Pencatatan Stok & Produksi Hidangan</h4>
                        <p class="text-[11px] text-stone-400 dark:text-zinc-400">Setiap kali dapur menyiapkan menu makanan atau minuman baru, sisa stok bahan baku di dalam gudang penyimpanan akan otomatis berkurang rapi tanpa perlu hitung manual.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5 shadow-sm">3</div>
                    <div>
                        <h4 class="text-xs font-bold text-stone-800 dark:text-white">Kasir Digital & Catat Kasbon Instan</h4>
                        <p class="text-[11px] text-stone-400 dark:text-zinc-400">Layani pesanan pelanggan dengan cepat menggunakan aplikasi kasir digital. Jika ada konsumen yang kasbon, nominal tagihan langsung tersimpan aman di buku utang digital.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-center">
            <div class="bg-gradient-to-tr from-emerald-500/10 to-teal-500/5 dark:from-emerald-950/20 dark:to-teal-950/10 p-8 rounded-3xl border border-emerald-500/10 max-w-md w-full relative overflow-hidden flex flex-col items-center justify-center text-center space-y-6 py-12 shadow-inner">
                <span class="material-symbols-outlined text-emerald-500 text-6xl animate-bounce">rocket_launch</span>
                <h3 class="text-lg font-bold text-stone-800 dark:text-white">Siap Naik Kelas Digital?</h3>
                <p class="text-xs text-stone-500 dark:text-zinc-400">Tanpa ribet instalasi aplikasi, berjalan optimal langsung dari browser HP maupun laptop kesayangan Anda.</p>
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 px-6 rounded-2xl shadow-md shadow-emerald-500/20 transition-all duration-200">
                        Masuk ke Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 px-6 rounded-2xl shadow-md shadow-emerald-500/20 transition-all duration-200">
                        Buat Akun Gratis Sekarang
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- PRICING SECTION (Harga Jujur) -->
    <section id="harga" class="bg-stone-100/60 dark:bg-zinc-900/40 py-20 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-6 text-center space-y-12">
            <div class="max-w-xl mx-auto space-y-3">
                <span class="text-xs font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-widest font-extrabold">Harga</span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-stone-800 dark:text-zinc-100 tracking-tight">Rencana Harga yang Sederhana & Transparan</h2>
                <p class="text-xs text-stone-500 dark:text-zinc-400">Akses penuh ke seluruh ekosistem fitur tanpa ada biaya tersembunyi.</p>
            </div>

            <div class="max-w-sm mx-auto bg-white dark:bg-zinc-800 rounded-3xl p-8 shadow-xl shadow-emerald-900/5 border border-emerald-500/20 relative">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-emerald-500 text-white text-[10px] font-extrabold uppercase tracking-wider shadow-md">
                    Promo Spesial UMKM
                </div>
                <h3 class="text-md font-bold text-stone-800 dark:text-white mt-2">SAHAYU Premium</h3>
                <div class="my-6">
                    <span class="text-3xl font-extrabold text-stone-800 dark:text-zinc-100">Gratis</span>
                    <span class="text-stone-400 dark:text-zinc-500 text-xs">/ selamanya (Masa Promosi)</span>
                </div>
                <ul class="space-y-3 text-left text-xs text-stone-600 dark:text-zinc-400 mb-8">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check</span> Kasir POS Tanpa Batas Transaksi</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check</span> Manajemen Resep & Yield Bahan</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check</span> Notifikasi Stok Bahan Kritis</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check</span> Laporan Keuangan Cash-Basis</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm font-bold">check</span> Buku Kasbon Piutang & Pengingat</li>
                </ul>
                @auth
                    <a href="{{ route('dashboard') }}" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 px-6 rounded-2xl shadow-md transition-all duration-200 text-xs text-center">
                        Masuk ke Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 px-6 rounded-2xl shadow-md transition-all duration-200 text-xs text-center">
                        Mulai Gratis Sekarang
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t border-stone-200/50 dark:border-zinc-800/40 bg-stone-50 dark:bg-zinc-900 py-12 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-2">
                <img src="{{ asset('logo.png') }}" alt="SAHAYU Logo" class="w-8 h-8 rounded-lg shadow-sm flex-shrink-0 object-cover" />
                <span class="text-md font-bold tracking-tight text-stone-800 dark:text-white">SAHAYU</span>
            </div>
            <p class="text-xs text-stone-400 dark:text-zinc-500">
                &copy; 2026 SAHAYU Application. Hak Cipta Dilindungi.
            </p>
            <div class="flex items-center gap-4 text-xs text-stone-400 dark:text-zinc-500">
                <a href="#" class="hover:underline">Syarat & Ketentuan</a>
                <a href="#" class="hover:underline">Kebijakan Privasi</a>
            </div>
        </div>
    </footer>

</body>
</html>
