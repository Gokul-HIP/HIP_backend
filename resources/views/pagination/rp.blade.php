@if ($paginator->hasPages())
    <nav class="rp-pagination" role="navigation" aria-label="Pagination Navigation">
        <ul class="rp-pagination-list">
            @if ($paginator->onFirstPage())
                <li><button type="button" class="rp-page-button is-disabled" disabled aria-label="Previous page">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12.5 4.5-5 5.5 5 5.5" /></svg>
                </button></li>
            @else
                <li><button type="button" class="rp-page-button" wire:click="previousPage('{{ $paginator->getPageName() }}')" aria-label="Previous page">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m12.5 4.5-5 5.5 5 5.5" /></svg>
                </button></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="rp-page-gap" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><button type="button" class="rp-page-button is-active" aria-current="page" aria-label="Page {{ $page }}" disabled>{{ $page }}</button></li>
                        @else
                            <li><button type="button" class="rp-page-button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" aria-label="Page {{ $page }}">{{ $page }}</button></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><button type="button" class="rp-page-button" wire:click="nextPage('{{ $paginator->getPageName() }}')" aria-label="Next page">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 4.5 5 5.5-5 5.5" /></svg>
                </button></li>
            @else
                <li><button type="button" class="rp-page-button is-disabled" disabled aria-label="Next page">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m7.5 4.5 5 5.5-5 5.5" /></svg>
                </button></li>
            @endif
        </ul>
    </nav>
@endif
