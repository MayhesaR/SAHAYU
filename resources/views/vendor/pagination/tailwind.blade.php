@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between py-2">
        
        <!-- Mobile Layout -->
        <div class="flex gap-3 items-center justify-between w-full sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-stone-400 bg-stone-50 border border-stone-150 cursor-not-allowed rounded-xl leading-5">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-stone-700 bg-white border border-stone-200 leading-5 rounded-xl hover:bg-stone-50 hover:text-emerald-700 hover:border-emerald-600 transition-all duration-150 shadow-sm active:scale-95">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            <span class="text-xs font-semibold text-stone-500">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-stone-700 bg-white border border-stone-200 leading-5 rounded-xl hover:bg-stone-50 hover:text-emerald-700 hover:border-emerald-600 transition-all duration-150 shadow-sm active:scale-95">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-stone-400 bg-stone-50 border border-stone-150 cursor-not-allowed rounded-xl leading-5">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        <!-- Desktop Layout -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs text-stone-500 leading-5 font-semibold">
                    {!! __('Menampilkan') !!}
                    @if ($paginator->firstItem())
                        <span class="font-extrabold text-emerald-900">{{ $paginator->firstItem() }}</span>
                        {!! __('sampai') !!}
                        <span class="font-extrabold text-emerald-900">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('dari') !!}
                    <span class="font-extrabold text-emerald-900">{{ $paginator->total() }}</span>
                    {!! __('data') !!}
                </p>
            </div>

            <div>
                <div class="flex items-center gap-1.5">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center border bg-stone-50 border-stone-150 text-stone-300 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="w-9 h-9 rounded-xl flex items-center justify-center border bg-white border-stone-200 text-stone-600 hover:bg-stone-50 hover:text-emerald-700 hover:border-emerald-600 hover:scale-[1.03] transition-all duration-150 shadow-sm active:scale-95" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="w-9 h-9 flex items-center justify-center text-stone-400 text-xs font-bold">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#0b6e4f] text-white border border-[#0b6e4f] font-black text-xs shadow-md shadow-emerald-900/10 scale-105 select-none">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="w-9 h-9 rounded-xl flex items-center justify-center border bg-white border-stone-200 text-stone-600 hover:bg-stone-50 hover:text-emerald-700 hover:border-emerald-600 hover:scale-[1.03] font-semibold text-xs transition-all duration-150 shadow-sm active:scale-95" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="w-9 h-9 rounded-xl flex items-center justify-center border bg-white border-stone-200 text-stone-600 hover:bg-stone-50 hover:text-emerald-700 hover:border-emerald-600 hover:scale-[1.03] transition-all duration-150 shadow-sm active:scale-95" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center border bg-stone-50 border-stone-150 text-stone-300 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </nav>
@endif
