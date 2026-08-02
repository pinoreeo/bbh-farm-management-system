<x-layouts.admin title="Keluarkan Betina" skeleton="form">
    @php
        $record = $context['breeding_female'] ?? [];
        $period = data_get($record, 'breeding_period', []);
        $female = data_get($record, 'female_animal', []);
        $penOptions = $context['pen_options'] ?? [];
        $reasonOptions = $context['reason_options'] ?? [];
        $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') : '-';
    @endphp

    <div class="admin-form-shell">
        <x-panel title="Keluarkan Betina dari Periode">
            @if ($errors->any())
                <div class="admin-alert admin-alert-danger">
                    <p class="font-semibold">Gagal</p>
                    <p class="mt-1 theme-muted">Periksa tanggal keluar, alasan, dan koloni tujuan sebelum memproses data.</p>
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
                    <span>Koloni Periode</span>
                    <strong>{{ data_get($period, 'colony_pen.pen_code', '-') }}</strong>
                </div>
                <div class="admin-readonly-field">
                    <span>Tag Pejantan</span>
                    <strong>{{ data_get($period, 'male_animal.tag_number', '-') }}</strong>
                </div>
                <div class="admin-readonly-field">
                    <span>Tanggal Masuk</span>
                    <strong>{{ $date(data_get($record, 'entry_date')) }}</strong>
                </div>
                <div class="admin-readonly-field">
                    <span>Tanggal Kawin</span>
                    <strong>{{ $date(data_get($record, 'mating_date')) }}</strong>
                </div>
                <div class="admin-readonly-field md:col-span-2">
                    <span>Perkiraan Lahir</span>
                    <strong>{{ $date(data_get($record, 'expected_birth_date')) }}</strong>
                </div>
            </div>

            <form class="mt-6 grid gap-5" method="post" action="{{ route('admin.breeding-females.exit.store', ['id' => $id]) }}">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="ui-label">Tanggal Keluar</span>
                        <input class="ui-input" type="date" name="exit_date" value="{{ old('exit_date', now()->toDateString()) }}" required @disabled(data_get($record, 'exit_date'))>
                    </label>

                    <label>
                        <span class="ui-label">Alasan Keluar</span>
                        <select class="ui-input" name="exit_reason_code" required @disabled(data_get($record, 'exit_date'))>
                            <option value="">Pilih alasan</option>
                            @foreach ($reasonOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('exit_reason_code') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="ui-label">Detail Alasan Lainnya</span>
                        <input class="ui-input" type="text" name="exit_reason" value="{{ old('exit_reason') }}" placeholder="Wajib diisi jika memilih Lainnya" @disabled(data_get($record, 'exit_date'))>
                    </label>

                    <label>
                        <span class="ui-label">Koloni Tujuan</span>
                        <select class="ui-input" name="to_pen_id" @disabled(data_get($record, 'exit_date'))>
                            <option value="">Tidak pindah koloni sekarang</option>
                            @foreach ($penOptions as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('to_pen_id') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="md:col-span-2">
                        <span class="ui-label">Catatan</span>
                        <textarea class="ui-input min-h-28 py-3" name="exit_notes" placeholder="Tambahkan catatan jika ada kejadian khusus, misalnya pemeriksaan bunting, pejantan sakit, atau pemindahan darurat." @disabled(data_get($record, 'exit_date'))>{{ old('exit_notes') }}</textarea>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: var(--app-border);">
                    <a class="ui-btn ui-btn-soft" href="{{ route('admin.breeding-females') }}">
                        <x-icons name="x" class="h-4 w-4" />
                        Batal
                    </a>
                    @unless (data_get($record, 'exit_date'))
                        <button class="ui-btn ui-btn-primary" type="submit">
                            <x-icons name="logout" class="h-4 w-4" />
                            Keluarkan
                        </button>
                    @endunless
                </div>
            </form>
        </x-panel>
    </div>
</x-layouts.admin>
