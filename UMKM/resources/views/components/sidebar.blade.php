<aside class="h-screen w-64 fixed left-0 top-0 z-50 bg-slate-50 flex flex-col py-6 border-r border-outline-variant/10 transition-transform duration-300 -translate-x-full xl:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full xl:translate-x-0'">
    <div class="px-8 mb-10 flex justify-between items-center">
        <div>
            <h1 class="text-lg font-black text-teal-800 tracking-tight">SAHAYU</h1>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-1"></p>
        </div>
        <!-- Close button for mobile -->
        <button @click="sidebarOpen = false" class="xl:hidden text-slate-400 hover:text-slate-600">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto no-scrollbar">
        @php
            $activeClass = "flex items-center px-8 py-3 text-teal-900 border-r-4 border-teal-600 bg-teal-50/50 font-manrope text-sm font-semibold tracking-wide material-slide";
            $inactiveClass = "flex items-center px-8 py-3 text-slate-500 font-manrope text-sm font-semibold tracking-wide hover:text-teal-700 hover:pl-10 transition-all material-slide";
        @endphp

        @if(auth()->check() && auth()->user()->isStaff())
            <!-- STAFF ROLE NAVIGATION -->
            <a class="{{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}" href="{{ route('dashboard') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">home</span> Dashboard Harian
            </a>

            <a class="{{ request()->routeIs('sales.*') ? $activeClass : $inactiveClass }}" href="{{ route('sales.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">shopping_cart</span> Kasir POS
            </a>

            <a class="{{ request()->routeIs('history.*') ? $activeClass : $inactiveClass }}" href="{{ route('history.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">history</span> Riwayat Transaksi
            </a>

            <a class="{{ request()->routeIs('debts.*') ? $activeClass : $inactiveClass }}" href="{{ route('debts.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">book</span> Piutang / Kasbon
            </a>

            <a class="{{ request()->routeIs('customers.*') ? $activeClass : $inactiveClass }}" href="{{ route('customers.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">group</span> Data Pelanggan
            </a>

            <!-- Collapsible Dropdown "Kelola Toko" -->
            @php
                $isKelolaActive = request()->routeIs('materials.*') || request()->routeIs('productions.*') || request()->routeIs('overhead.*') || request()->routeIs('reports.*') || request()->routeIs('ai.*');
            @endphp
            <div x-data="{ openKelolaToko: {{ $isKelolaActive ? 'true' : 'false' }} }" class="w-full">
                <button @click="openKelolaToko = !openKelolaToko" 
                        class="w-full flex items-center justify-between px-8 py-3 text-slate-500 font-manrope text-sm font-semibold tracking-wide hover:text-teal-700 transition-all material-slide">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-3 flex-shrink-0">storefront</span>
                        <span>Kelola Toko</span>
                    </div>
                    <span class="material-symbols-outlined text-xs transition-transform duration-200" :class="openKelolaToko ? 'rotate-180' : ''">expand_more</span>
                </button>
                
                <div x-show="openKelolaToko" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 max-h-0 overflow-hidden"
                     x-transition:enter-end="opacity-100 max-h-[500px]"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 max-h-[500px]"
                     x-transition:leave-end="opacity-0 max-h-0 overflow-hidden"
                     class="space-y-1 pl-4 bg-slate-100/20">
                    
                    <a class="{{ request()->routeIs('materials.*') ? $activeClass : $inactiveClass }}" href="{{ route('materials.index') }}">
                        <span class="material-symbols-outlined mr-3 flex-shrink-0">inventory_2</span> Bahan Baku
                    </a>

                    <a class="{{ request()->routeIs('productions.*') ? $activeClass : $inactiveClass }}" href="{{ route('productions.index') }}">
                        <span class="material-symbols-outlined mr-3 flex-shrink-0">precision_manufacturing</span> Produksi
                    </a>

                    <a class="{{ request()->routeIs('overhead.*') ? $activeClass : $inactiveClass }}" href="{{ route('overhead.index') }}">
                        <span class="material-symbols-outlined mr-3 flex-shrink-0">account_balance_wallet</span> Biaya Operasional
                    </a>

                    <a class="{{ request()->routeIs('reports.*') ? $activeClass : $inactiveClass }}" href="{{ route('reports.index') }}">
                        <span class="material-symbols-outlined mr-3 flex-shrink-0">analytics</span> Laporan Analisis
                    </a>

                    <a class="{{ request()->routeIs('ai.*') ? $activeClass : $inactiveClass }}" href="{{ route('ai.index') }}">
                        <span class="material-symbols-outlined mr-3 flex-shrink-0">smart_toy</span> SAHAYU AI Assistant
                    </a>
                </div>
            </div>

            <a class="{{ request()->routeIs('profile.*') ? $activeClass : $inactiveClass }}" href="{{ route('profile.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">person</span> Profil Saya
            </a>
        @else
            <!-- ORIGINAL ADMIN / OTHER ROLES NAVIGATION -->
            <a class="{{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}" href="{{ route('dashboard') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">dashboard</span> Dashboard
            </a>

            <a class="{{ request()->routeIs('materials.*') ? $activeClass : $inactiveClass }}" href="{{ route('materials.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">inventory_2</span> Bahan Baku
            </a>

            <a class="{{ request()->routeIs('productions.*') ? $activeClass : $inactiveClass }}" href="{{ route('productions.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">precision_manufacturing</span> Produksi
            </a>

            <a class="{{ request()->routeIs('products.*') ? $activeClass : $inactiveClass }}" href="{{ route('products.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">inventory</span> Produk Jadi
            </a>

            @if(auth()->check() && auth()->user()->isAdmin())
            <a class="{{ request()->routeIs('hpp.*') ? $activeClass : $inactiveClass }}" href="{{ route('hpp.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">calculate</span> HPP Otomatis
            </a>
            @endif

            <a class="{{ request()->routeIs('sales.*') ? $activeClass : $inactiveClass }}" href="{{ route('sales.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">point_of_sale</span> Penjualan
            </a>

            <a class="{{ request()->routeIs('customers.*') ? $activeClass : $inactiveClass }}" href="{{ route('customers.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">group</span> Data Pelanggan
            </a>

            <a class="{{ request()->routeIs('overhead.*') ? $activeClass : $inactiveClass }}" href="{{ route('overhead.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">account_balance_wallet</span> Biaya Operasional
            </a>

            <a class="{{ request()->routeIs('reports.*') ? $activeClass : $inactiveClass }}" href="{{ route('reports.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">analytics</span> Laporan
            </a>

            <a class="{{ request()->routeIs('ai.*') ? $activeClass : $inactiveClass }}" href="{{ route('ai.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">smart_toy</span> SAHAYU Assistant
            </a>

            @if(auth()->check() && auth()->user()->isAdmin())
            <a class="{{ request()->routeIs('accounts.*') ? $activeClass : $inactiveClass }}" href="{{ route('accounts.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">manage_accounts</span> Manajemen Akun
            </a>
            @endif

            <a class="{{ request()->routeIs('profile.*') ? $activeClass : $inactiveClass }}" href="{{ route('profile.index') }}">
                <span class="material-symbols-outlined mr-3 flex-shrink-0">person</span> Profil Saya
            </a>
        @endif
    </nav>

    <div class="px-8 mt-auto pt-6 border-t border-slate-100">
        <form method="POST" action="{{ route('logout') }}" class="m-0 w-full block">
            @csrf
            <button type="submit" class="flex items-center py-2 space-x-3 text-slate-500 hover:text-error transition-all w-full text-left font-semibold">
                <span class="material-symbols-outlined">logout</span>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
