<x-layouts.admin title="Kebuntingan" skeleton="form">
    <div class="admin-form-shell">
        @if ($errors->any())
            <div class="admin-alert admin-alert-danger">
                <p class="font-semibold">Gagal</p>
                <p class="mt-1 theme-muted">Periksa kembali data pemeriksaan kebuntingan sebelum menyimpan.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-panel title="Form Pemeriksaan Bunting">
            <form class="grid gap-5" method="post" action="{{ ($mode ?? 'edit') === 'edit' ? route('admin.resource.update', ['resource' => 'pregnancy-checks', 'id' => $id]) : route('admin.resource.store', ['resource' => 'pregnancy-checks']) }}">
                @csrf
                @if (($mode ?? 'edit') === 'edit')
                    @method('put')
                @endif
                <input type="hidden" name="breeding_period_id" value="{{ data_get($values ?? [], 'breeding_period_id') }}">
                <input type="hidden" name="female_animal_id" value="{{ data_get($values ?? [], 'female_animal_id') }}">

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([['Kode Periode', data_get($values ?? [], 'breeding_period.period_code', 'BRD-2026-001')], ['Kandang', data_get($values ?? [], 'breeding_period.colony_pen.pen_code', 'KP-001')], ['Pejantan', data_get($values ?? [], 'breeding_period.male_animal.tag_number', '0001')], ['Tag Betina', data_get($values ?? [], 'female_animal.tag_number', '0002')]] as [$label, $value])
                        <div class="admin-field-summary">
                            <p class="text-xs font-medium uppercase tracking-wide theme-muted">{{ $label }}</p>
                            <p class="mt-2 text-sm font-semibold text-[var(--app-text)]">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <h2 class="text-base font-semibold text-[var(--app-text)]">Input Pemeriksaan</h2>

                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="ui-label">Tanggal Periksa</span>
                        <input class="ui-input" type="date" name="check_date" value="{{ old('check_date', substr((string) data_get($values ?? [], 'check_date', now()->toDateString()), 0, 10)) }}">
                    </label>

                    <label>
                        <span class="ui-label">Status Bunting</span>
                        <select class="ui-input" name="is_pregnant">
                            @php($pregnant = old('is_pregnant', data_get($values ?? [], 'is_pregnant')))
                            <option value="">Pilih Status</option>
                            <option value="1" @selected((string) $pregnant === '1')>Bunting</option>
                            <option value="0" @selected((string) $pregnant === '0')>Tidak Bunting</option>
                        </select>
                    </label>

                    <label>
                        <span class="ui-label">Metode Periksa</span>
                        <select class="ui-input" name="method">
                            <option value="">Pilih Metode</option>
                            @php($method = old('method', data_get($values ?? [], 'method')))
                            <option value="Palpasi" @selected($method === 'Palpasi')>Palpasi</option>
                            <option value="USG" @selected($method === 'USG')>USG</option>
                            <option value="Visual" @selected($method === 'Visual')>Visual</option>
                        </select>
                    </label>

                    <label>
                        <span class="ui-label">Estimasi Usia</span>
                        <div class="flex items-center gap-3">
                            <input class="ui-input" type="number" name="estimated_gestation_days" value="{{ old('estimated_gestation_days', data_get($values ?? [], 'estimated_gestation_days')) }}" placeholder="45">
                            <span class="text-sm font-medium theme-muted">hari</span>
                        </div>
                    </label>

                    <label class="md:col-span-2">
                        <span class="ui-label">Catatan</span>
                        <textarea class="ui-input min-h-28 py-3" name="notes" placeholder="Tambahkan catatan pemeriksaan">{{ old('notes', data_get($values ?? [], 'notes')) }}</textarea>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: var(--app-border);">
                    <a class="ui-btn ui-btn-soft" href="{{ route('admin.resource.show', ['resource' => 'pregnancy-checks', 'id' => data_get($values ?? [], 'breeding_period_id', 1)]) }}">
                        <x-icons name="arrow-left" class="h-4 w-4" />
                        Kembali
                    </a>
                    <button class="ui-btn ui-btn-primary" type="submit">
                        <x-icons name="save" class="h-4 w-4" />
                        Simpan
                    </button>
                </div>
            </form>
        </x-panel>
    </div>
</x-layouts.admin>
