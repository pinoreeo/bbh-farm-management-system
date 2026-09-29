<x-layouts.admin title="Profil Peternakan" skeleton="form">
    <section class="admin-form-shell">
        <form class="admin-form" method="post" action="{{ route('admin.farm-profile.update') }}" data-skeleton-target="form">
            @csrf
            @method('put')

            <x-panel title="Informasi Peternakan" subtitle="Perbarui nama, alamat, dan nomor telepon peternakan." :padded="false" class="admin-form-panel">
                <div class="admin-form-panel-body grid gap-5">
                    @if ($farmProfileMessage)
                        <div class="admin-alert admin-alert-success">
                            <p class="font-semibold">Sukses</p>
                            <p class="mt-1">{{ $farmProfileMessage }}</p>
                        </div>
                    @endif

                    @if ($errors->hasAny(['farm_name', 'address', 'phone']))
                        <div class="admin-alert admin-alert-danger">
                            <p class="font-semibold">Gagal</p>
                            <p class="mt-1 theme-muted">Periksa kembali isian yang ditandai sebelum menyimpan.</p>
                        </div>
                    @endif

                    <label class="block">
                        <span class="ui-label">Nama Peternakan</span>
                        <input class="ui-input" name="farm_name" value="{{ old('farm_name', $farm['farm_name'] ?? 'BBH Farm') }}" required>
                        @error('farm_name')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="ui-label">Alamat Peternakan</span>
                        <textarea class="ui-input min-h-28 py-3" name="address">{{ old('address', $farm['address'] ?? '') }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="ui-label">Nomor Telepon Peternakan</span>
                        <input class="ui-input" type="tel" name="phone" value="{{ old('phone', $farm['phone'] ?? '') }}" placeholder="Contoh: 081234567890">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </label>
                </div>
            </x-panel>

            <div class="admin-form-actions">
                <button class="ui-btn ui-btn-primary" type="submit">
                    <x-icons name="save" class="h-4 w-4" />
                    Simpan Profil Peternakan
                </button>
            </div>
        </form>
    </section>
</x-layouts.admin>
