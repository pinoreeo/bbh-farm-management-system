<x-layouts.admin :title="$pageTitle" skeleton="form" :page-header="false">
    <x-admin.record-page-header
        collection="Manajemen Pengguna"
        :collection-route="route('admin.users')"
        :title="'Edit ' . $recordTitle"
        subtitle="Perbarui nama, nomor telepon, atau status akun pengguna."
        :record="$recordTitle"
        mode="Edit"
    />

    <section class="admin-form-shell admin-user-form-shell space-y-6">
        <form class="admin-form" method="post" action="{{ route('admin.resource.update', ['resource' => 'users', 'id' => $id]) }}" data-skeleton-target="form">
            @csrf
            @method('put')
            <x-panel title="Informasi Pengguna" subtitle="Nama, kontak, dan status akun pengguna.">
                <div class="grid gap-5">
                    @if ($errors->any())
                        <div class="admin-alert admin-alert-danger">
                            <p class="font-semibold">Gagal</p>
                            <p class="mt-1">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="ui-label">Nama lengkap</span>
                        <input class="ui-input" name="name" value="{{ old('name', $user['name'] ?? '') }}" required autofocus>
                    </label>

                    <label class="block">
                        <span class="ui-label">Nomor telepon</span>
                        <input class="ui-input" type="tel" name="phone" value="{{ old('phone', $user['phone'] ?? '') }}" placeholder="Contoh: 081234567890">
                    </label>

                    <label class="block md:col-span-2">
                        <span class="ui-label">Email</span>
                        <input class="ui-input bg-[var(--app-surface-soft)]" type="email" value="{{ $user['email'] ?? '' }}" readonly>
                        <p class="mt-1 text-sm text-[var(--app-muted)]">Email digunakan untuk login dan tidak dapat diubah.</p>
                    </label>
                    </div>

                    <label class="flex items-center gap-3 rounded-md border px-4 py-3" style="border-color: var(--app-border);">
                        <input type="hidden" name="is_active" value="0">
                        <input class="h-4 w-4" type="checkbox" name="is_active" value="1" @checked(old('is_active', $user['is_active'] ?? false))>
                        <span class="text-sm font-medium text-[var(--app-text)]">Akun aktif</span>
                    </label>
                </div>
            </x-panel>
            <div class="admin-form-actions">
                <button class="ui-btn ui-btn-primary" type="submit">
                    <x-icons name="save" class="h-4 w-4" />
                    Simpan perubahan
                </button>
                <a class="ui-btn ui-btn-soft" href="{{ route('admin.resource.show', ['resource' => 'users', 'id' => $id]) }}">
                    <x-icons name="x" class="h-4 w-4" />
                    Batal
                </a>
            </div>
        </form>

        <x-panel title="Reset Password" subtitle="Kirim tautan reset password ke email pengguna.">
            @if ($user['is_active'] ?? false)
                <form method="post" action="{{ route('admin.users.password-reset-link', ['id' => $id]) }}" data-skeleton-target="form">
                    @csrf
                    <button class="ui-btn ui-btn-soft" type="submit">Kirim tautan reset password</button>
                </form>
            @else
                <p class="text-sm text-[var(--app-muted)]">Aktifkan akun dan simpan perubahan sebelum mengirim tautan reset password.</p>
            @endif
        </x-panel>
    </section>
</x-layouts.admin>
