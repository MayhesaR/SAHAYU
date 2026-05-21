<header class="sticky top-0 z-40 bg-white dark:bg-zinc-900 border-b border-stone-200/60 dark:border-zinc-800/80 flex items-center justify-between px-6 md:px-8 h-16 w-full xl:sticky xl:top-4 xl:mt-4 xl:mb-2 xl:mx-8 xl:w-auto xl:bg-white/80 xl:backdrop-blur-md dark:xl:bg-zinc-900/85 xl:border xl:border-stone-200/50 dark:xl:border-zinc-850 dark:xl:border-zinc-800/65 xl:rounded-[1.25rem] xl:shadow-sm xl:shadow-stone-200/5 transition-all duration-300">
    <div class="flex items-center gap-4">
        <!-- Hamburger Menu Button (Mobile) -->
        <button @click="sidebarOpen = true" class="p-2 rounded-xl text-stone-500 dark:text-white hover:text-[#0b6e4f] dark:hover:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 transition-all duration-200 xl:hidden">
            <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        
        <!-- Search bar (Always shown or based on section) -->
        <div class="relative group hidden md:block">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 dark:text-white group-focus-within:text-[#0b6e4f] dark:group-focus-within:text-emerald-400 transition-colors text-base">search</span>
            <input class="pl-9 pr-12 py-1.5 bg-stone-100/60 dark:bg-zinc-800/60 border border-stone-200/40 dark:border-zinc-800/40 dark:border-zinc-700/40 rounded-xl text-xs focus:ring-4 focus:ring-[#0b6e4f]/5 dark:focus:ring-emerald-500/10 focus:border-[#0b6e4f] dark:focus:border-emerald-500 w-64 transition-all duration-200 font-medium placeholder-stone-400 dark:placeholder-zinc-500 text-stone-700 dark:text-zinc-50 dark:text-zinc-200 outline-none" 
                   placeholder="Cari transaksi atau data..." 
                   type="text"/>
            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-stone-400 dark:text-zinc-400 bg-stone-200/60 dark:bg-zinc-800 px-1.5 py-0.5 rounded-md select-none pointer-events-none">⌘ K</span>
        </div>
    </div>
    
    <div class="flex items-center space-x-3.5">
        <!-- Theme Toggle Button -->
        <button @click="darkMode = !darkMode" 
                class="text-stone-500 dark:text-white hover:text-[#0b6e4f] dark:hover:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 p-2 rounded-xl transition-all duration-200 flex items-center justify-center cursor-pointer animate-fade-in" 
                title="Ganti Tema">
            <!-- Sun Icon (Active in Dark Mode) -->
            <svg x-show="darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <!-- Moon Icon (Active in Light Mode) -->
            <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>

        <!-- Mail Button & Dropdown -->
        <div class="relative flex items-center" x-data="{ open: false }">
            <button @click="open = !open" class="text-stone-500 dark:text-white hover:text-[#0b6e4f] dark:hover:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 p-2 rounded-xl transition-all duration-200 relative hidden sm:block" title="Pesan">
                <span class="material-symbols-outlined text-xl">mail</span>
                <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-[#0b6e4f] dark:bg-emerald-600 rounded-full border border-white"></span>
            </button>
            
            <div x-show="open" 
                 @click.outside="open = false" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-zinc-900 rounded-2xl shadow-xl border border-stone-100 dark:border-zinc-800/60 dark:border-zinc-800/80 p-4 z-50 focus:outline-none"
                 style="display: none;">
                <div class="flex items-center justify-between border-b border-stone-100 dark:border-zinc-800/60 dark:border-zinc-800 pb-2.5 mb-3">
                    <h3 class="text-xs font-bold text-stone-800 dark:text-white flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-stone-500 dark:text-white text-base">receipt_long</span>
                        Log Aktivitas Sistem
                    </h3>
                    <span class="text-[9px] font-bold text-[#0b6e4f] dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">Terbaru</span>
                </div>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @foreach($activities as $act)
                    <div class="flex items-start gap-3 text-left">
                        <div class="w-7 h-7 rounded-lg bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 flex items-center justify-center text-stone-600 dark:text-zinc-300 shrink-0">
                            <span class="material-symbols-outlined text-base">{{ $act['icon'] }}</span>
                        </div>
                        <div class="space-y-0.5 col-span-1 min-w-0">
                            <p class="text-[10px] text-stone-750 dark:text-zinc-50 dark:text-white font-medium leading-snug break-words">{{ $act['message'] }}</p>
                            <p class="text-[9px] text-stone-400 dark:text-zinc-400 flex items-center gap-1">
                                <span class="font-semibold text-stone-500 dark:text-zinc-400">{{ $act['user'] }}</span>
                                <span>&bull;</span>
                                <span>{{ $act['time'] }}</span>
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Notifications Button & Dropdown -->
        <div class="relative flex items-center" x-data="{ open: false }">
            <button @click="open = !open" class="text-stone-500 dark:text-white hover:text-[#0b6e4f] dark:hover:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 p-2 rounded-xl transition-all duration-200 relative hidden sm:block" title="Notifikasi">
                <span class="material-symbols-outlined text-xl">notifications</span>
                <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-rose-500 rounded-full border border-white"></span>
                <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-rose-500 rounded-full border border-white animate-ping"></span>
            </button>
            
            <div x-show="open" 
                 @click.outside="open = false" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-zinc-900 rounded-2xl shadow-xl border border-stone-100 dark:border-zinc-800/60 dark:border-zinc-800/80 p-4 z-50 focus:outline-none"
                 style="display: none;">
                <div class="flex items-center justify-between border-b border-stone-100 dark:border-zinc-800/60 dark:border-zinc-800 pb-2.5 mb-3">
                    <h3 class="text-xs font-bold text-stone-800 dark:text-white flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-rose-500 dark:text-rose-400 text-base">warning</span>
                        Peringatan Sistem
                    </h3>
                    <span class="text-[9px] font-bold text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-full">{{ count($alerts) }} Peringatan</span>
                </div>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @foreach($alerts as $alert)
                    <div class="flex items-start gap-3 text-left">
                        <div class="w-7 h-7 rounded-lg bg-rose-50/50 dark:bg-rose-950/50 dark:bg-rose-950/20 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                            <span class="material-symbols-outlined text-base">warning</span>
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-[10px] text-stone-750 dark:text-zinc-50 dark:text-white font-semibold leading-snug">{{ $alert }}</p>
                            <p class="text-[8px] text-rose-500 dark:text-rose-400 font-bold uppercase tracking-wider">Penting</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Settings Button -->
        <a href="{{ route('settings.index') }}" class="text-stone-500 dark:text-white hover:text-[#0b6e4f] dark:hover:text-zinc-400 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/60 p-2 rounded-xl transition-all duration-200 hidden sm:block" title="Pengaturan">
            <span class="material-symbols-outlined text-xl">settings</span>
        </a>

        <div class="h-6 w-px bg-stone-200 dark:bg-zinc-800"></div>
        
        <!-- User Profile Pill -->
        <a href="{{ route('profile.index') }}" 
           class="flex items-center space-x-3 hover:opacity-85 transition-opacity">
            @if(auth()->check())
            <div class="hidden md:block text-right leading-none">
                <p class="text-xs font-semibold text-stone-850 dark:text-white">{{ auth()->user()->name }}</p>
                <p class="text-[8px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-wider mt-0.5">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Staff Kasir' }}</p>
            </div>
            <div class="w-8.5 h-8.5 rounded-full bg-gradient-to-tr from-[#0b6e4f] to-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-sm shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            @endif
        </a>
    </div>
</header>
