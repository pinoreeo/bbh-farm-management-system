@props(['history' => [], 'collection'])

@if (count($history) > 0)
    <x-panel :title="'Riwayat Pembaruan ' . $collection" subtitle="Perubahan yang pernah dibuat pada data ini." title-icon="history">
        <ol class="admin-record-history">
            @foreach ($history as $entry)
                <li>
                    <div>
                        <p class="font-medium text-[var(--app-text)]">{{ $entry['action'] }}</p>
                        <p class="mt-1 text-[var(--app-muted)]">{{ $entry['description'] }}</p>
                    </div>
                    <div class="admin-record-history-meta">
                        <span>{{ $entry['admin'] }}</span>
                        <time>{{ $entry['performed_at'] }}</time>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-panel>
@endif
