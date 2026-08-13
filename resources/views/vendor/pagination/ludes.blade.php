@if ($paginator->hasPages())
    <nav class="ludes-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-links">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="page-btn disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <i class="bi bi-chevron-left"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="page-btn" rel="prev" aria-label="@lang('pagination.previous')">
                    <i class="bi bi-chevron-left"></i>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="page-btn disabled" aria-disabled="true">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="page-btn active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="page-btn" rel="next" aria-label="@lang('pagination.next')">
                    <i class="bi bi-chevron-right"></i>
                </a>
            @else
                <span class="page-btn disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <i class="bi bi-chevron-right"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
