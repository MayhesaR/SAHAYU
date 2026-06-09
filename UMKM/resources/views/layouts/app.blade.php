<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden max-w-full" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'SAHAYU')</title>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- Driver.js Library CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css">
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <!-- AlpineJS for responsive sidebar toggling -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        html, body { 
            overflow-x: hidden !important; 
            position: relative; 
            max-width: 100%; 
            min-height: 100vh;
        }
        * { box-sizing: border-box; }
        
        /* Premium Background Mesh Glow */
        body { 
            font-family: 'Inter', sans-serif; 
            background: 
                radial-gradient(at 0% 0%, rgba(11, 110, 79, 0.04) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(11, 110, 79, 0.02) 0px, transparent 50%),
                #fafbfa !important;
            background-attachment: fixed !important;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        html.dark body {
            background: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.03) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(16, 185, 129, 0.01) 0px, transparent 50%),
                #09090b !important;
        }
        
        h1, h2, h3, h4, h5, h6 { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        
        /* Glassmorphism Styles */
        .glass-card {
            background: rgba(255, 255, 255, 0.65) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 10px 30px -10px rgba(4, 120, 87, 0.04), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.8) !important;
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        html.dark .glass-card {
            background: rgba(24, 24, 27, 0.75) !important;
            border: 1px solid rgba(63, 63, 70, 0.4) !important;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.03) !important;
        }
        
        .glass-card-dark {
            background: rgba(6, 78, 59, 0.85) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 20px 40px -15px rgba(2, 44, 34, 0.3),
                        inset 0 1px 0px rgba(255, 255, 255, 0.15) !important;
        }
        
        /* Interactive Utilities */
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .hover-lift:hover {
            transform: translateY(-5px) scale(1.01) !important;
            box-shadow: 0 20px 40px -15px rgba(4, 120, 87, 0.1),
                        inset 0 1px 1px rgba(255, 255, 255, 0.8) !important;
        }
        
        .hover-lift-sm {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .hover-lift-sm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px -6px rgba(4, 120, 87, 0.08) !important;
        }
        
        /* Smooth Scrollbars */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(11, 110, 79, 0.15);
            border-radius: 99px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(11, 110, 79, 0.35);
        }

        /* Driver.js Popover Premium Customizations */
        .driver-popover {
            background-color: #ffffff !important;
            color: #1c1917 !important; /* stone-900 */
            font-family: 'Inter', sans-serif !important;
            border-radius: 1rem !important; /* rounded-2xl */
            border: 1px solid rgba(16, 185, 129, 0.2) !important; /* Emerald-500 border with low opacity */
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            padding: 1.25rem !important;
            max-width: 320px !important;
        }
        
        .dark .driver-popover {
            background-color: #18181b !important; /* zinc-900 */
            color: #f4f4f5 !important; /* zinc-100 */
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
        }

        .driver-popover-title {
            font-family: 'Manrope', sans-serif !important;
            font-weight: 800 !important;
            font-size: 0.95rem !important;
            color: #065f46 !important; /* emerald-800 */
            margin-bottom: 0.5rem !important;
        }

        .dark .driver-popover-title {
            color: #34d399 !important; /* emerald-400 */
        }

        .driver-popover-description {
            font-size: 0.8rem !important;
            line-height: 1.4 !important;
            color: #4b5563 !important; /* gray-600 */
            font-weight: 500 !important;
        }

        .dark .driver-popover-description {
            color: #d4d4d8 !important; /* zinc-300 */
        }

        .driver-popover-navigation-btns {
            margin-top: 1rem !important;
            gap: 0.5rem !important;
            display: flex !important;
            justify-content: flex-end !important;
        }

        .driver-popover-btn {
            background-color: #f3f4f6 !important;
            color: #374151 !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            padding: 0.4rem 0.8rem !important;
            border-radius: 0.5rem !important;
            border: 1px solid #e5e7eb !important;
            text-shadow: none !important;
            transition: all 0.15s ease !important;
        }

        .driver-popover-btn:hover {
            background-color: #e5e7eb !important;
            color: #111827 !important;
        }

        .dark .driver-popover-btn {
            background-color: #27272a !important; /* zinc-800 */
            color: #e4e4e7 !important;
            border: 1px solid #3f3f46 !important;
        }

        .dark .driver-popover-btn:hover {
            background-color: #3f3f46 !important;
            color: #ffffff !important;
        }

        .driver-popover-btn-next, .driver-popover-btn-done {
            background-color: #10b981 !important; /* emerald-500 */
            color: #ffffff !important;
            border: 1px solid #10b981 !important;
        }

        .driver-popover-btn-next:hover, .driver-popover-btn-done:hover {
            background-color: #059669 !important; /* emerald-600 */
            color: #ffffff !important;
            border: 1px solid #059669 !important;
        }

        .driver-popover-progress-text {
            font-size: 0.75rem !important;
            color: #9ca3af !important;
            font-weight: 600 !important;
        }
        
        .driver-popover-arrow {
            border-color: #ffffff !important;
        }
        .dark .driver-popover-arrow {
            border-color: #18181b !important;
        }

        [x-cloak] { display: none !important; }

        @yield('styles')
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 text-stone-800 dark:text-white antialiased selection:bg-emerald-500/20 selection:text-emerald-900 dark:selection:text-emerald-300 dark:selection:text-white overflow-x-hidden max-w-full"
      x-data="{ 
          sidebarOpen: false,
          searchOpen: false, 
          searchInput: '', 
          searchResults: [], 
          isLoading: false,
          fetchResults() {
              this.isLoading = true;
              fetch('/api/global-search?q=' + encodeURIComponent(this.searchInput))
                  .then(res => res.json())
                  .then(data => {
                      this.searchResults = data;
                      this.isLoading = false;
                  })
                  .catch(() => {
                      this.isLoading = false;
                  });
          }
      }"
      x-init="$watch('searchOpen', val => { if(val) { $nextTick(() => $refs.searchInput.focus()); fetchResults(); } else { searchInput = ''; } })"
      @keydown.window.prevent.cmd.k="searchOpen = true"
      @keydown.window.prevent.meta.k="searchOpen = true"
      @keydown.window.prevent.ctrl.k="searchOpen = true"
      @keydown.window.escape="searchOpen = false">

    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen" 
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm xl:hidden transition-opacity"
         x-transition.opacity
         @click="sidebarOpen = false"></div>

    @include('components.sidebar')

    <main class="min-h-screen flex flex-col min-w-0 transition-all duration-300 xl:pl-[288px]">
        @include('components.topbar')

        <div class="w-full px-6 md:px-8 py-8 space-y-8 flex-1 min-w-0">
            @yield('content')
        </div>
    </main>

    <!-- Global Spotlight Search Modal -->
    <div x-show="searchOpen" 
         class="fixed inset-0 z-[150] overflow-y-auto bg-stone-900/40 dark:bg-black/70 backdrop-blur-md flex items-start justify-center pt-20 px-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="searchOpen = false"
         x-cloak>
        
        <div x-show="searchOpen"
             class="bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 shadow-2xl rounded-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[75vh]"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4">
             
            <!-- Search Header -->
            <div class="relative flex items-center border-b border-stone-100 dark:border-zinc-850 shrink-0">
                <span class="material-symbols-outlined absolute left-4 text-stone-400 dark:text-zinc-500 text-xl">search</span>
                <input x-model="searchInput"
                       @input.debounce.300ms="fetchResults()"
                       x-ref="searchInput"
                       type="text"
                       class="w-full bg-transparent pl-12 pr-12 py-4 text-stone-855 dark:text-zinc-150 text-sm focus:outline-none placeholder-stone-400 dark:placeholder-zinc-500 font-medium"
                       placeholder="Cari produk, pelanggan, transaksi, atau menu..." />
                
                <!-- Loading indicator -->
                <div x-show="isLoading" class="absolute right-4 flex items-center" x-cloak>
                    <div class="w-4 h-4 rounded-full border-2 border-[#0b6e4f] dark:border-emerald-500 border-t-transparent animate-spin"></div>
                </div>

                <!-- Clear button -->
                <button x-show="searchInput && !isLoading" 
                        @click="searchInput = ''; fetchResults(); $refs.searchInput.focus()" 
                        class="absolute right-4 text-stone-400 dark:text-zinc-400 hover:text-stone-650 dark:hover:text-white transition-colors"
                        type="button" 
                        x-cloak>
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Search Results Container -->
            <div class="overflow-y-auto flex-1 px-4 py-3 min-h-[250px] max-h-[450px] bg-white dark:bg-zinc-900 custom-scrollbar">
                <!-- If there are results -->
                <div class="space-y-4" x-show="searchResults.length > 0">
                    <template x-for="group in searchResults" :key="group.category">
                        <div>
                            <h4 class="text-[10px] font-bold text-[#0b6e4f] dark:text-emerald-400 uppercase tracking-widest mb-2 px-2" x-text="group.category"></h4>
                            <div class="space-y-1">
                                <template x-for="item in group.items" :key="item.title + item.url">
                                    <a :href="item.url" 
                                       class="flex items-center justify-between p-2 rounded-xl hover:bg-stone-50 dark:hover:bg-zinc-800/40 transition-all duration-200 group/item">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-stone-100/80 dark:bg-zinc-800 flex items-center justify-center text-stone-500 dark:text-zinc-400 group-hover/item:bg-[#0b6e4f]/10 dark:group-hover/item:bg-emerald-500/10 group-hover/item:text-[#0b6e4f] dark:group-hover/item:text-emerald-400 transition-colors shrink-0">
                                                <span class="material-symbols-outlined text-lg" x-text="item.icon"></span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-stone-750 dark:text-zinc-200 group-hover/item:text-[#0b6e4f] dark:group-hover/item:text-emerald-400 transition-colors truncate" x-text="item.title"></p>
                                                <p class="text-[10px] text-stone-450 dark:text-zinc-500 font-medium mt-0.5 truncate" x-text="item.subtitle"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full" 
                                                  :class="item.badge === 'Fitur' ? 'bg-emerald-50 text-[#0b6e4f] dark:bg-emerald-950/40 dark:text-emerald-400' : 
                                                          (item.badge === 'Produk' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400' :
                                                          (item.badge === 'Pelanggan' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400' : 'bg-stone-100 text-stone-600 dark:bg-zinc-800 dark:text-zinc-400'))"
                                                  x-text="item.badge"></span>
                                            <span class="material-symbols-outlined text-stone-300 dark:text-zinc-650 group-hover/item:text-[#0b6e4f] dark:group-hover/item:text-emerald-400 text-sm transition-transform duration-200 group-hover/item:translate-x-0.5">chevron_right</span>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="!isLoading && searchResults.length === 0 && searchInput" 
                     class="py-12 text-center flex flex-col items-center justify-center" 
                     x-cloak>
                    <span class="material-symbols-outlined text-stone-300 dark:text-zinc-750 text-5xl mb-3">search_off</span>
                    <p class="text-xs font-semibold text-stone-650 dark:text-zinc-350">Tidak ada hasil ditemukan untuk "<span class="text-stone-850 dark:text-zinc-150 font-bold" x-text="searchInput"></span>"</p>
                    <p class="text-[10px] text-stone-400 dark:text-zinc-500 mt-1">Coba gunakan kata kunci pencarian yang lain</p>
                </div>
            </div>

            <!-- Search Footer -->
            <div class="px-4 py-2.5 bg-stone-50 dark:bg-zinc-900/60 border-t border-stone-100 dark:border-zinc-850 flex items-center justify-between text-[10px] text-stone-400 dark:text-zinc-500 font-medium shrink-0">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1"><span class="bg-stone-200 dark:bg-zinc-800 px-1 py-0.5 rounded text-[8px] font-bold text-stone-600 dark:text-zinc-400">ESC</span> Keluar</span>
                    <span class="flex items-center gap-1"><span class="bg-stone-200 dark:bg-zinc-800 px-1.5 py-0.5 rounded text-[8px] font-bold text-stone-600 dark:text-zinc-400">↵</span> Navigasi</span>
                </div>
                <div>
                    <span>SAHAYU Command Palette</span>
                </div>
            </div>
        </div>
    </div>

    @yield('scripts')
</body>
</html>
