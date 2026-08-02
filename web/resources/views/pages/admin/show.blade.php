<x-layouts.admin :title="$pageTitle" skeleton="detail">
    <div class="admin-page-actions">
        <a class="ui-btn ui-btn-soft" href="{{ route('admin.' . $slug) }}">
            <x-icons name="arrow-left" class="h-4 w-4" />
            Kembali
        </a>
        <div class="admin-inline-actions">
            <a class="ui-btn ui-btn-primary" href="{{ route('admin.resource.edit', ['resource' => $slug, 'id' => $id]) }}">
                <x-icons name="edit" class="h-4 w-4" />
                Edit
            </a>
        </div>
    </div>

    <x-panel :title="$pageTitle">
        <dl class="grid gap-4 md:grid-cols-2">
            @foreach ($columns as $index => $column)
                <div class="admin-detail-item">
                    <dt class="text-xs font-semibold uppercase tracking-wide theme-muted">{{ $column }}</dt>
                    <dd class="mt-2 text-sm font-semibold" style="color: var(--app-text);">{{ $row[$index] ?? '-' }}</dd>
                </div>
            @endforeach
        </dl>
    </x-panel>
</x-layouts.admin>
