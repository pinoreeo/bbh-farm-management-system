@props([
    'records' => [],
    'formatDate',
])

<section class="bbh-result-card mt-7 px-6 py-6 sm:px-8 sm:py-8">
    <h2 class="bbh-result-card-title">Riwayat Kesehatan</h2>
    @if (count($records) > 0)
        <div class="mt-6 grid gap-4">
            @foreach ($records as $record)
                <article class="rounded-[12px] border px-5 py-4" style="border-color: var(--result-border);">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="bbh-result-value font-semibold">{{ $record['title'] ?? 'Riwayat kesehatan' }}</p>
                            @if (! empty($record['description']))
                                <p class="bbh-result-muted mt-2">{{ $record['description'] }}</p>
                            @endif
                            @if (! empty($record['notes']))
                                <p class="bbh-result-muted mt-2">{{ $record['notes'] }}</p>
                            @endif
                        </div>
                        <div class="sm:text-right">
                            <p class="bbh-result-value font-semibold text-[var(--bbh-text)]">{{ $record['type'] ?? 'Kesehatan' }}</p>
                            <p class="bbh-result-muted mt-1">{{ $formatDate($record['date'] ?? null) }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <p class="bbh-result-muted mt-6 rounded-[12px] border px-5 py-4" style="border-color: var(--result-border); background: #f7faf4;">
            Belum ada riwayat kesehatan yang tercatat.
        </p>
    @endif
</section>
