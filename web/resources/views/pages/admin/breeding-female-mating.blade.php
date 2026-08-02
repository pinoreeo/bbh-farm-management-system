<x-layouts.admin title="Catat Kawin" skeleton="form">
    @php
        $record = $context['breeding_female'] ?? [];
        $period = data_get($record, 'breeding_period', []);
        $female = data_get($record, 'female_animal', []);
        $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') : '-';
        $inputDate = old('mating_date', data_get($record, 'mating_date') ? substr((string) data_get($record, 'mating_date'), 0, 10) : now()->toDateString());
    @endphp

    <div class="admin-form-shell">
        <x-panel title="Catat Tanggal Kawin">
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

            <form class="mt-6 grid gap-5" method="post" action="{{ route('admin.breeding-females.mating.store', ['id' => $id]) }}">
                @csrf

                <label>
                    <span class="ui-label">Tanggal Kawin</span>
                    <input class="ui-input" type="date" name="mating_date" value="{{ $inputDate }}" required @disabled(data_get($record, 'exit_date'))>
                </label>

                <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: var(--app-border);">
                    <a class="ui-btn ui-btn-soft" href="{{ route('admin.breeding-females') }}">
                        <x-icons name="x" class="h-4 w-4" />
                        Batal
                    </a>
                    @unless (data_get($record, 'exit_date'))
                        <button class="ui-btn ui-btn-primary" type="submit">
                            <x-icons name="calendar" class="h-4 w-4" />
                            Simpan Tanggal Kawin
                        </button>
                    @endunless
                </div>
            </form>
        </x-panel>
    </div>
</x-layouts.admin>
