<x-layouts.admin title="Dashboard" skeleton="dashboard">
    @php
        $topStats = $dashboard['stats'] ?? [];
    @endphp

    @if (! empty($dashboard['apiFailureMessage']))
        <div class="admin-alert admin-alert-danger">
            <p class="font-semibold">Data Dashboard Tidak Lengkap</p>
            <p class="mt-1 theme-muted">{{ $dashboard['apiFailureMessage'] }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($topStats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" :tone="$stat['tone']" :trend="$stat['trend'] ?? []" :icon="$stat['icon'] ?? 'dashboard'" />
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <x-panel title="Overview" subtitle="Tren kelahiran kambing per bulan" class="xl:col-span-8">
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

            <div class="dashboard-chart-shell">
                @php
                    $birthValues = array_values(array_map('intval', $dashboard['birthChart'] ?? []));
                    $birthValues = $birthValues !== [] ? $birthValues : array_fill(0, 12, 0);
                    $maxBirthValue = max(1, max($birthValues));
                    $birthChartSvg = $dashboard['birthChartSvg'] ?? ['line' => '', 'area' => '', 'points' => []];
                    $monthLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    $chartTicks = array_values(array_unique([
                        $maxBirthValue,
                        (int) ceil($maxBirthValue * .75),
                        (int) ceil($maxBirthValue * .5),
                        (int) ceil($maxBirthValue * .25),
                        0,
                    ]));
                    $totalBirths = array_sum($birthValues);
                    $peakBirths = max($birthValues);
                    $peakMonthIndex = array_search($peakBirths, $birthValues, true);
                    $peakMonthLabel = $monthLabels[$peakMonthIndex === false ? 0 : $peakMonthIndex] ?? '-';
                    $averageBirths = round($totalBirths / max(1, count($birthValues)), 1);
                    $chartPoints = $birthChartSvg['points'] ?? [];
                    $lastPoint = $chartPoints === [] ? null : $chartPoints[array_key_last($chartPoints)];
                @endphp
                <div class="dashboard-chart-metrics" aria-label="Ringkasan grafik kelahiran">
                    <div>
                        <span>Total</span>
                        <strong>{{ $totalBirths }}</strong>
                    </div>
                    <div>
                        <span>Puncak</span>
                        <strong>{{ $peakBirths }} <small>{{ $peakMonthLabel }}</small></strong>
                    </div>
                    <div>
                        <span>Rata-rata</span>
                        <strong>{{ $averageBirths }}</strong>
                    </div>
                </div>
                <svg class="dashboard-trend-chart" viewBox="0 0 920 420" preserveAspectRatio="none" aria-label="Grafik kelahiran tahunan">
                    <defs>
                        <linearGradient id="birthTrendArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="var(--app-accent)" stop-opacity=".24" />
                            <stop offset="58%" stop-color="var(--app-accent)" stop-opacity=".075" />
                            <stop offset="100%" stop-color="var(--app-accent)" stop-opacity="0" />
                        </linearGradient>
                        <linearGradient id="birthTrendStroke" x1="0" x2="1" y1="0" y2="0">
                            <stop offset="0%" stop-color="var(--app-accent-strong)" />
                            <stop offset="100%" stop-color="var(--app-accent)" />
                        </linearGradient>
                    </defs>
                    @foreach ($chartTicks as $tick)
                        @php
                            $tickY = 334 - (($tick / $maxBirthValue) * 280);
                        @endphp
                        <line class="dashboard-chart-grid" x1="108" x2="884" y1="{{ $tickY }}" y2="{{ $tickY }}" />
                        <text x="34" y="{{ $tickY + 5 }}" fill="var(--app-muted)" font-size="14">{{ $tick }}</text>
                    @endforeach
                    <line class="dashboard-chart-axis" x1="108" x2="884" y1="334" y2="334" />
                    <path class="dashboard-trend-area" d="{{ $birthChartSvg['area'] }}" />
                    <path class="dashboard-trend-glow" d="{{ $birthChartSvg['line'] }}" />
                    <path class="dashboard-trend-line" d="{{ $birthChartSvg['line'] }}" />
                    @if ($lastPoint)
                        <line class="dashboard-chart-current-line" x1="{{ $lastPoint['x'] }}" x2="{{ $lastPoint['x'] }}" y1="{{ $lastPoint['y'] }}" y2="334" />
                        <circle class="dashboard-trend-current-dot" cx="{{ $lastPoint['x'] }}" cy="{{ $lastPoint['y'] }}" r="6" />
                    @endif
                    @foreach ($monthLabels as $i => $month)
                        <text x="{{ 108 + ($i * 70.55) }}" y="388" fill="var(--app-muted)" font-size="14" text-anchor="middle">{{ $month }}</text>
                    @endforeach
                </svg>
            </div>
        </x-panel>

        <div class="grid gap-4 xl:col-span-4">
            <x-panel title="Tugas Prioritas">
                <div class="dashboard-scroll-list dashboard-scroll-list-3 space-y-3">
                    @forelse (($dashboard['priorityTasks'] ?? []) as $item)
                        <div class="rounded-lg px-2 py-2 transition hover:bg-[var(--app-surface-soft)]">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-medium leading-snug">{{ $item['title'] }}</p>
                                <span class="shrink-0 text-xs text-[var(--app-muted)]/70">{{ $item['date'] }}</span>
                            </div>
                            <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">{{ $item['note'] }}</p>
                            @if (! empty($item['action_url']) && ! empty($item['action_label']))
                                <a class="mt-1 inline-flex text-xs font-medium text-[var(--app-active-text)]" href="{{ $item['action_url'] }}">
                                    {{ $item['action_label'] }}
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-3 py-3">
                            <p class="text-sm font-medium text-[var(--app-text)]">Tidak ada tugas prioritas</p>
                            <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">Semua pekerjaan yang perlu tindakan segera sudah terselesaikan.</p>
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
                            <p class="mt-1 text-xs text-[var(--app-muted)]/70">{{ $item['date'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--app-muted)]">Belum ada agenda perkiraan lahir atau kontrol kesehatan.</p>
                    @endforelse
                </div>
            </x-panel>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <x-panel title="Data Terbaru" class="xl:col-span-8">
            <div class="dashboard-latest-table-wrap">
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
                            <th>Terakhir Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dashboard['latestAnimals'] as $row)
                            <tr>
                                <td>
                                    <span class="font-medium text-[var(--app-text)]">{{ $row[0] }}</span>
                                </td>
                                <td>{{ $row[1] }}</td>
                                <td>{{ $row[2] }}</td>
                                <td>
                                    <span class="ui-badge" style="background: color-mix(in oklab, var(--app-success) 14%, transparent); color: var(--app-success);">{{ $row[3] }}</span>
                                </td>
                                <td>{{ $row[4] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>

        <x-panel title="Aktivitas Terbaru" class="xl:col-span-4">
            <div class="dashboard-scroll-list dashboard-scroll-list-5 space-y-3">
                @foreach ($dashboard['activities'] as $item)
                    <div class="rounded-lg px-2 py-2 transition hover:bg-[var(--app-surface-soft)]">
                        <p class="text-sm font-medium leading-snug">{{ $item['text'] }}</p>
                        <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">{{ $item['time'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-panel>
    </div>
</x-layouts.admin>
