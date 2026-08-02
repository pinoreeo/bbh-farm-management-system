@props([
    'title',
    'slug',
    'automaticLogs' => false,
    'hideCreateButton' => false,
    'hasAdvancedFilters' => false,
    'activeFilterCount' => 0,
    'createLabel' => 'Tambah Data',
    'reportMap' => [],
    'filterYears' => [],
    'filterMonths' => [],
])

<section class="admin-list-toolbar mb-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="w-full lg:max-w-md" data-live-search-form>
            <label class="relative block">
                <x-icons name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                <input class="ui-input pl-10" type="search" placeholder="Cari {{ strtolower($title) }}..." data-live-search-input>
            </label>
        </div>

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

            @if (isset($reportMap[$slug]))
                <a class="ui-btn ui-btn-soft" href="{{ route('admin.reports.xlsx', ['report' => $reportMap[$slug], 'date_from' => request('date_from'), 'date_to' => request('date_to'), 'year' => request('year'), 'month' => request('month')]) }}">
                    <x-icons name="file" class="h-4 w-4" />
                    Export
                </a>
            @endif

            @if (! $automaticLogs && ! $hideCreateButton)
                <a class="ui-btn ui-btn-primary" href="{{ route('admin.resource.create', ['resource' => $slug]) }}">
                    <x-icons name="plus" class="h-4 w-4" />
                    {{ $createLabel }}
                </a>
            @endif
        </div>
    </div>
</section>
