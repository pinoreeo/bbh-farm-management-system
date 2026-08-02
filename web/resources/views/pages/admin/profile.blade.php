<x-layouts.admin title="Profil Farm" skeleton="form">
    <div class="grid gap-4 xl:grid-cols-2">
        <x-panel title="Profil Farm">
            <form class="space-y-4" method="post" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('put')

                @if ($profileMessage)
                    <div class="admin-alert admin-alert-success">
                        <p class="font-semibold">Sukses</p>
                        <p class="mt-1">{{ $profileMessage }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="admin-alert admin-alert-danger">
                        <p class="font-semibold">Gagal</p>
                        <p class="mt-1 theme-muted">Periksa kembali informasi profil peternakan sebelum menyimpan.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <label class="block">
                    <span class="ui-label">Nama Farm</span>
                    <input class="ui-input" name="farm_name" value="{{ old('farm_name', $farm['farm_name'] ?? 'BBH Farm') }}" required>
                </label>

                <label class="block">
                    <span class="ui-label">Alamat</span>
                    <textarea class="ui-input min-h-28 py-3" name="address">{{ old('address', $farm['address'] ?? '') }}</textarea>
                </label>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="ui-label">Telepon</span>
                        <input class="ui-input" name="phone" value="{{ old('phone', $farm['phone'] ?? '') }}">
                    </label>

                    <label class="block">
                        <span class="ui-label">Email</span>
                        <input class="ui-input" type="email" name="email" value="{{ old('email', $farm['email'] ?? '') }}">
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: var(--app-border);">
                    <a class="ui-btn ui-btn-soft" href="{{ route('admin.dashboard') }}">
                        <x-icons name="arrow-left" class="h-4 w-4" />
                        Kembali
                    </a>
                    <button class="ui-btn ui-btn-primary" type="submit">
                        <x-icons name="save" class="h-4 w-4" />
                        Simpan Profil
                    </button>
                </div>
            </form>
        </x-panel>

        <x-panel title="Ubah Password">
            <form class="space-y-4" method="post" action="{{ route('admin.profile.password') }}">
                @csrf
                @method('put')

                @if ($passwordMessage)
                    <div class="admin-alert admin-alert-success">
                        <p class="font-semibold">Sukses</p>
                        <p class="mt-1">{{ $passwordMessage }}</p>
                    </div>
                @endif

                <label class="block">
                    <span class="ui-label">Password Saat Ini</span>
                    <input class="ui-input" type="password" name="current_password" required>
                </label>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="ui-label">Password Baru</span>
                        <input class="ui-input" type="password" name="password" required>
                    </label>

                    <label class="block">
                        <span class="ui-label">Konfirmasi Password Baru</span>
                        <input class="ui-input" type="password" name="password_confirmation" required>
                    </label>
                </div>

                <div class="flex justify-end border-t pt-5" style="border-color: var(--app-border);">
                    <button class="ui-btn ui-btn-primary" type="submit">
                        <x-icons name="save" class="h-4 w-4" />
                        Simpan Password
                    </button>
                </div>
            </form>
        </x-panel>
    </div>
</x-layouts.admin>
