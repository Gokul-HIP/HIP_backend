@props([
    'paginator',
    'label' => 'items',
])

@if($paginator->total() > 0)
    <div {{ $attributes->merge(['class' => 'mp-table-footer']) }}>
        <div class="mp-showing">
            Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong> {{ $label }}
        </div>
        @if($paginator->hasPages())
            <div class="mp-pages">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endif
