<header class="w-full sticky top-0 z-40 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-4 md:px-8 h-16 border-b border-outline-variant/10">
    <div class="flex items-center">
        <!-- Hamburger Menu Button -->
        <button @click="sidebarOpen = true" class="mr-4 md:hidden text-slate-500 hover:text-teal-700 transition-colors">
            <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        
        <span class="text-sm sm:text-lg md:text-xl font-bold tracking-tight text-teal-900 mr-2 md:mr-8 break-words leading-tight">@yield('page_title', 'ArchitectLedger')</span>
        @hasSection('search_placeholder')
        <div class="relative group hidden md:block">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-teal-600 transition-colors">search</span>
            <input class="pl-10 pr-4 py-2 bg-slate-50 border-none rounded-full text-sm focus:ring-2 focus:ring-teal-600/20 w-64 transition-all" placeholder="@yield('search_placeholder')" type="text"/>
        </div>
        @endif
    </div>
    
    <div class="flex items-center space-x-6">
        <button class="text-slate-500 hover:bg-slate-100 p-2 rounded-full transition-colors relative hidden sm:block">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full border-2 border-white"></span>
        </button>
        <button class="text-slate-500 hover:bg-slate-100 p-2 rounded-full transition-colors hidden sm:block">
            <span class="material-symbols-outlined">settings</span>
        </button>
        
        <a href="{{ route('profile.index') }}" class="flex items-center space-x-3 ml-4 cursor-pointer hover:bg-slate-50 p-1.5 rounded-lg transition-colors">
            @if(auth()->check())
            <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold shadow-sm">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
            <div class="hidden lg:block text-right">
                <p class="text-sm font-bold text-teal-900 leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-tighter">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Staff/Operator' }}</p>
            </div>
            @endif
        </a>
    </div>
</header>
