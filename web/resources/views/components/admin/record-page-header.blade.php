@props([
    'collection',
    'collectionRoute',
    'title',
    'subtitle' => null,
    'record' => null,
    'mode' => null,
    'editRoute' => null,
])

<header class="admin-record-page-header">
    <div>
        <nav class="admin-resource-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span>/</span>
            <a href="{{ $collectionRoute }}">{{ $collection }}</a>
            @if ($record)
                <span>/</span>
                <span>{{ $record }}</span>
            @endif
            @if ($mode)
                <span>/</span>
                <span>{{ $mode }}</span>
            @endif
        </nav>

        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if ($editRoute)
        <a class="ui-btn ui-btn-soft" href="{{ $editRoute }}">
            <x-icons name="edit" class="h-4 w-4" />
            Edit
        </a>
    @endif
</header>
