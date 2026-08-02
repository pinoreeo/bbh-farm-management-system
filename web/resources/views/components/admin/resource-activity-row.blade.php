@props(['row'])

@php($result = $row[6] ?? 'Tercatat')
@php($resultClass = match ($result) {
    'Berhasil' => 'audit-badge-success',
    'Ditolak', 'Gagal' => 'audit-badge-danger',
    'Dialihkan' => 'audit-badge-warning',
    default => 'audit-badge-neutral',
})

<td class="whitespace-nowrap">{{ $row[0] ?? '-' }}</td>
<td class="whitespace-nowrap font-medium">{{ $row[1] ?? '-' }}</td>
<td>
    <div class="font-medium text-[var(--app-text)]">{{ str($row[2] ?? '-')->before(' (') }}</div>
    @if (str_contains($row[2] ?? '', '('))
        <div class="mt-0.5 text-xs theme-muted">{{ str($row[2])->between('(', ')') }}</div>
    @endif
</td>
<td><span class="audit-badge audit-badge-module">{{ $row[3] ?? '-' }}</span></td>
<td class="font-medium">{{ $row[4] ?? '-' }}</td>
<td class="max-w-[520px] text-sm leading-6 theme-muted">{{ $row[5] ?? '-' }}</td>
<td><span class="audit-badge {{ $resultClass }}">{{ $result }}</span></td>
<td class="whitespace-nowrap font-mono text-xs">{{ $row[7] ?? '-' }}</td>
