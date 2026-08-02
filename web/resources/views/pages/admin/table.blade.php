<x-layouts.admin :title="$title" skeleton="table">
    @php
        $automaticLogs = in_array($slug, ['certificate-logs', 'activity-logs'], true);
        $hideCreateButton = $automaticLogs || $slug === 'pregnancy-checks';
        $reportMap = [
            'animals' => 'animals',
            'weight-records' => 'weights',
            'pens' => 'pens',
            'pen-movements' => 'pen-movements',
            'breeding-periods' => 'breeding',
            'breeding-females' => 'breeding-females',
            'pregnancy-checks' => 'pregnancies',
            'birth-events' => 'births',
            'offspring-births' => 'offsprings',
            'health-treatments' => 'health',
            'vaccinations' => 'vaccinations',
            'activity-logs' => 'activity-logs',
        ];
        $hasAdvancedFilters = $slug === 'animals'
            || $slug === 'pens'
            || $automaticLogs
            || isset($reportMap[$slug]);
        $activeFilterKeys = match (true) {
            $slug === 'animals' => ['sex', 'life_status', 'exit_status', 'date_from', 'date_to'],
            $slug === 'pens' => ['colony_phase', 'date_from', 'date_to'],
            $automaticLogs => ['year', 'month'],
            isset($reportMap[$slug]) => ['date_from', 'date_to'],
            default => [],
        };
        $activeFilterCount = collect($activeFilterKeys)->filter(fn ($key) => request()->filled($key))->count();
        $createLabel = match ($slug) {
            'certificates' => 'Terbitkan',
            'users' => 'Tambah Admin',
            default => 'Tambah Data',
        };
    @endphp

    <x-admin.resource-toolbar
        :title="$title"
        :slug="$slug"
        :automatic-logs="$automaticLogs"
        :hide-create-button="$hideCreateButton"
        :has-advanced-filters="$hasAdvancedFilters"
        :active-filter-count="$activeFilterCount"
        :create-label="$createLabel"
        :report-map="$reportMap"
        :filter-years="$filterYears ?? []"
        :filter-months="$filterMonths ?? []"
    />

    <x-admin.resource-flash />

    @if (! empty($apiFailureMessage))
        <div class="admin-alert admin-alert-danger">
            <p class="font-semibold">Data Tidak Dapat Dimuat</p>
            <p class="mt-1 theme-muted">{{ $apiFailureMessage }}</p>
        </div>
    @endif

    <x-panel :title="$title">
        <x-admin.resource-table
            :columns="$columns"
            :records="$records"
            :slug="$slug"
            :automatic-logs="$automaticLogs"
        />
    </x-panel>
</x-layouts.admin>
