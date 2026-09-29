@props(['paginator'])

<nav class="admin-pagination" aria-label="Navigasi halaman">
    <p>Menampilkan {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data</p>

    @if ($paginator->hasPages())
        @php
            $firstPage = max(1, $paginator->currentPage() - 1);
            $lastPage = min($paginator->lastPage(), $paginator->currentPage() + 1);
        @endphp

        <div class="admin-pagination-actions">
            @if ($paginator->onFirstPage())
                <span class="admin-pagination-button is-disabled">Sebelumnya</span>
            @else
                <a class="admin-pagination-button" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>
            @endif

            @for ($page = $firstPage; $page <= $lastPage; $page++)
                @if ($page === $paginator->currentPage())
                    <span class="admin-pagination-button is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="admin-pagination-button" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($paginator->hasMorePages())
                <a class="admin-pagination-button" href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="admin-pagination-button is-disabled">Berikutnya</span>
            @endif
        </div>
    @endif
</nav>
