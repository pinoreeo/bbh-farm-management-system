@props(['columns', 'records', 'slug', 'automaticLogs' => false])

@php
    $columnSpan = count($columns) + ($automaticLogs ? 0 : 1);
    $compactRowStyle = 'height: var(--admin-table-row-height) !important; min-height: var(--admin-table-row-height) !important; max-height: var(--admin-table-row-height) !important;';
    $compactCellStyle = 'height: var(--admin-table-row-height) !important; min-height: var(--admin-table-row-height) !important; max-height: var(--admin-table-row-height) !important; padding-top: 0 !important; padding-bottom: 0 !important; line-height: 1.2 !important;';
@endphp

<div class="overflow-x-auto">
    <table class="ui-table" data-live-search-table data-server-sort>
        <thead>
            <tr>
                @foreach ($columns as $index => $column)
                    @php
                        $isSorted = (string) request('sort') === (string) $index;
                        $sortDirection = $isSorted && request('direction') === 'desc' ? 'descending' : 'ascending';
                        $nextDirection = $isSorted && request('direction') !== 'desc' ? 'desc' : 'asc';
                    @endphp
                    <th data-table-column-index="{{ $index }}" aria-sort="{{ $isSorted ? $sortDirection : 'none' }}">
                        <a class="ui-sort-btn" href="{{ route('admin.' . $slug, array_merge(request()->except('sort', 'direction', 'page'), ['sort' => $index, 'direction' => $nextDirection])) }}" aria-label="Urutkan berdasarkan {{ $column }}">
                            <span>{{ $column }}</span>
                            <span class="ui-sort-icon"></span>
                        </a>
                    </th>
                @endforeach
                @unless ($automaticLogs)
                    <th class="text-right"><span class="sr-only">Aksi</span></th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @if (count($records) === 0)
                <tr>
                    <td colspan="{{ $columnSpan }}" class="text-center theme-muted">{{ request()->filled('q') || request()->filled('account_status') ? 'Tidak ada data yang sesuai dengan pencarian atau filter.' : 'Belum ada data yang dicatat.' }}</td>
                </tr>
            @endif
            @foreach ($records as $record)
                @php
                    $row = $record['cells'];
                    $id = $record['id'];
                @endphp
                <tr data-live-search-row style="{{ $compactRowStyle }}" @if ($slug === 'users') data-table-status="{{ (data_get($record, 'raw.is_active') ?? true) ? 'active' : 'inactive' }}" @endif>
                    @if ($slug === 'activity-logs')
                        <x-admin.resource-activity-row :row="$row" :cell-style="$compactCellStyle" />
                    @elseif ($slug === 'users')
                        @php
                            $name = $row[0] ?? 'Admin';
                            $email = data_get($record, 'raw.email', '-');
                            $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('') ?: 'AD';
                        @endphp
                        <td data-table-column-index="0" style="{{ $compactCellStyle }}">
                            <div class="admin-user-cell">
                                <span class="admin-user-avatar">{{ $initials }}</span>
                                <span>
                                    <strong>{{ $name }}</strong>
                                    <small>{{ $email }}</small>
                                </span>
                            </div>
                        </td>
                        <td data-table-column-index="1" style="{{ $compactCellStyle }}"><span class="admin-role-badge" data-role="{{ strtolower(str_replace(' ', '-', $row[1] ?? 'admin')) }}">{{ $row[1] ?? 'Admin' }}</span></td>
                        <td data-table-column-index="2" style="{{ $compactCellStyle }}"><span class="admin-status-badge" data-status="{{ ($row[2] ?? '') === 'Aktif' ? 'active' : 'inactive' }}">{{ $row[2] ?? '-' }}</span></td>
                        <td data-table-column-index="3" style="{{ $compactCellStyle }}">{{ ($row[3] ?? '-') === '-' ? 'Belum pernah masuk' : $row[3] }}</td>
                    @else
                        @foreach ($row as $index => $cell)
                            <td data-table-column-index="{{ $index }}" style="{{ $compactCellStyle }}">{{ $cell }}</td>
                        @endforeach
                    @endif
                    @unless ($automaticLogs)
                        <td class="text-right" style="{{ $compactCellStyle }}">
                            <x-admin.resource-row-actions :slug="$slug" :id="$id" :row="$row" :record="$record" />
                        </td>
                    @endunless
                </tr>
            @endforeach
            @if (count($records) > 0)
                <tr data-live-search-empty hidden>
                    <td colspan="{{ $columnSpan }}" class="text-center theme-muted">Tidak ada data yang sesuai dengan pencarian.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
