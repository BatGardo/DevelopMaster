@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pagination">
        <div class="pagination__stats">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="pagination__stats-number">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="pagination__stats-number">{{ $paginator->lastItem() }}</span>
            @else
                <span class="pagination__stats-number">{{ $paginator->count() }}</span>
            @endif
            {!! __('of') !!}
            <span class="pagination__stats-number">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </div>

        <ul class="pagination__list" role="list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pagination__control pagination__control--disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        <span class="sr-only">{{ __('pagination.previous') }}</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination__control" aria-label="{{ __('pagination.previous') }}">
                        <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        <span class="sr-only">{{ __('pagination.previous') }}</span>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pagination__dots" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="pagination__page pagination__page--current" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pagination__page" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination__control" aria-label="{{ __('pagination.next') }}">
                        <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        <span class="sr-only">{{ __('pagination.next') }}</span>
                    </a>
                @else
                    <span class="pagination__control pagination__control--disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        <span class="sr-only">{{ __('pagination.next') }}</span>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
