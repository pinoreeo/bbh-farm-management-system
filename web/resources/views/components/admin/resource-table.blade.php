@props(['columns', 'records', 'slug', 'automaticLogs' => false])

<div class="overflow-x-auto">
    <table class="ui-table" data-live-search-table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
                @unless ($automaticLogs)
                    <th class="text-right">Aksi</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @if (count($records) === 0)
                <tr>
                    <td colspan="{{ count($columns) + ($automaticLogs ? 0 : 1) }}" class="text-center theme-muted">Belum ada data yang tersedia.</td>
                </tr>
            @endif
            @foreach ($records as $record)
                @php($row = $record['cells'])
                @php($id = $record['id'])
                <tr data-live-search-row>
                    @if ($slug === 'activity-logs')
                        <x-admin.resource-activity-row :row="$row" />
                    @else
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    @endif
                    @unless ($automaticLogs)
                        <td class="text-right">
                            <x-admin.resource-row-actions :slug="$slug" :id="$id" :row="$row" :record="$record" />
                        </td>
                    @endunless
                </tr>
            @endforeach
            @if (count($records) > 0)
                <tr data-live-search-empty hidden>
                    <td colspan="{{ count($columns) + ($automaticLogs ? 0 : 1) }}" class="text-center theme-muted">Tidak ada data yang cocok.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
