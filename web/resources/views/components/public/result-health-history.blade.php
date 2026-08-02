@props([
    'records' => [],
    'formatDate',
])

<section class="bbh-result-card mt-7 px-6 py-6 sm:px-8 sm:py-8">
    <h2 class="bbh-result-card-title">Riwayat Kesehatan</h2>
    @if (count($records) > 0)
        <div class="mt-6 grid gap-4">
            @foreach ($records as $record)
                <article class="rounded-[18px] border px-5 py-4" style="border-color: var(--result-border);">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-extrabold text-[#101820]">{{ $record['title'] ?? 'Riwayat kesehatan' }}</p>
                            @if (! empty($record['description']))
                                <p class="mt-2 text-sm font-semibold leading-6 text-[#626c65]">{{ $record['description'] }}</p>
                            @endif
                            @if (! empty($record['notes']))
                                <p class="mt-2 text-sm font-semibold leading-6 text-[#626c65]">{{ $record['notes'] }}</p>
                            @endif
                        </div>
                        <div class="text-sm sm:text-right">
                            <p class="font-extrabold text-[#00aa13]">{{ $record['type'] ?? 'Kesehatan' }}</p>
                            <p class="mt-1 font-semibold text-[#626c65]">{{ $formatDate($record['date'] ?? null) }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <p class="mt-6 rounded-[18px] border px-5 py-4 text-sm font-semibold leading-6 text-[#626c65]" style="border-color: var(--result-border); background: #f5f7f1;">
            Belum ada riwayat kesehatan yang tercatat.
        </p>
    @endif
</section>
