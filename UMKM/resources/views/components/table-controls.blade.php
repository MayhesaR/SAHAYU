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
    'showDates' => true,
    'prefix' => '',
])

@php
    $p = $prefix ? "{$prefix}_" : "";
@endphp

<form action="{{ $action }}" method="GET" id="{{ $p }}table-controls-form"
      class="bg-surface-container-lowest rounded-xl shadow-sm border border-surface-container-high p-5">
    <div class="flex flex-wrap items-end gap-4">
        {{-- Search Input --}}
        <div class="flex-1 min-w-[280px]">
            <label for="{{ $p }}tc-search" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">search</span> Pencarian
            </label>
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none z-10 text-slate-400">
                    <span class="material-symbols-outlined text-base">search</span>
                </span>
                <input type="text"
                       id="{{ $p }}tc-search"
                       name="{{ $p }}search"
                       value="{{ request($p . 'search') }}"
                       placeholder="{{ $searchPlaceholder }}"
                       class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500 bg-surface-container-highest text-sm font-medium text-teal-900 placeholder-slate-400 transition-all shadow-sm" />
                @if(request($p . 'search'))
                <a href="{{ $action }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors" title="Hapus pencarian">
                    <span class="material-symbols-outlined text-base">close</span>
                </a>
                @endif
            </div>
        </div>

        {{-- Sort Dropdown --}}
        @if(count($sortOptions) > 0)
        <div class="w-full sm:w-auto min-w-[160px]">
            <label for="{{ $p }}tc-sort" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">sort</span> Urutkan
            </label>
            <select id="{{ $p }}tc-sort"
                    name="{{ $p }}sort"
                    onchange="this.form.submit()"
                    class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer shadow-sm">
                <option value="">Default</option>
                @foreach($sortOptions as $option)
                <option value="{{ $option['value'] }}" {{ request($p . 'sort') === $option['value'] ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Date Range --}}
        @if($showDates)
        <div class="flex items-end gap-2 w-full lg:w-auto">
            <div class="flex-1 lg:w-[150px]">
                <label for="{{ $p }}tc-start-date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                    <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">calendar_today</span> Dari
                </label>
                <input type="date"
                       id="{{ $p }}tc-start-date"
                       name="{{ $p }}start_date"
                       value="{{ request($p . 'start_date') }}"
                       onchange="this.form.submit()"
                       class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer shadow-sm" />
            </div>
            <div class="flex-1 lg:w-[150px]">
                <label for="{{ $p }}tc-end-date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                    <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">event</span> Sampai
                </label>
                <input type="date"
                       id="{{ $p }}tc-end-date"
                       name="{{ $p }}end_date"
                       value="{{ request($p . 'end_date') }}"
                       onchange="this.form.submit()"
                       class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer shadow-sm" />
            </div>
        </div>
        @endif

        {{-- Dynamic Filters --}}
        @foreach($filterOptions as $filter)
        <div class="w-full sm:w-auto min-w-[140px]">
            <label for="{{ $p }}tc-filter-{{ $filter['name'] }}" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">filter_alt</span> {{ $filter['label'] }}
            </label>
            <select id="{{ $p }}tc-filter-{{ $filter['name'] }}"
                    name="{{ $p }}{{ $filter['name'] }}"
                    onchange="this.form.submit()"
                    class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer shadow-sm">
                <option value="">Semua</option>
                @foreach($filter['choices'] as $choiceValue => $choiceLabel)
                <option value="{{ $choiceValue }}" {{ request($p . $filter['name']) === (string) $choiceValue ? 'selected' : '' }}>
                    {{ $choiceLabel }}
                </option>
                @endforeach
            </select>
        </div>
        @endforeach

        {{-- Per Page --}}
        @if($showPerPage)
        <div class="w-full sm:w-auto min-w-[100px]">
            <label for="{{ $p }}tc-per-page" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <span class="material-symbols-outlined text-[10px] align-middle mr-0.5">view_list</span> Baris
            </label>
            <select id="{{ $p }}tc-per-page"
                    name="{{ $p }}per_page"
                    onchange="this.form.submit()"
                    class="w-full bg-surface-container-highest border-none rounded-lg px-4 py-2.5 text-sm font-semibold text-teal-900 focus:ring-2 focus:ring-teal-500/20 transition-all cursor-pointer shadow-sm">
                @foreach([10, 15, 25, 50] as $pp)
                <option value="{{ $pp }}" {{ (int) request($p . 'per_page', 15) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 ml-auto">
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2"
                    style="background-color: #005050 !important; color: #ffffff !important; font-weight: 900;">
                <span class="material-symbols-outlined text-base">search</span>
                <span>Cari</span>
            </button>

            @if(request()->anyFilled(array_map(fn($k) => $p . $k, ['search', 'sort', 'category', 'status', 'per_page', 'start_date', 'end_date', 'type'])))
            <a href="{{ $action }}"
               class="px-6 py-2.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-50 transition-all flex items-center gap-1.5 shadow-sm"
               title="Reset semua filter">
                <span class="material-symbols-outlined text-base">restart_alt</span>
                <span>Reset</span>
            </a>
            @endif
        </div>
    </div>

    {{-- Active Filters Badges --}}
    @if(request($p . 'search') || request($p . 'sort') || request($p . 'start_date') || request($p . 'end_date') || collect($filterOptions)->pluck('name')->first(fn ($n) => request($p . $n)))
    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2 flex-wrap">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Filter aktif:</span>
        @if(request($p . 'search'))
        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-teal-50 text-teal-700 text-[10px] font-bold rounded-full border border-teal-200">
            Pencarian: "{{ request($p . 'search') }}"
            <a href="{{ request()->fullUrlWithoutQuery($p . 'search') }}" class="text-teal-500 hover:text-teal-800">
                <span class="material-symbols-outlined text-[10px]">close</span>
            </a>
        </span>
        @endif
        @if(request($p . 'sort'))
        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-bold rounded-full border border-indigo-200">
            Urutan: {{ request($p . 'sort') }}
        </span>
        @endif
        @if(request($p . 'start_date'))
        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-full border border-blue-200">
            Dari: {{ \Carbon\Carbon::parse(request($p . 'start_date'))->translatedFormat('d M Y') }}
        </span>
        @endif
        @if(request($p . 'end_date'))
        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-full border border-blue-200">
            Sampai: {{ \Carbon\Carbon::parse(request($p . 'end_date'))->translatedFormat('d M Y') }}
        </span>
        @endif
        @foreach($filterOptions as $filter)
            @if(request($p . $filter['name']))
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 text-[10px] font-bold rounded-full border border-amber-200">
                {{ $filter['label'] }}: {{ $filter['choices'][request($p . $filter['name'])] ?? request($p . $filter['name']) }}
            </span>
            @endif
        @endforeach
    </div>
    @endif
</form>
