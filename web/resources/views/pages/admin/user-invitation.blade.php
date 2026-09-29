<x-layouts.admin :title="$pageTitle" skeleton="form" :page-header="false">
    <x-admin.record-page-header
        collection="Manajemen Pengguna"
        :collection-route="route('admin.users')"
        :title="$pageTitle"
        subtitle="Kirim undangan email agar admin baru dapat mengaktifkan akunnya."
        mode="Tambah"
    />

    <section class="admin-form-shell admin-user-form-shell">
        <form class="admin-form" method="post" action="{{ route('admin.resource.store', ['resource' => 'users']) }}" data-skeleton-target="form">
            @csrf

            <x-panel title="Informasi Admin" subtitle="Nama dan kontak admin yang akan diundang." :padded="false" class="admin-form-panel">
                <div class="admin-form-panel-body grid gap-5">
                    @if ($errors->any())
                        <div class="admin-alert admin-alert-danger">
                            <p class="font-semibold">Gagal</p>
                            <p class="mt-1">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <label class="block">
                        <span class="ui-label">Nama lengkap</span>
                        <input class="ui-input" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" autocomplete="name" required autofocus>
                    </label>

                    <label class="block">
                        <span class="ui-label">Email</span>
                        <input class="ui-input" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required>
                    </label>

                    <label class="block">
                        <span class="ui-label">Nomor telepon</span>
                        <input class="ui-input" type="tel" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 081234567890" autocomplete="tel">
                    </label>
                </div>
            </x-panel>

            <div class="admin-form-actions">
                <button class="ui-btn ui-btn-primary" type="submit">Kirim undangan email</button>
                <a class="ui-btn ui-btn-soft" href="{{ route('admin.users') }}">
                    <x-icons name="x" class="h-4 w-4" />
                    Batal
                </a>
            </div>
        </form>
    </section>
</x-layouts.admin>
