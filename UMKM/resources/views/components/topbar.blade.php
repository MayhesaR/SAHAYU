<header class="sticky top-0 z-40 bg-white border-b border-stone-200/60 flex items-center justify-between px-6 md:px-8 h-16 w-full xl:sticky xl:top-4 xl:mt-4 xl:mb-2 xl:mx-8 xl:w-auto xl:bg-white/80 xl:backdrop-blur-md xl:border xl:border-stone-200/50 xl:rounded-[1.25rem] xl:shadow-sm xl:shadow-stone-200/5">
    <div class="flex items-center gap-4">
        <!-- Hamburger Menu Button (Mobile) -->
        <button @click="sidebarOpen = true" class="p-2 rounded-xl text-stone-500 hover:text-[#0b6e4f] hover:bg-stone-50 transition-all duration-200 xl:hidden">
            <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        
        <!-- Search bar (Always shown or based on section) -->
        <div class="relative group hidden md:block">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 group-focus-within:text-[#0b6e4f] transition-colors text-base">search</span>
            <input class="pl-9 pr-12 py-1.5 bg-stone-100/60 border border-stone-200/40 rounded-xl text-xs focus:ring-4 focus:ring-[#0b6e4f]/5 focus:border-[#0b6e4f] w-64 transition-all duration-200 font-medium placeholder-stone-400 text-stone-700 outline-none" 
                   placeholder="Cari transaksi atau data..." 
                   type="text"/>
            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-stone-400 bg-stone-200/60 px-1.5 py-0.5 rounded-md select-none pointer-events-none">⌘ K</span>
        </div>
    </div>
    
    <div class="flex items-center space-x-3.5">
        <!-- Mail Button -->
        <button class="text-stone-500 hover:text-[#0b6e4f] hover:bg-stone-50 p-2 rounded-xl transition-all duration-200 relative hidden sm:block" title="Pesan">
            <span class="material-symbols-outlined text-xl">mail</span>
        </button>

        <!-- Notifications Button -->
        <button class="text-stone-500 hover:text-[#0b6e4f] hover:bg-stone-50 p-2 rounded-xl transition-all duration-200 relative hidden sm:block" title="Notifikasi">
            <span class="material-symbols-outlined text-xl">notifications</span>
            <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-rose-500 rounded-full border border-white"></span>
        </button>
        
        <!-- Settings Button -->
        <button class="text-stone-500 hover:text-[#0b6e4f] hover:bg-stone-50 p-2 rounded-xl transition-all duration-200 hidden sm:block" title="Pengaturan">
            <span class="material-symbols-outlined text-xl">settings</span>
        </button>

        <div class="h-6 w-px bg-stone-200"></div>
        
        <!-- User Profile Pill -->
        <a href="{{ route('profile.index') }}" 
           class="flex items-center space-x-3 hover:opacity-85 transition-opacity">
            @if(auth()->check())
            <div class="hidden md:block text-right leading-none">
                <p class="text-xs font-semibold text-stone-850">{{ auth()->user()->name }}</p>
                <p class="text-[8px] font-bold text-stone-400 uppercase tracking-wider mt-0.5">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Staff Kasir' }}</p>
            </div>
            <div class="w-8.5 h-8.5 rounded-full bg-gradient-to-tr from-[#0b6e4f] to-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-sm shadow-[#0b6e4f]/10">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            @endif
        </a>
    </div>
</header>
