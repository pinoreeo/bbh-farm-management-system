<x-layouts.admin title="Catat Kawin" skeleton="form" :page-header="false">
    @php
        $record = $context['breeding_female'] ?? [];
        $period = data_get($record, 'breeding_period', []);
        $female = data_get($record, 'female_animal', []);
        $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') : '-';
        $inputDate = old('mating_date', data_get($record, 'mating_date') ? substr((string) data_get($record, 'mating_date'), 0, 10) : now()->toDateString());
    @endphp

    <x-admin.record-page-header
        collection="Betina Kawin"
        :collection-route="route('admin.breeding-females')"
        title="Catat Kawin"
        subtitle="Catat tanggal perkawinan betina pada periode ini."
        :record="data_get($female, 'tag_number')"
        mode="Catat Kawin"
    />

    <div class="admin-form-shell">
        <form class="admin-form" method="post" action="{{ route('admin.breeding-females.mating.store', ['id' => $id]) }}" data-skeleton-target="table">
            @csrf
            <x-panel>
            @if ($errors->any())
                <div class="admin-alert admin-alert-danger">
                    <p class="font-semibold">Gagal</p>
                    <p class="mt-1 theme-muted">Periksa kembali tanggal kawin dan periode yang sedang berjalan.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (data_get($record, 'exit_date'))
                <div class="admin-alert admin-alert-danger">
                    <p class="font-semibold">Peringatan</p>
                    <p class="mt-1 theme-muted">Tanggal keluar: {{ $date(data_get($record, 'exit_date')) }}</p>
                </div>
            @endif

            <div class="grid gap-3 md:grid-cols-2">
                <div class="admin-readonly-field">
                    <span>Kode Periode</span>
                    <strong>{{ data_get($period, 'period_code', '-') }}</strong>
                </div>
                <div class="admin-readonly-field">
                    <span>Tag Betina</span>
                    <strong>{{ data_get($female, 'tag_number', '-') }}</strong>
                </div>
                <div class="admin-readonly-field">
                    <span>Tag Pejantan</span>
                    <strong>{{ data_get($period, 'male_animal.tag_number', '-') }}</strong>
                </div>
                <div class="admin-readonly-field">
                    <span>Perkiraan Lahir Saat Ini</span>
                    <strong>{{ $date(data_get($record, 'expected_birth_date')) }}</strong>
                </div>
            </div>

            <div class="mt-6 grid gap-5">
                <label>
                    <span class="ui-label">Tanggal Kawin</span>
                    <input class="ui-input" type="date" name="mating_date" value="{{ $inputDate }}" required @disabled(data_get($record, 'exit_date'))>
                </label>
            </div>
            </x-panel>
            <div class="admin-form-actions">
                @unless (data_get($record, 'exit_date'))
                    <button class="ui-btn ui-btn-primary" type="submit">
                        <x-icons name="calendar" class="h-4 w-4" />
                        Simpan Tanggal Kawin
                    </button>
                @endunless
                <a class="ui-btn ui-btn-soft" href="{{ route('admin.breeding-females') }}">
                    <x-icons name="x" class="h-4 w-4" />
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-layouts.admin>
