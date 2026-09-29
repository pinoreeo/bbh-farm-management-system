@props(['slug', 'records', 'periodFemaleCounts' => []])

@php
    $resourceIcons = [
        'pens' => 'home',
        'breeding-periods' => 'calendar',
        'breeding-females' => 'female',
        'pregnancy-checks' => 'pregnancy',
        'birth-events' => 'birth',
        'offspring-births' => 'baby',
    ];
@endphp

<div class="admin-resource-card-grid" data-live-search-table>
    @forelse ($records as $record)
        @php
            $row = $record['cells'] ?? [];
            $id = $record['id'];
            $card = match ($slug) {
                'pens' => [
                    'eyebrow' => 'Kandang',
                    'title' => $row[0] ?? '-',
                    'subtitle' => trim(($row[1] ?? '-') . ' - ' . ($row[2] ?? '-')),
                    'status' => $row[6] ?? '-',
                    'fields' => [['Fase koloni', $row[3] ?? '-'], ['Lokasi', $row[4] ?? '-'], ['Kapasitas', ($row[5] ?? '-') . ' ekor']],
                    'note' => null,
                ],
                'breeding-periods' => [
                    'eyebrow' => 'Periode kawin',
                    'title' => $row[0] ?? '-',
                    'subtitle' => 'Kandang ' . ($row[1] ?? '-'),
                    'status' => $row[5] ?? '-',
                    'fields' => [['Pejantan', $row[4] ?? '-'], ['Betina terdaftar', ($periodFemaleCounts[$id] ?? 0) . ' betina'], ['Mulai', $row[2] ?? '-'], ['Berakhir', $row[3] ?? '-']],
                    'note' => null,
                ],
                'breeding-females' => [
                    'eyebrow' => 'Betina kawin',
                    'title' => $row[1] ?? '-',
                    'subtitle' => 'Periode ' . ($row[0] ?? '-'),
                    'status' => $row[5] ?? '-',
                    'fields' => [['Masuk periode', $row[2] ?? '-'], ['Tanggal kawin', $row[3] ?? '-'], ['Perkiraan lahir', $row[4] ?? '-']],
                    'note' => ($row[6] ?? '-') !== '-' ? 'Keluar: ' . ($row[6] ?? '-') . ' - ' . ($row[7] ?? '-') : null,
                ],
                'pregnancy-checks' => [
                    'eyebrow' => 'Kebuntingan',
                    'title' => $row[0] ?? '-',
                    'subtitle' => 'Kandang ' . ($row[1] ?? '-'),
                    'status' => $row[5] ?? '-',
                    'fields' => [['Pejantan', $row[2] ?? '-'], ['Mulai', $row[3] ?? '-'], ['Berakhir', $row[4] ?? '-']],
                    'note' => null,
                ],
                'birth-events' => [
                    'eyebrow' => 'Kelahiran',
                    'title' => $row[2] ?? '-',
                    'subtitle' => 'Induk ' . ($row[1] ?? '-') . ' - Pejantan ' . ($row[0] ?? '-'),
                    'status' => $row[5] ?? '-',
                    'fields' => [['Waktu', $row[3] ?? '-'], ['Jumlah cempe', $row[4] ?? '-'], ['Lokasi', $row[6] ?? '-']],
                    'note' => null,
                ],
                default => [
                    'eyebrow' => 'Cempe lahir',
                    'title' => $row[1] ?? '-',
                    'subtitle' => 'Tanggal lahir ' . ($row[0] ?? '-'),
                    'status' => $row[4] ?? '-',
                    'fields' => [['Berat lahir', ($row[2] ?? '-') . ' kg'], ['Grade', $row[3] ?? '-']],
                    'note' => ($row[5] ?? '-') !== '-' ? $row[5] : null,
                ],
            };
            $status = $card['status'];
            $normalizedStatus = strtolower($status);
            $statusTone = str_contains($normalizedStatus, 'cabut') || str_contains($normalizedStatus, 'gagal') || str_contains($normalizedStatus, 'mati')
                ? 'negative'
                : (in_array($normalizedStatus, ['aktif', 'hidup', 'bunting', 'kawin', 'normal'], true) ? 'positive' : 'neutral');
            $showRoute = route('admin.resource.show', ['resource' => $slug, 'id' => $id]);
        @endphp

        <article class="admin-resource-card" data-live-search-row>
            <header class="admin-resource-card-header">
                <div class="min-w-0">
                    <p class="admin-resource-card-eyebrow">
                        <x-icons :name="$resourceIcons[$slug] ?? 'file'" class="h-4 w-4" />
                        {{ $card['eyebrow'] }}
                    </p>
                    <a href="{{ $showRoute }}" class="admin-resource-card-title">{{ $card['title'] }}</a>
                    <p class="admin-resource-card-subtitle">{{ $card['subtitle'] }}</p>
                </div>
                <div class="flex shrink-0 items-start gap-2">
                    @if ($status !== '-')
                        <span class="admin-resource-card-status" data-tone="{{ $statusTone }}">{{ $status }}</span>
                    @endif
                    <x-admin.resource-row-actions :slug="$slug" :id="$id" :row="$row" :record="$record" />
                </div>
            </header>

            <dl class="admin-resource-card-fields">
                @foreach ($card['fields'] as [$label, $value])
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($card['note'])
                <p class="admin-resource-card-note">{{ $card['note'] }}</p>
            @endif

        </article>
    @empty
        <div class="admin-resource-card-empty">{{ request()->filled('q') ? 'Tidak ada data yang sesuai dengan pencarian.' : 'Belum ada data yang dicatat.' }}</div>
    @endforelse

</div>
