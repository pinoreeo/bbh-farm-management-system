<x-layouts.admin title="Kebuntingan" skeleton="detail" :page-header="false">
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

    <x-admin.record-page-header
        collection="Kebuntingan"
        :collection-route="route('admin.pregnancy-checks')"
        :title="data_get($period, 'period_code', '-')"
        :record="data_get($period, 'period_code', '-')"
        subtitle="Catatan pemeriksaan kebuntingan pada periode ini."
    />

    <div class="space-y-5">
        <div class="admin-record-card-grid grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-panel title="Periode Kawin" title-icon="calendar" class="admin-record-detail-card">
                <dl class="admin-record-detail-list">
                    @foreach ([
                        ['Kode Periode', data_get($period, 'period_code', '-')],
                        ['Status Periode', $periodStatus],
                        ['Tanggal Mulai', substr((string) (data_get($period, 'start_date') ?: '-'), 0, 10)],
                        ['Tanggal Selesai', substr((string) (data_get($period, 'end_date') ?: '-'), 0, 10)],
                    ] as [$label, $value])
                        <div class="admin-record-detail-row admin-record-detail-row-single">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </x-panel>

            <x-panel title="Kandang & Pejantan" title-icon="home" class="admin-record-detail-card">
                <dl class="admin-record-detail-list">
                    @foreach ([
                        ['Kandang', data_get($period, 'colony_pen.pen_code', '-')],
                        ['Tag Pejantan', data_get($period, 'male_animal.tag_number', '-')],
                    ] as [$label, $value])
                        <div class="admin-record-detail-row admin-record-detail-row-single">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </x-panel>

            <x-panel title="Ringkasan Betina" title-icon="female" class="admin-record-detail-card">
                <div class="admin-period-summary">
                    @foreach ([['Total Betina', $summary['total']], ['Bunting', $summary['pregnant']], ['Tidak Bunting', $summary['not_pregnant']], ['Belum Dicek', $summary['unchecked']], ['Lahir', $summary['born'] ?? 0]] as [$label, $value])
                        <div>
                            <span>{{ $label }}</span>
                            <strong>{{ $value }}</strong>
                        </div>
                    @endforeach
                </div>
            </x-panel>
        </div>

        <x-panel title="Daftar Betina Dalam Periode" title-icon="stethoscope" class="admin-data-panel">
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
                            <th class="text-right"><span class="sr-only">Aksi</span></th>
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
                                    <div class="flex flex-nowrap justify-end gap-2">
                                        <details class="admin-row-menu" data-row-menu>
                                            <summary aria-label="Buka aksi {{ $female['tag'] }}" title="Aksi">
                                                <x-icons name="more" class="h-5 w-5" />
                                            </summary>
                                            <div class="admin-row-menu-panel">
                                                <a href="{{ route('admin.resource.show', ['resource' => 'breeding-females', 'id' => $female['id']]) }}">
                                                    <x-icons name="eye" class="h-4 w-4" />
                                                    Lihat detail betina
                                                </a>
                                                <a href="{{ $female['check_id'] ? route('admin.resource.edit', ['resource' => 'pregnancy-checks', 'id' => $female['check_id']]) : route('admin.resource.create', ['resource' => 'pregnancy-checks', 'period_id' => $id, 'female_animal_id' => $female['female_animal_id']]) }}">
                                                    <x-icons :name="$female['check_id'] ? 'edit' : 'plus'" class="h-4 w-4" />
                                                    {{ $female['check_id'] ? 'Edit pemeriksaan' : 'Input pemeriksaan' }}
                                                </a>
                                            </div>
                                        </details>
                                    </div>
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
