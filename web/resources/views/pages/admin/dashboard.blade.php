<x-layouts.admin title="Dashboard" skeleton="dashboard">
    @php($topStats = $dashboard['stats'] ?? [])

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($topStats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" :tone="$stat['tone']" />
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <x-panel title="Grafik Kelahiran Tahunan" class="xl:col-span-8">
            <x-slot:actions>
                <form class="w-28" method="get">
                    <label>
                        <span class="sr-only">Filter tahun kelahiran</span>
                        <select class="ui-input" name="birth_year" onchange="this.form.submit()">
                        @foreach ($dashboard['birthYears'] as $year)
                            <option value="{{ $year }}" @selected((int) ($dashboard['selectedBirthYear'] ?? $year) === (int) $year)>{{ $year }}</option>
                        @endforeach
                        </select>
                    </label>
                </form>
            </x-slot:actions>

            <div class="dashboard-chart-shell">
                <svg class="dashboard-line-chart" viewBox="0 0 760 360" preserveAspectRatio="none" aria-label="Grafik kelahiran tahunan">
                    <defs>
                        <linearGradient id="birthArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="currentColor" stop-opacity=".2" />
                            <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    @foreach ([55, 115, 175, 235, 295] as $y)
                        <line x1="58" x2="735" y1="{{ $y }}" y2="{{ $y }}" stroke="var(--app-border)" stroke-dasharray="4 6" />
                    @endforeach
                    <path d="M58 260 C105 238 125 275 172 238 C218 202 245 240 290 205 C335 170 365 172 410 145 C455 118 480 152 525 112 C570 72 615 102 658 72 C692 48 715 38 735 30 L735 330 L58 330 Z" fill="url(#birthArea)" />
                    <path d="M58 260 C105 238 125 275 172 238 C218 202 245 240 290 205 C335 170 365 172 410 145 C455 118 480 152 525 112 C570 72 615 102 658 72 C692 48 715 38 735 30" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    @foreach (['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] as $i => $month)
                        <text x="{{ 58 + ($i * 61) }}" y="350" fill="var(--app-muted)" font-size="12" text-anchor="middle">{{ $month }}</text>
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
                                <span class="shrink-0 text-[11px] text-[var(--app-muted)]/70">{{ $item['date'] }}</span>
                            </div>
                            <p class="mt-1 text-xs leading-snug text-[var(--app-muted)]">{{ $item['note'] }}</p>
                            @if (! empty($item['action_url']) && ! empty($item['action_label']))
                                <a class="mt-1 inline-flex text-xs font-semibold text-[var(--app-active-text)]" href="{{ $item['action_url'] }}">
                                    {{ $item['action_label'] }}
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] px-3 py-3">
                            <p class="text-sm font-semibold text-[var(--app-text)]">Tidak ada tugas prioritas</p>
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
                            <p class="mt-1 text-[11px] text-[var(--app-muted)]/70">{{ $item['date'] }}</p>
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

        <x-panel title="Recent Activity" class="xl:col-span-4">
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
