@if ($paginator->hasPages())
    <nav>
        <small>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</small>
        <span style="display:flex;gap:6px">
            @if ($paginator->onFirstPage())<span class="btn btn-sm" aria-disabled="true" style="opacity:.5">Previous</span>@else<a class="btn btn-sm" href="{{ $paginator->previousPageUrl() }}">Previous</a>@endif
            @if ($paginator->hasMorePages())<a class="btn btn-sm" href="{{ $paginator->nextPageUrl() }}">Next</a>@else<span class="btn btn-sm" aria-disabled="true" style="opacity:.5">Next</span>@endif
        </span>
    </nav>
@endif
