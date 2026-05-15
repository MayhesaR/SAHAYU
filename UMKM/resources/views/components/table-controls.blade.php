{{--
    Reusable Table Controls Component: Search + Sort + Filter
    =========================================================
    Usage: <x-table-controls :sortOptions="[...]" :filterOptions="[...]" />

    Props:
    - action       (string)  Route/URL for the form. Default: current URL.
    - sortOptions  (array)   Array of ['value' => 'column_asc', 'label' => 'Nama (A-Z)'] for sort dropdown.
    - filterOptions (array)  Array of ['name' => 'status', 'label' => 'Status', 'choices' => [...]] for filter dropdowns.
    - searchPlaceholder (string) Placeholder text for search input.
    - showPerPage  (bool)    Whether to show per-page selector. Default: true.
--}}

@props([
    'action' => url()->current(),
    'sortOptions' => [],
    'filterOptions' => [],
    'searchPlaceholder' => 'Cari data...',
    'showPerPage' => true,
])

<form action="{{ $action }}" method="GET" id="table-controls-form"
      class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high p-5">
    <div class="flex items-end gap-3 flex-wrap">

        {{-- Search Input --}}
        <div class="flex-1 min-w-[200px]">
            <label for="tc-search" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">search</span> Pencarian
            </label>
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none z-10 text-slate-400">
                    <span class="material-symbols-outlined text-base">search</span>
                </span>
                <input type="text"
                       id="tc-search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ $searchPlaceholder }}"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-teal-500 focus:border-teal-500 bg-surface-container-highest text-sm font-medium text-teal-900 placeholder-slate-400 transition-all" />
                @if(request('search'))
                <a href="{{ $action }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors" title="Hapus pencarian">
                    <span class="material-symbols-outlined text-base">close</span>
                </a>
                @endif
            </div>
        </div>

        {{-- Sort Dropdown --}}
        @if(count($sortOptions) > 0)
        <div class="min-w-[180px]">
            <label for="tc-sort" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">sort</span> Urutkan
            </label>
            <select id="tc-sort"
                    name="sort"
                    onchange="this.form.submit()"
                    class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer">
                <option value="">Default</option>
                @foreach($sortOptions as $option)
                <option value="{{ $option['value'] }}" {{ request('sort') === $option['value'] ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Dynamic Filter Dropdowns --}}
        @foreach($filterOptions as $filter)
        <div class="min-w-[150px]">
            <label for="tc-filter-{{ $filter['name'] }}" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">filter_alt</span> {{ $filter['label'] }}
            </label>
            <select id="tc-filter-{{ $filter['name'] }}"
                    name="{{ $filter['name'] }}"
                    onchange="this.form.submit()"
                    class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer">
                <option value="">Semua</option>
                @foreach($filter['choices'] as $choiceValue => $choiceLabel)
                <option value="{{ $choiceValue }}" {{ request($filter['name']) === (string) $choiceValue ? 'selected' : '' }}>
                    {{ $choiceLabel }}
                </option>
                @endforeach
            </select>
        </div>
        @endforeach

        {{-- Per Page --}}
        @if($showPerPage)
        <div class="min-w-[100px]">
            <label for="tc-per-page" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">view_list</span> Per Halaman
            </label>
            <select id="tc-per-page"
                    name="per_page"
                    onchange="this.form.submit()"
                    class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer">
                @foreach([10, 15, 25, 50] as $pp)
                <option value="{{ $pp }}" {{ (int) request('per_page', 15) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Search Button --}}
        <button type="submit"
                class="px-5 py-2.5 rounded-lg shadow-sm hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2"
                style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;">
            <span class="material-symbols-outlined text-base">search</span>
            <span>Cari</span>
        </button>

        {{-- Reset --}}
        @if(request()->hasAny(['search', 'sort', 'category', 'status', 'per_page']))
        <a href="{{ $action }}"
           class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-600 text-sm font-bold hover:bg-slate-200 transition-all flex items-center gap-1.5"
           title="Reset semua filter">
            <span class="material-symbols-outlined text-base">restart_alt</span>
            <span>Reset</span>
        </a>
        @endif
    </div>

    {{-- Active Filters Badges --}}
    @if(request('search') || request('sort') || collect($filterOptions)->pluck('name')->first(fn ($n) => request($n)))
    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2 flex-wrap">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Filter aktif:</span>
        @if(request('search'))
        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-teal-50 text-teal-700 text-[10px] font-bold rounded-full border border-teal-200">
            Pencarian: "{{ request('search') }}"
            <a href="{{ request()->fullUrlWithoutQuery('search') }}" class="text-teal-500 hover:text-teal-800">
                <span class="material-symbols-outlined text-[10px]">close</span>
            </a>
        </span>
        @endif
        @if(request('sort'))
        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-bold rounded-full border border-indigo-200">
            Urutan: {{ request('sort') }}
        </span>
        @endif
        @foreach($filterOptions as $filter)
            @if(request($filter['name']))
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 text-[10px] font-bold rounded-full border border-amber-200">
                {{ $filter['label'] }}: {{ $filter['choices'][request($filter['name'])] ?? request($filter['name']) }}
            </span>
            @endif
        @endforeach
    </div>
    @endif
</form>
