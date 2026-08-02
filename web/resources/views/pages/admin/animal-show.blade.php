<x-layouts.admin title="Detail Kambing" skeleton="detail">
    <div class="admin-page-actions">
        <a class="ui-btn ui-btn-soft" href="{{ route('admin.animals') }}">
            <x-icons name="arrow-left" class="h-4 w-4" />
            Kembali
        </a>
        <div class="admin-inline-actions">
            <a class="ui-btn ui-btn-primary" href="{{ route('admin.resource.edit', ['resource' => 'animals', 'id' => $id]) }}">
                <x-icons name="edit" class="h-4 w-4" />
                Edit
            </a>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[320px_1fr]">
        <x-panel title="Identitas">
            <div class="overflow-hidden rounded-lg border" style="border-color: var(--app-border); background: var(--app-surface-soft);">
                @if (data_get($animal, 'photo_url'))
                    <img class="h-56 w-full object-cover" src="{{ data_get($animal, 'photo_url') }}" alt="Foto {{ data_get($animal, 'tag_number') }}">
                @else
                    <div class="grid h-56 place-items-center theme-muted">Belum ada foto</div>
                @endif
            </div>

            <dl class="mt-4 space-y-3 text-sm">
                @foreach ([
                    'Eartag' => data_get($animal, 'tag_number'),
                    'Ras' => data_get($animal, 'breed.breed_name'),
                    'Generasi' => data_get($animal, 'generation'),
                    'Jenis Kelamin' => data_get($animal, 'sex') === 'male' ? 'Jantan' : 'Betina',
                    'Koloni' => data_get($animal, 'current_pen.pen_code', '-'),
                    'Status Reproduksi' => str(data_get($animal, 'reproductive_status', '-'))->replace('_', ' ')->title(),
                    'Status Ternak' => match (data_get($animal, 'exit_status')) {
                        'sold' => 'Dijual',
                        'culled' => 'Afkir / Tidak Produktif',
                        'lost' => 'Hilang',
                        default => '-',
                    },
                    'Status Hidup' => data_get($animal, 'life_status') === 'dead' ? 'Mati' : 'Hidup',
                    'Umur' => data_get($animal, 'umur'),
                ] as $label => $value)
                    <div class="flex justify-between gap-4 border-b pb-2" style="border-color: var(--app-border);">
                        <dt class="theme-muted">{{ $label }}</dt>
                        <dd class="text-right font-semibold" style="color: var(--app-text);">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-panel>

        <div class="space-y-4">
            @php
                $sections = [
                    'Riwayat Kesehatan' => [data_get($animal, 'health_treatments', []), ['treatment_date' => 'Tanggal', 'treatment_group' => 'Jenis', 'diagnosis' => 'Diagnosis', 'product_name' => 'Obat', 'next_control_date' => 'Kontrol']],
                    'Catatan Bobot' => [data_get($animal, 'weight_records', []), ['record_date' => 'Tanggal', 'weight_kg' => 'Berat Kg', 'notes' => 'Catatan']],
                    'Riwayat Pindah Koloni' => [data_get($animal, 'pen_movements', []), ['movement_date' => 'Tanggal', 'from_pen.pen_code' => 'Dari', 'to_pen.pen_code' => 'Ke', 'reason' => 'Alasan']],
                    'Riwayat Kawin' => [data_get($animal, 'breeding_females', []), ['breeding_period.period_code' => 'Periode', 'mating_date' => 'Tanggal Kawin', 'expected_birth_date' => 'Perkiraan Lahir']],
                    'Riwayat Kebuntingan' => [data_get($animal, 'pregnancy_checks', []), ['check_date' => 'Tanggal', 'is_pregnant' => 'Status', 'method' => 'Metode', 'notes' => 'Catatan']],
                ];
            @endphp

            @foreach ($sections as $title => [$items, $columns])
                <x-panel :title="$title">
                    <div class="overflow-x-auto">
                        <table class="ui-table">
                            <thead>
                                <tr>
                                    @foreach ($columns as $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        @foreach ($columns as $key => $label)
                                            @php($value = data_get($item, $key, '-'))
                                            <td>
                                                @if ($key === 'is_pregnant')
                                                    {{ $value ? 'Bunting' : 'Tidak Bunting' }}
                                                @else
                                                    {{ is_string($value) ? substr($value, 0, 30) : ($value ?? '-') }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($columns) }}" class="text-center theme-muted">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-panel>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
