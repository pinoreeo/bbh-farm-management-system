<x-layouts.admin :title="data_get($breedingPeriod, 'period.period_code', 'Periode Kawin')" skeleton="cards" :page-header="false">
    @php
        $period = $breedingPeriod['period'] ?? [];
        $summary = $breedingPeriod['summary'] ?? ['total' => 0, 'pregnant' => 0, 'not_pregnant' => 0, 'unchecked' => 0, 'born' => 0];
        $females = $breedingPeriod['females'] ?? [];
        $periodStatus = match (data_get($period, 'status')) {
            'active' => 'Aktif',
            'closed' => 'Ditutup',
            default => data_get($period, 'status', '-'),
        };
        $isActivePeriod = $periodStatus === 'Aktif';
        $periodCode = data_get($period, 'period_code', '-');
    @endphp

    <x-admin.record-page-header
        collection="Periode Kawin"
        :collection-route="route('admin.breeding-periods')"
        :title="$periodCode"
        subtitle="Rincian periode perkawinan dan betina yang terdaftar."
        :record="$periodCode"
        :edit-route="route('admin.resource.edit', ['resource' => 'breeding-periods', 'id' => $id])"
    />

    <div class="admin-record-card-grid grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-panel title="Periode & Kandang" subtitle="Tanggal, kandang, dan status periode." title-icon="calendar" class="admin-record-detail-card">
            <dl class="admin-record-detail-list">
                <div class="admin-record-detail-row">
                    <div>
                        <dt>Kode Periode</dt>
                        <dd>{{ $periodCode }}</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>{{ $periodStatus }}</dd>
                    </div>
                </div>
                <div class="admin-record-detail-row">
                    <div>
                        <dt>Kandang</dt>
                        <dd>{{ data_get($period, 'colony_pen.pen_code', '-') }}</dd>
                    </div>
                    <div>
                        <dt>Periode</dt>
                        <dd>{{ substr((string) data_get($period, 'start_date', '-'), 0, 10) }} s/d {{ substr((string) data_get($period, 'end_date', '-'), 0, 10) }}</dd>
                    </div>
                </div>
            </dl>
        </x-panel>

        <x-panel title="Pejantan" subtitle="Pejantan yang tercatat untuk periode ini." title-icon="goat" class="admin-record-detail-card">
            <div class="admin-period-stud">
                <span class="admin-period-stud-icon"><x-icons name="goat" class="h-5 w-5" /></span>
                <div>
                    <p>Tag Pejantan</p>
                    <strong>{{ data_get($period, 'male_animal.tag_number', '-') }}</strong>
                </div>
            </div>
        </x-panel>

        <x-panel title="Ringkasan Betina" subtitle="Jumlah betina berdasarkan catatan statusnya." title-icon="female" class="admin-record-detail-card">
            <div class="admin-period-summary">
                @foreach ([['Terdaftar', $summary['total']], ['Bunting', $summary['pregnant']], ['Belum Dicek', $summary['unchecked']], ['Tidak Bunting', $summary['not_pregnant']]] as [$label, $value])
                    <div>
                        <span>{{ $label }}</span>
                        <strong>{{ $value }}</strong>
                    </div>
                @endforeach
            </div>
        </x-panel>
    </div>

    <section class="admin-period-female-section">
        <header class="admin-period-female-section-header">
            <div>
                <h2>Betina Dalam Periode</h2>
                <p>Betina yang tercatat pada periode {{ $periodCode }}.</p>
            </div>
            @if ($isActivePeriod)
                <a class="ui-btn ui-btn-primary" href="{{ route('admin.resource.create', ['resource' => 'breeding-females', 'period_id' => $id]) }}">
                    <x-icons name="plus" class="h-4 w-4" />
                    Masukkan Betina
                </a>
            @endif
        </header>

        <div class="admin-period-female-grid">
            @forelse ($females as $female)
                @php
                    $pregnancyStatus = $female['pregnancy_status'] ?? 'Belum Dicek';
                    $statusTone = $pregnancyStatus === 'Bunting' || $pregnancyStatus === 'Lahir'
                        ? 'positive'
                        : ($pregnancyStatus === 'Tidak Bunting' ? 'negative' : 'neutral');
                    $isExited = ($female['exit_date'] ?? '-') !== '-';
                @endphp
                <article class="admin-period-female-card">
                    <header>
                        <div>
                            <p>Tag Betina</p>
                            <h3>{{ $female['tag'] }}</h3>
                        </div>
                        <span class="admin-resource-card-status" data-tone="{{ $statusTone }}">{{ $pregnancyStatus }}</span>
                    </header>
                    <dl>
                        @foreach ([['Masuk Periode', $female['entry_date']], ['Tanggal Kawin', $female['mating_date']], ['Perkiraan Lahir', $female['expected_birth_date']], ['Pemeriksaan Terakhir', $female['last_check_date']]] as [$label, $value])
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if (! $isExited && $isActivePeriod)
                        <footer>
                            <a class="ui-btn ui-btn-soft" href="{{ route('admin.breeding-females.mating', ['id' => $female['id']]) }}">
                                <x-icons name="heart" class="h-4 w-4" />
                                Catat Kawin
                            </a>
                            <a class="ui-btn ui-btn-soft" href="{{ $female['check_id'] ? route('admin.resource.edit', ['resource' => 'pregnancy-checks', 'id' => $female['check_id']]) : route('admin.resource.create', ['resource' => 'pregnancy-checks', 'period_id' => $id, 'female_animal_id' => $female['female_animal_id']]) }}">
                                <x-icons name="stethoscope" class="h-4 w-4" />
                                Periksa
                            </a>
                            <a class="ui-btn ui-btn-soft" href="{{ route('admin.breeding-females.exit', ['id' => $female['id']]) }}">
                                <x-icons name="logout" class="h-4 w-4" />
                                Keluarkan
                            </a>
                        </footer>
                    @endif
                </article>
            @empty
                <div class="admin-resource-card-empty">Belum ada betina yang dimasukkan ke periode ini.</div>
            @endforelse
        </div>
    </section>

    @if (count($history) > 0)
        <div class="mt-5">
            <x-admin.record-history :history="$history" collection="Periode Kawin" />
        </div>
    @endif
</x-layouts.admin>
