<x-layouts.admin title="Pengaturan" skeleton="form">
    <div class="admin-settings-layout grid gap-8 xl:grid-cols-[180px_minmax(0,560px)] xl:gap-10" data-settings-tabs>
        <nav class="admin-settings-nav flex h-fit gap-1 overflow-x-auto border-b pb-3 xl:block xl:border-b-0 xl:border-r xl:pb-0 xl:pr-6" aria-label="Navigasi pengaturan" role="tablist">
            <button class="admin-settings-tab is-active block shrink-0 rounded-md px-3 py-2 text-left text-sm" type="button" data-settings-tab="profile" role="tab" aria-controls="profil-pengguna" aria-selected="true">Profil pengguna</button>
            <button class="admin-settings-tab block shrink-0 rounded-md px-3 py-2 text-left text-sm" type="button" data-settings-tab="password" role="tab" aria-controls="keamanan-akun" aria-selected="false">Password</button>
        </nav>

        <div class="min-w-0">
            <section id="profil-pengguna" class="scroll-mt-24" data-settings-panel="profile" role="tabpanel">
                <div class="mb-6">
                    <h2 class="admin-section-title">Profil pengguna</h2>
                    <p class="mt-1 text-sm text-[var(--app-muted)]">Kelola nama dan nomor telepon akun Anda.</p>
                </div>

                <form class="space-y-5" method="post" action="{{ route('admin.profile.user') }}" data-skeleton-target="form">
                    @csrf
                    @method('put')

                    @if ($userProfileMessage)
                        <div class="admin-alert admin-alert-success">
                            <p class="font-semibold">Sukses</p>
                            <p class="mt-1">{{ $userProfileMessage }}</p>
                        </div>
                    @endif

                    @if ($errors->has('user_name'))
                        <div class="admin-alert admin-alert-danger">
                            <p class="font-semibold">Gagal</p>
                            <p class="mt-1">{{ $errors->first('user_name') }}</p>
                        </div>
                    @endif

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="ui-label">Nama lengkap</span>
                            <input class="ui-input" name="user_name" value="{{ old('user_name', $user['name'] ?? '') }}" required>
                            @error('user_name')
                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="ui-label">Nomor telepon</span>
                            <input class="ui-input" type="tel" name="phone" value="{{ old('phone', $user['phone'] ?? '') }}" placeholder="Contoh: 081234567890">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="ui-label">Email</span>
                        <input class="ui-input bg-[var(--app-surface-soft)]" type="email" value="{{ $user['email'] ?? '' }}" readonly>
                        <p class="mt-1 text-sm text-[var(--app-muted)]">Email digunakan untuk login dan tidak dapat diubah.</p>
                    </label>

                    <label class="block">
                        <span class="ui-label">Role</span>
                        <input class="ui-input bg-[var(--app-surface-soft)]" value="{{ \Illuminate\Support\Str::headline($user['role'] ?? 'admin') }}" readonly>
                    </label>

                    <div class="flex justify-end pt-1">
                        <button class="ui-btn ui-btn-primary" type="submit">
                            <x-icons name="save" class="h-4 w-4" />
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </section>

            <section id="keamanan-akun" class="scroll-mt-24" data-settings-panel="password" role="tabpanel" hidden>
                <div class="mb-6">
                    <h2 class="admin-section-title">Password</h2>
                    <p class="mt-1 text-sm text-[var(--app-muted)]">Perbarui password akun Anda.</p>
                </div>

                <form class="space-y-5" method="post" action="{{ route('admin.profile.password') }}" data-skeleton-target="form">
                    @csrf
                    @method('put')

                    @if ($passwordMessage)
                        <div class="admin-alert admin-alert-success">
                            <p class="font-semibold">Sukses</p>
                            <p class="mt-1">{{ $passwordMessage }}</p>
                        </div>
                    @endif

                    <label class="block">
                        <span class="ui-label">Password saat ini</span>
                        <input class="ui-input" type="password" name="current_password" required>
                    </label>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="ui-label">Password baru</span>
                            <input class="ui-input" type="password" name="password" required>
                        </label>

                        <label class="block">
                            <span class="ui-label">Konfirmasi password baru</span>
                            <input class="ui-input" type="password" name="password_confirmation" required>
                        </label>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button class="ui-btn ui-btn-primary" type="submit">
                            <x-icons name="save" class="h-4 w-4" />
                            Simpan password
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
