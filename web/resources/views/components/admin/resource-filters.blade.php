@props([
    'slug',
    'automaticLogs' => false,
    'activeFilterCount' => 0,
    'reportMap' => [],
    'filterYears' => [],
    'filterMonths' => [],
])

<details class="admin-filter-popover" @if($activeFilterCount > 0) open @endif>
    <summary class="ui-btn ui-btn-soft cursor-pointer select-none">
        <x-icons name="filter" class="h-4 w-4" />
        Filter
        @if ($activeFilterCount > 0)
            <span class="ui-badge bg-[var(--app-accent)] text-white">{{ $activeFilterCount }}</span>
        @endif
    </summary>
    <div class="admin-filter-panel">
        <form class="grid gap-3 md:grid-cols-2 xl:grid-cols-4" method="get">
            @if ($slug === 'animals')
                <label>
                    <span class="ui-filter-label">Jenis kelamin</span>
                    <select class="ui-input" name="sex">
                        <option value="">Semua</option>
                        <option value="male" @selected(request('sex') === 'male')>Jantan</option>
                        <option value="female" @selected(request('sex') === 'female')>Betina</option>
                    </select>
                </label>
                <label>
                    <span class="ui-filter-label">Status hidup</span>
                    <select class="ui-input" name="life_status">
                        <option value="">Semua</option>
                        <option value="alive" @selected(request('life_status') === 'alive')>Hidup</option>
                        <option value="dead" @selected(request('life_status') === 'dead')>Mati</option>
                    </select>
                </label>
                <label>
                    <span class="ui-filter-label">Status ternak</span>
                    <select class="ui-input" name="exit_status">
                        <option value="">Semua</option>
                        @foreach (['sold' => 'Dijual', 'culled' => 'Afkir / Tidak Produktif', 'lost' => 'Hilang'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('exit_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @elseif ($slug === 'pens')
                <label>
                    <span class="ui-filter-label">Fase koloni</span>
                    <select class="ui-input" name="colony_phase">
                        <option value="">Semua</option>
                        @foreach (['koloni_kawin' => 'Kawin', 'koloni_bunting' => 'Bunting', 'koloni_kering' => 'Kering', 'koloni_laktasi' => 'Laktasi', 'koloni_anak' => 'Anak'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('colony_phase') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            @if ($automaticLogs)
                <label>
                    <span class="ui-filter-label">Tahun</span>
                    <select class="ui-input" name="year">
                        @foreach ($filterYears as $year)
                            <option value="{{ $year }}" @selected((string) request('year', $filterYears[0] ?? '') === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="ui-filter-label">Bulan</span>
                    <select class="ui-input" name="month">
                        <option value="">Semua bulan</option>
                        @foreach ($filterMonths as $month)
                            <option value="{{ $month }}" @selected((string) request('month') === (string) $month)>{{ \Illuminate\Support\Carbon::create()->month($month)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </label>
            @elseif (isset($reportMap[$slug]))
                <label>
                    <span class="ui-filter-label">Dari tanggal</span>
                    <input class="ui-input" type="date" name="date_from" value="{{ request('date_from') }}">
                </label>
                <label>
                    <span class="ui-filter-label">Sampai tanggal</span>
                    <input class="ui-input" type="date" name="date_to" value="{{ request('date_to') }}">
                </label>
            @endif

            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-1">
                <button class="ui-btn ui-btn-primary flex-1" type="submit">Terapkan</button>
                <a class="ui-btn ui-btn-soft flex-1" href="{{ url()->current() }}">Reset</a>
            </div>
        </form>
    </div>
</details>
