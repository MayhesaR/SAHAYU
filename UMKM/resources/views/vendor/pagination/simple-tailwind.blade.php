@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-2 items-center justify-between py-2">

        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-stone-400 bg-stone-50 border border-stone-150 cursor-not-allowed rounded-xl leading-5">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-stone-700 bg-white border border-stone-200 leading-5 rounded-xl hover:bg-stone-50 hover:text-emerald-700 hover:border-emerald-600 transition-all duration-150 shadow-sm active:scale-95">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-stone-700 bg-white border border-stone-200 leading-5 rounded-xl hover:bg-stone-50 hover:text-emerald-700 hover:border-emerald-600 transition-all duration-150 shadow-sm active:scale-95">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-stone-400 bg-stone-50 border border-stone-150 cursor-not-allowed rounded-xl leading-5">
                {!! __('pagination.next') !!}
            </span>
        @endif

    </nav>
@endif
