@php
    $cardResources = ['pens', 'breeding-periods', 'breeding-females', 'pregnancy-checks', 'birth-events', 'offspring-births'];
    $cardLayout = in_array($slug, $cardResources, true);
@endphp

<x-layouts.admin :title="$title" :skeleton="$cardLayout ? 'cards' : 'table'" :page-header="false">
    @php
        $automaticLogs = in_array($slug, ['certificate-logs', 'activity-logs'], true);
        $hideCreateButton = $automaticLogs || in_array($slug, ['pregnancy-checks', 'breeding-females'], true);
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

    <header class="admin-resource-page-header">
        <div>
            <nav class="admin-resource-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>/</span>
                <span>{{ $title }}</span>
            </nav>
            <h1>{{ $title }}</h1>
            <p>{{ $subtitle }}</p>
        </div>

        @if (! $automaticLogs && ! $hideCreateButton)
            <a class="ui-btn ui-btn-primary" href="{{ route('admin.resource.create', ['resource' => $slug]) }}">
                <x-icons name="plus" class="h-4 w-4" />
                {{ $createLabel }}
            </a>
        @endif
    </header>

    <x-admin.resource-toolbar
        :title="$title"
        :slug="$slug"
        :columns="$columns"
        :automatic-logs="$automaticLogs"
        :card-layout="$cardLayout"
        :hide-create-button="$hideCreateButton"
        :has-advanced-filters="$hasAdvancedFilters"
        :active-filter-count="$activeFilterCount"
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
    @elseif (! empty($dataTruncated))
        <div class="admin-alert admin-alert-warning">
            <p class="font-semibold">Sebagian Data Belum Ditampilkan</p>
            <p class="mt-1 theme-muted">Jumlah catatan melebihi batas tampilan. Hubungi pengelola sistem bila data yang dicari belum terlihat.</p>
        </div>
    @endif

    @if (empty($apiFailureMessage))
        @if ($automaticLogs)
            <x-panel :padded="false" class="admin-log-feed-panel">
                <x-admin.resource-log-list :slug="$slug" :records="$records" />
            </x-panel>
        @elseif ($cardLayout)
            <x-admin.resource-card-list :slug="$slug" :records="$records" :period-female-counts="$periodFemaleCounts ?? []" />
        @else
            <x-panel :padded="false" class="admin-data-panel">
                <x-admin.resource-table
                    :columns="$columns"
                    :records="$records"
                    :slug="$slug"
                    :automatic-logs="$automaticLogs"
                />
            </x-panel>
        @endif
    @endif

    @if (empty($apiFailureMessage) && $records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->total() > 0)
        <x-admin.pagination :paginator="$records" />
    @endif
</x-layouts.admin>
