<x-layouts.admin title="Dashboard" subtitle="Ringkasan data pencatatan peternakan." skeleton="dashboard">
    @php
        $topStats = $dashboard['stats'] ?? [];
        $showActivities = $dashboard['showActivities'] ?? false;
        $displayDate = static fn ($value) => $value === '-' || blank($value)
            ? '-'
            : \Illuminate\Support\Carbon::parse($value)->locale('id')->translatedFormat('d M Y');
        $displayDateTime = static fn ($value) => $value === '-' || blank($value)
            ? '-'
            : \Illuminate\Support\Carbon::parse($value)->locale('id')->translatedFormat('d M Y, H:i');
    @endphp

    @if (! empty($dashboard['apiFailureMessage']))
        <div class="admin-alert admin-alert-danger">
            <p class="font-semibold">Data dashboard belum lengkap</p>
            <p class="mt-1 theme-muted">{{ $dashboard['apiFailureMessage'] }}</p>
        </div>
    @endif

    @if ($topStats !== [])
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($topStats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" :tone="$stat['tone']" :trend="$stat['trend'] ?? []" />
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <x-panel title="Ringkasan Kelahiran" subtitle="Catatan kelahiran dan jumlah cempe per bulan." class="xl:col-span-8">
            <x-slot:actions>
                <div class="dashboard-chart-actions">
                    <span>Bulanan</span>
                    <form method="get" data-skeleton-target="dashboard">
                        <label>
                            <span class="sr-only">Filter tahun kelahiran</span>
                            <select class="dashboard-chart-select" name="birth_year" onchange="this.form.submit()">
                            @foreach ($dashboard['birthYears'] as $year)
                                <option value="{{ $year }}" @selected((int) ($dashboard['selectedBirthYear'] ?? $year) === (int) $year)>{{ $year }}</option>
                            @endforeach
                            </select>
                        </label>
                    </form>
                </div>
            </x-slot:actions>

            <div class="dashboard-chart-shell" data-dashboard-chart>
                @php
                    $birthValues = array_values(array_map('intval', $dashboard['birthChart'] ?? []));
                    $birthValues = $birthValues !== [] ? $birthValues : array_fill(0, 12, 0);
                    $offspringValues = array_values(array_map('intval', $dashboard['offspringChart'] ?? []));
                    $offspringValues = $offspringValues !== [] ? $offspringValues : array_fill(0, 12, 0);
                    $maxBirthValue = max(1, max($birthValues));
                    $maxOffspringValue = max(1, max($offspringValues));
                    $offspringChartSvg = $dashboard['offspringChartSvg'] ?? ['line' => '', 'area' => '', 'points' => []];
                    $monthLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    $birthTicks = array_values(array_unique([
                        $maxBirthValue,
                        (int) ceil($maxBirthValue * .75),
                        (int) ceil($maxBirthValue * .5),
                        (int) ceil($maxBirthValue * .25),
                        0,
                    ]));
                    rsort($birthTicks);
                    $offspringTicks = array_values(array_unique([
                        $maxOffspringValue,
                        (int) ceil($maxOffspringValue * .75),
                        (int) ceil($maxOffspringValue * .5),
                        (int) ceil($maxOffspringValue * .25),
                        0,
                    ]));
                    rsort($offspringTicks);
                    $chartLeft = 82;
                    $chartRight = 850;
                    $chartTop = 42;
                    $chartBottom = 304;
                    $chartStep = ($chartRight - $chartLeft) / 11;
                    $selectedYear = (int) ($dashboard['selectedBirthYear'] ?? now()->year);
                    $highlightIndex = $selectedYear === now()->year ? now()->month - 1 : 11;
                    $highlightIndex = max(0, min(11, $highlightIndex));
                    $highlightPoint = $offspringChartSvg['points'][$highlightIndex] ?? ['x' => $chartLeft, 'y' => $chartBottom];
                @endphp
                <div class="dashboard-chart-tooltip" data-dashboard-chart-tooltip aria-live="polite">
                    <span data-dashboard-chart-month>{{ $monthLabels[$highlightIndex] }}</span>
                    <strong><i class="dashboard-chart-legend-dot dashboard-chart-legend-dot-events"></i><b data-dashboard-chart-events>{{ $birthValues[$highlightIndex] }}</b> peristiwa kelahiran</strong>
                    <strong><i class="dashboard-chart-legend-dot dashboard-chart-legend-dot-offspring"></i><b data-dashboard-chart-offspring>{{ $offspringValues[$highlightIndex] }}</b> cempe lahir</strong>
                </div>
                <div class="dashboard-chart-guide" data-dashboard-chart-guide style="left: {{ 8.9 + (($highlightIndex / 11) * 83.5) }}%;"></div>
                <svg class="dashboard-combo-chart" viewBox="0 0 920 390" preserveAspectRatio="none" aria-label="Grafik peristiwa kelahiran dan cempe lahir per bulan">
                    <defs>
                        <linearGradient id="offspringTrendArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#D4613E" stop-opacity=".24" />
                            <stop offset="70%" stop-color="#D4613E" stop-opacity=".06" />
                            <stop offset="100%" stop-color="#D4613E" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    @foreach ($birthTicks as $tick)
                        @php
                            $tickY = $chartBottom - (($tick / $maxBirthValue) * ($chartBottom - $chartTop));
                        @endphp
                        <line class="dashboard-chart-grid" x1="{{ $chartLeft }}" x2="{{ $chartRight }}" y1="{{ $tickY }}" y2="{{ $tickY }}" />
                        <text x="62" y="{{ $tickY + 5 }}" class="dashboard-chart-label" text-anchor="end">{{ $tick }}</text>
                    @endforeach
                    @foreach ($offspringTicks as $tick)
                        @php
                            $tickY = $chartBottom - (($tick / $maxOffspringValue) * ($chartBottom - $chartTop));
                        @endphp
                        <text x="872" y="{{ $tickY + 5 }}" class="dashboard-chart-label" text-anchor="start">{{ $tick }}</text>
                    @endforeach
                    <line class="dashboard-chart-axis" x1="{{ $chartLeft }}" x2="{{ $chartRight }}" y1="{{ $chartBottom }}" y2="{{ $chartBottom }}" />
                    @foreach ($birthValues as $index => $value)
                        @php
                            $x = $chartLeft + ($chartStep * $index);
                            $height = ($value / $maxBirthValue) * ($chartBottom - $chartTop);
                        @endphp
                        <rect class="dashboard-chart-bar" x="{{ $x - 13 }}" y="{{ $chartBottom - $height }}" width="26" height="{{ $height }}" rx="5" />
                    @endforeach
                    <path class="dashboard-combo-area" d="{{ $offspringChartSvg['area'] }}" />
                    <path class="dashboard-combo-line" d="{{ $offspringChartSvg['line'] }}" />
                    <line class="dashboard-chart-active-line" x1="{{ $highlightPoint['x'] }}" x2="{{ $highlightPoint['x'] }}" y1="{{ $chartTop }}" y2="{{ $chartBottom }}" />
                    <circle class="dashboard-chart-active-dot" cx="{{ $highlightPoint['x'] }}" cy="{{ $highlightPoint['y'] }}" r="5" />
                    @foreach ($monthLabels as $i => $month)
                        @php
                            $x = $chartLeft + ($chartStep * $i);
                        @endphp
                        <text x="{{ $x }}" y="348" class="dashboard-chart-label" text-anchor="middle">{{ $month }}</text>
                        <g class="dashboard-chart-hitbox" data-dashboard-chart-point data-month="{{ $month }}" data-events="{{ $birthValues[$i] }}" data-offspring="{{ $offspringValues[$i] }}" data-index="{{ $i }}" tabindex="0" role="button" aria-label="{{ $month }}: {{ $birthValues[$i] }} peristiwa kelahiran dan {{ $offspringValues[$i] }} cempe lahir" @if ($i === $highlightIndex) data-dashboard-chart-default @endif>
                            <rect x="{{ $x - ($chartStep / 2) }}" y="{{ $chartTop }}" width="{{ $chartStep }}" height="{{ $chartBottom - $chartTop }}" />
                            <title>{{ $month }}: {{ $birthValues[$i] }} peristiwa kelahiran, {{ $offspringValues[$i] }} cempe lahir</title>
                        </g>
                    @endforeach
                </svg>
                <div class="dashboard-chart-legend" aria-label="Keterangan grafik">
                    <span><i class="dashboard-chart-legend-bar"></i>Peristiwa kelahiran</span>
                    <span><i class="dashboard-chart-legend-line"></i>Cempe lahir</span>
                </div>
            </div>
        </x-panel>

        <div class="grid gap-4 xl:col-span-4">
            <x-panel title="Tugas Prioritas">
                <div class="dashboard-scroll-list dashboard-scroll-list-3 space-y-3">
                    @forelse (($dashboard['priorityTasks'] ?? []) as $item)
                        <div class="rounded-lg px-2 py-2 transition hover:bg-[var(--app-surface-soft)]">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="dashboard-status-pill is-{{ $item['tone'] }}">{{ $item['status'] }}</span>
                                <time class="text-xs text-[var(--app-muted)]" datetime="{{ $item['date'] }}">{{ $displayDate($item['date']) }}</time>
                            </div>
                            <p class="mt-2 text-sm font-medium leading-snug">{{ $item['title'] }}</p>
                            <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">{{ $item['note'] }}</p>
                            @if (! empty($item['action_url']) && ! empty($item['action_label']))
                                <a class="mt-1 inline-flex text-xs font-medium text-[var(--app-active-text)]" href="{{ $item['action_url'] }}">
                                    {{ $item['action_label'] }}
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="px-2 py-2">
                            <p class="text-sm font-medium text-[var(--app-text)]">Tidak ada tugas prioritas</p>
                            <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">Belum ada pengingat berdasarkan catatan saat ini.</p>
                        </div>
                    @endforelse
                </div>
            </x-panel>

            <x-panel title="Agenda Operasional">
                <div class="dashboard-scroll-list dashboard-scroll-list-3 space-y-3">
                    @forelse (($dashboard['agenda'] ?? []) as $item)
                        <div class="rounded-lg px-2 py-2 transition hover:bg-[var(--app-surface-soft)]">
                            <p class="text-sm font-medium leading-snug">{{ $item['title'] }}</p>
                            <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">{{ $item['note'] }}</p>
                            <time class="mt-1 block text-xs text-[var(--app-muted)]" datetime="{{ $item['date'] }}">{{ $displayDate($item['date']) }}</time>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--app-muted)]">Belum ada perkiraan kelahiran atau jadwal kontrol yang tercatat.</p>
                    @endforelse
                </div>
            </x-panel>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <x-panel title="Kambing Terbaru" class="{{ $showActivities ? 'xl:col-span-8' : 'xl:col-span-12' }}">
            <div class="dashboard-latest-table-wrap" role="region" aria-label="Kambing terbaru" tabindex="0">
                <table class="ui-table dashboard-latest-table">
                    <colgroup>
                        <col class="w-[30%]">
                        <col class="w-[19%]">
                        <col class="w-[18%]">
                        <col class="w-[14%]">
                        <col class="w-[19%]">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Tag Kambing</th>
                            <th>Ras</th>
                            <th>Jenis Kelamin</th>
                            <th>Status</th>
                            <th>Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboard['latestAnimals'] as $row)
                            <tr>
                                <td>
                                    <span class="font-medium text-[var(--app-text)]">{{ $row[0] }}</span>
                                </td>
                                <td>{{ $row[1] }}</td>
                                <td>{{ $row[2] }}</td>
                                <td>
                                    <span class="admin-resource-card-status" data-tone="{{ $row[3] === 'Hidup' ? 'positive' : ($row[3] === 'Mati' ? 'negative' : 'neutral') }}">{{ $row[3] }}</span>
                                </td>
                                <td>{{ $displayDate($row[4]) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-[var(--app-muted)]">Belum ada data kambing yang dicatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        @if ($showActivities)
            <x-panel title="Aktivitas Terbaru" class="xl:col-span-4">
                <div class="dashboard-scroll-list dashboard-scroll-list-5 space-y-3">
                    @forelse ($dashboard['activities'] as $item)
                        <div class="rounded-lg px-2 py-2 transition hover:bg-[var(--app-surface-soft)]">
                            <p class="text-sm font-medium leading-snug">{{ $item['text'] }}</p>
                            <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">{{ $displayDateTime($item['time']) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--app-muted)]">Belum ada aktivitas yang dicatat.</p>
                    @endforelse
                </div>
            </x-panel>
        @endif
    </div>
    @endif
</x-layouts.admin>
