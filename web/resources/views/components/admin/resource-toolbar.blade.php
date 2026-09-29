@props([
    'title',
    'slug',
    'columns' => [],
    'automaticLogs' => false,
    'cardLayout' => false,
    'hideCreateButton' => false,
    'hasAdvancedFilters' => false,
    'activeFilterCount' => 0,
    'reportMap' => [],
    'filterYears' => [],
    'filterMonths' => [],
])

<section class="admin-list-toolbar mb-5">
    @if ($slug === 'users')
        <div class="admin-list-toolbar-actions">
            <nav class="admin-resource-tabs" aria-label="Status akun">
                @foreach (['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'] as $status => $label)
                    <a href="{{ route('admin.users', array_merge(request()->except('account_status', 'page'), $status === 'all' ? [] : ['account_status' => $status])) }}" @if (request('account_status', 'all') === $status) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    @endif

    <div class="admin-list-toolbar-controls">
        <form class="flex w-full items-center gap-2 sm:max-w-md" method="get" action="{{ route('admin.' . $slug) }}" role="search">
            @foreach (request()->except('q', 'page') as $key => $value)
                @if (is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Cari {{ strtolower($title) }}</span>
                <x-icons name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                <input class="ui-input pl-10" type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Cari {{ strtolower($title) }}...">
            </label>
            <button class="ui-btn ui-btn-soft" type="submit">Cari</button>
        </form>

        <div class="flex flex-wrap items-center gap-2">
            @if ($hasAdvancedFilters)
                <x-admin.resource-filters
                    :slug="$slug"
                    :automatic-logs="$automaticLogs"
                    :active-filter-count="$activeFilterCount"
                    :report-map="$reportMap"
                    :filter-years="$filterYears"
                    :filter-months="$filterMonths"
                />
            @endif

            @unless ($automaticLogs || $cardLayout)
                <details class="admin-column-popover" data-table-column-menu>
                    <summary class="ui-btn ui-btn-soft cursor-pointer select-none">Kolom</summary>
                    <div class="admin-column-panel">
                        @foreach ($columns as $index => $column)
                            <label>
                                <input type="checkbox" value="{{ $index }}" checked>
                                <span>{{ $column }}</span>
                            </label>
                        @endforeach
                    </div>
                </details>
            @endunless

            @if (isset($reportMap[$slug]))
                <a class="ui-btn ui-btn-soft" href="{{ route('admin.reports.xlsx', ['report' => $reportMap[$slug], 'date_from' => request('date_from'), 'date_to' => request('date_to'), 'year' => request('year'), 'month' => request('month')]) }}" data-no-skeleton>
                    Export
                </a>
            @endif

        </div>
    </div>
</section>
