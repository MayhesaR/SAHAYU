<aside class="h-screen w-64 fixed left-0 top-0 z-50 bg-white dark:bg-zinc-900 border-r border-stone-200/60 dark:border-zinc-800/80 flex flex-col py-6 shadow-sm transition-all duration-300 -translate-x-full xl:translate-x-0 xl:left-4 xl:top-4 xl:h-[calc(100vh-2rem)] xl:rounded-[2rem] xl:border xl:border-stone-200/50 dark:xl:border-zinc-850 xl:shadow-lg xl:shadow-stone-200/5"
       :class="sidebarOpen ? 'translate-x-0 !shadow-2xl' : '-translate-x-full xl:translate-x-0'">
    
    <!-- Branding Header -->
    <div class="px-6 mb-8 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <img src="{{ asset('logo.png') }}" alt="SAHAYU Logo" class="w-9 h-9 rounded-xl shadow-md shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15 flex-shrink-0 object-cover" />
            <div>
                <h1 class="text-lg font-black text-stone-900 dark:text-white tracking-tight font-headline">SAHAYU</h1>
                <p class="text-[9px] font-bold text-stone-400 dark:text-white uppercase tracking-widest leading-none mt-0.5">Keuangan UMKM</p>
            </div>
        </div>
        <!-- Close button for mobile -->
        <button @click="sidebarOpen = false" class="xl:hidden p-1.5 rounded-lg hover:bg-stone-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800/80 dark:hover:bg-zinc-850 text-stone-400 dark:text-white hover:text-stone-600 dark:hover:text-zinc-300 dark:hover:text-white transition-colors">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>

    <!-- Navigation Items -->
    <div class="px-3 mb-2">
        <p class="text-[9px] font-bold text-stone-400 dark:text-white uppercase tracking-widest px-3 mb-2">Menu Utama</p>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto no-scrollbar px-3">
        @php
            $activeClass = "flex items-center justify-between px-3.5 py-2.5 text-white bg-[#0b6e4f] dark:bg-emerald-600 rounded-xl font-body text-xs font-semibold tracking-wide transition-all duration-200 shadow-sm shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15";
            $inactiveClass = "flex items-center justify-between px-3.5 py-2.5 text-stone-500 dark:text-white hover:text-[#0b6e4f] dark:hover:text-emerald-400 hover:bg-stone-100/60 dark:hover:bg-zinc-800/50 rounded-xl font-body text-xs font-medium tracking-wide transition-all duration-200";
        @endphp

        <!-- Shared Navigation Menu (Accessible by both Admin and Staff) -->
        <a class="{{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}" href="{{ route('dashboard') }}">
            <div class="flex items-center">
                <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-stone-400 dark:text-white' }}">dashboard</span>
                <span>Dashboard Harian</span>
            </div>
        </a>

        <a class="{{ request()->routeIs('sales.*') ? $activeClass : $inactiveClass }}" href="{{ route('sales.index') }}">
            <div class="flex items-center">
                <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('sales.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">point_of_sale</span>
                <span>Kasir POS</span>
            </div>
        </a>

        <a class="{{ request()->routeIs('history.*') ? $activeClass : $inactiveClass }}" href="{{ route('history.index') }}">
            <div class="flex items-center">
                <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('history.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">history</span>
                <span>Riwayat Transaksi</span>
            </div>
        </a>

        <a class="{{ request()->routeIs('debts.*') ? $activeClass : $inactiveClass }}" href="{{ route('debts.index') }}">
            <div class="flex items-center">
                <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('debts.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">book</span>
                <span>Piutang / Kasbon</span>
            </div>
        </a>

        <a class="{{ request()->routeIs('expenses.*') ? $activeClass : $inactiveClass }}" href="{{ route('expenses.index') }}">
            <div class="flex items-center">
                <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('expenses.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">receipt_long</span>
                <span>Catat Pengeluaran</span>
            </div>
        </a>

        <a class="{{ request()->routeIs('customers.*') ? $activeClass : $inactiveClass }}" href="{{ route('customers.index') }}">
            <div class="flex items-center">
                <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('customers.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">group</span>
                <span>Data Pelanggan</span>
            </div>
        </a>

        <!-- Collapsible Dropdown "Kelola Toko" -->
        @php
            $isKelolaActive = request()->routeIs('materials.*') || request()->routeIs('productions.*') || request()->routeIs('overhead.*') || request()->routeIs('reports.*') || request()->routeIs('ai.*') || request()->routeIs('products.*') || request()->routeIs('hpp.*') || request()->routeIs('accounts.*');
        @endphp
        <div x-data="{ openKelolaToko: {{ $isKelolaActive ? 'true' : 'false' }} }" class="w-full">
            <button @click="openKelolaToko = !openKelolaToko" 
                    class="w-full flex items-center justify-between px-3.5 py-2.5 text-stone-500 dark:text-white hover:text-[#0b6e4f] dark:hover:text-emerald-400 hover:bg-stone-100/60 dark:hover:bg-zinc-800/60 dark:hover:bg-zinc-800/50 rounded-xl font-body text-xs font-medium tracking-wide transition-all duration-200">
                <div class="flex items-center">
                    <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0" :class="openKelolaToko || {{ $isKelolaActive ? 'true' : 'false' }} 'text-[#0b6e4f] dark:text-emerald-400' 'text-stone-400 dark:text-zinc-300 dark:text-zinc-450'">storefront</span>
                    <span>Kelola Toko</span>
                </div>
                <span class="material-symbols-outlined text-xs transition-transform duration-200" :class="openKelolaToko ? 'rotate-180 text-[#0b6e4f] dark:text-emerald-400' : 'text-stone-400 dark:text-zinc-300 dark:text-zinc-450'">expand_more</span>
            </button>
            
            <div x-show="openKelolaToko" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 max-h-0 overflow-hidden"
                 x-transition:enter-end="opacity-100 max-h-[500px]"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 max-h-[500px]"
                 x-transition:leave-end="opacity-0 max-h-0 overflow-hidden"
                 class="space-y-0.5 mt-0.5 pl-3 border-l border-[#0b6e4f]/10 ml-5">
                
                <a class="{{ request()->routeIs('products.*') ? $activeClass : $inactiveClass }}" href="{{ route('products.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('products.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">inventory</span>
                        <span>Produk Jadi</span>
                    </div>
                </a>

                @if(auth()->check() && auth()->user()->isAdmin())
                <a class="{{ request()->routeIs('hpp.*') ? $activeClass : $inactiveClass }}" href="{{ route('hpp.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('hpp.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">calculate</span>
                        <span>HPP Otomatis</span>
                    </div>
                </a>
                @endif

                <a class="{{ request()->routeIs('materials.*') ? $activeClass : $inactiveClass }}" href="{{ route('materials.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('materials.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">inventory_2</span>
                        <span>Bahan Baku</span>
                    </div>
                </a>

                <a class="{{ request()->routeIs('productions.*') ? $activeClass : $inactiveClass }}" href="{{ route('productions.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('productions.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">precision_manufacturing</span>
                        <span>Produksi</span>
                    </div>
                </a>

                <a class="{{ request()->routeIs('overhead.*') ? $activeClass : $inactiveClass }}" href="{{ route('overhead.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('overhead.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">account_balance_wallet</span>
                        <span>Biaya Operasional</span>
                    </div>
                </a>

                <a class="{{ request()->routeIs('reports.*') ? $activeClass : $inactiveClass }}" href="{{ route('reports.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('reports.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">analytics</span>
                        <span>Laporan Analisis</span>
                    </div>
                </a>

                <a class="{{ request()->routeIs('ai.*') ? $activeClass : $inactiveClass }}" href="{{ route('ai.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('ai.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">smart_toy</span>
                        <span>SAHAYU AI Assistant</span>
                    </div>
                </a>

                @if(auth()->check() && auth()->user()->isAdmin())
                <a class="{{ request()->routeIs('accounts.*') ? $activeClass : $inactiveClass }}" href="{{ route('accounts.index') }}">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 text-lg flex-shrink-0 {{ request()->routeIs('accounts.*') ? 'text-white' : 'text-stone-400 dark:text-white' }}">manage_accounts</span>
                        <span>Manajemen Akun</span>
                    </div>
                </a>
                @endif
            </div>
        </div>
    </nav>

    <!-- Bottom User Profile Card -->
    @if(auth()->check())
    <div class="relative overflow-hidden m-4 p-4 bg-[#0b6e4f] dark:bg-emerald-600 rounded-2xl text-white shadow-md shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15">
        <!-- Glow Decorative backgrounds -->
        <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-emerald-400/25 rounded-full blur-xl pointer-events-none"></div>
        <div class="absolute -left-6 -top-6 w-16 h-16 bg-emerald-400/15 rounded-full blur-lg pointer-events-none"></div>

        <div class="relative z-10 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center font-bold text-xs uppercase border border-white/20">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="overflow-hidden">
                <h4 class="text-xs font-semibold truncate leading-tight">{{ auth()->user()->name }}</h4>
                <p class="text-[9px] text-emerald-400 font-medium tracking-wide mt-0.5">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Staff Kasir' }}</p>
            </div>
        </div>
        <div class="relative z-10 mt-4 pt-3 border-t border-white/10 flex items-center justify-between gap-2">
            <a href="{{ route('profile.index') }}" class="flex-1 text-center py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all duration-200">
                Profil Saya
            </a>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="p-1.5 bg-rose-500/20 hover:bg-rose-500/40 text-rose-300 hover:text-white rounded-lg transition-colors" title="Keluar">
                    <span class="material-symbols-outlined text-sm block">logout</span>
                </button>
            </form>
        </div>
    </div>
    @endif
</aside>
