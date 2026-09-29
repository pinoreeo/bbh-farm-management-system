@props(['slug', 'records'])

@php
    $items = $records instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $records->getCollection()
        : collect($records ?? []);
    $total = $records instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $records->total()
        : $items->count();
    $heading = $slug === 'activity-logs' ? 'Semua log aktivitas' : 'Semua log sertifikat';
@endphp

<div class="admin-log-feed" data-live-search-table>
    <div class="admin-log-feed-heading">
        <h2>{{ $heading }}</h2>
        <span>{{ $total }} data</span>
    </div>

    <div class="admin-log-feed-list">
        @forelse ($items as $record)
            @php
                $row = $record['cells'] ?? [];
                $isActivityLog = $slug === 'activity-logs';
                $result = $isActivityLog ? ($row[6] ?? 'Tercatat') : ($row[4] ?? 'Tercatat');
                $isFailure = in_array($result, ['Gagal', 'Ditolak', 'Tidak Valid'], true);
                $title = $isActivityLog
                    ? trim(($row[4] ?? 'Aktivitas') . ' - ' . ($row[3] ?? 'Sistem'))
                    : 'Verifikasi sertifikat ' . ($row[0] ?? '-');
                $body = $isActivityLog
                    ? trim(($row[2] ?? 'Sistem') . ' - ' . ($row[5] ?? 'Tidak ada detail'))
                    : trim(($row[3] ?? 'Verifikasi') . ' - ' . (($row[5] ?? '-') === '-' ? $result : $row[5]));
                $time = $isActivityLog
                    ? trim(($row[0] ?? '-') . ' ' . ($row[1] ?? ''))
                    : trim(($row[1] ?? '-') . ' ' . ($row[2] ?? ''));
                $ip = $isActivityLog ? ($row[7] ?? '-') : ($row[6] ?? '-');
            @endphp

            <article class="admin-log-feed-item" data-live-search-row data-log-result="{{ $isFailure ? 'failure' : 'success' }}">
                <div class="min-w-0 flex-1">
                    <h3>
                        {{ $title }}
                        <span>{{ $result }}</span>
                    </h3>
                    <p class="admin-log-feed-body">{{ $body }}</p>
                    <p class="admin-log-feed-time">{{ $time }}@if ($ip !== '-') - {{ $ip }}@endif</p>
                </div>
            </article>
        @empty
            <div class="admin-log-feed-empty">{{ request()->filled('q') ? 'Tidak ada log yang sesuai dengan pencarian.' : 'Belum ada log yang dicatat pada periode ini.' }}</div>
        @endforelse

    </div>
</div>
