<x-layouts.admin title="Kebuntingan" skeleton="detail">
    @php
        $period = $pregnancyPeriod['period'] ?? [];
        $summary = $pregnancyPeriod['summary'] ?? ['total' => 0, 'pregnant' => 0, 'not_pregnant' => 0, 'unchecked' => 0, 'born' => 0];
        $females = $pregnancyPeriod['females'] ?? [];
        $periodStatus = match (data_get($period, 'status')) {
            'active' => 'Aktif',
            'closed' => 'Ditutup',
            default => data_get($period, 'status', '-'),
        };
    @endphp

    <div class="space-y-5">
        <div class="admin-page-actions">
            <a class="ui-btn ui-btn-soft" href="{{ route('admin.pregnancy-checks') }}">
                <x-icons name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
        </div>

        <x-panel title="Pemeriksaan Bunting / {{ data_get($period, 'period_code', '-') }}">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Kode Periode', data_get($period, 'period_code', '-')],
                    ['Pejantan', data_get($period, 'male_animal.tag_number', '-')],
                    ['Kandang', data_get($period, 'colony_pen.pen_code', '-')],
                    ['Status Periode', $periodStatus],
                ] as [$label, $value])
                    <div class="admin-detail-item">
                        <p class="text-xs font-medium uppercase tracking-wide theme-muted">{{ $label }}</p>
                        <p class="mt-2 text-sm font-semibold text-[var(--app-text)]">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="admin-detail-item">
                    <p class="text-xs font-medium uppercase tracking-wide theme-muted">Tanggal Periode</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--app-text)]">
                        {{ substr((string) data_get($period, 'start_date', '-'), 0, 10) }} s/d {{ substr((string) data_get($period, 'end_date', '-'), 0, 10) }}
                    </p>
                </div>
                <div class="admin-detail-item">
                    <p class="text-xs font-medium uppercase tracking-wide theme-muted">Ringkasan Betina</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ([
                            'Total ' . $summary['total'],
                            'Bunting ' . $summary['pregnant'],
                            'Tidak Bunting ' . $summary['not_pregnant'],
                            'Belum Dicek ' . $summary['unchecked'],
                            'Lahir ' . ($summary['born'] ?? 0),
                        ] as $item)
                            <span class="ui-badge bg-[var(--app-surface-soft)] text-[var(--app-text)]">{{ $item }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-panel>

        <x-panel title="Daftar Betina Dalam Periode">
            <div class="overflow-x-auto">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Tag Betina</th>
                            <th>Tanggal Masuk</th>
                            <th>Tanggal Keluar</th>
                            <th>Tanggal Kawin</th>
                            <th>Perkiraan Lahir</th>
                            <th>Tanggal Periksa Terakhir</th>
                            <th>Status Bunting</th>
                            <th>Metode</th>
                            <th>Estimasi Usia</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($females as $female)
                            <tr>
                                <td>{{ $female['tag'] }}</td>
                                <td>{{ $female['entry_date'] }}</td>
                                <td>{{ $female['exit_date'] }}</td>
                                <td>{{ $female['mating_date'] }}</td>
                                <td>{{ $female['expected_birth_date'] }}</td>
                                <td>{{ $female['last_check_date'] }}</td>
                                <td><span class="ui-badge">{{ $female['pregnancy_status'] }}</span></td>
                                <td>{{ $female['method'] }}</td>
                                <td>{{ $female['estimated_gestation_days'] }}</td>
                                <td class="text-right">
                                    <a class="ui-btn ui-btn-soft h-9 w-9 px-0" href="{{ $female['check_id'] ? route('admin.resource.edit', ['resource' => 'pregnancy-checks', 'id' => $female['check_id']]) : route('admin.resource.create', ['resource' => 'pregnancy-checks', 'period_id' => $id, 'female_animal_id' => $female['female_animal_id']]) }}" aria-label="Input pemeriksaan">
                                        <x-icons name="edit" class="h-4 w-4" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center theme-muted">Belum ada betina dalam periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>
    </div>
</x-layouts.admin>
