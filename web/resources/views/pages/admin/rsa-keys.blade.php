<x-layouts.admin title="RSA Key" skeleton="table">
    @php($rows = collect($records ?? []))
    @php($hasRecords = $rows->isNotEmpty())
    @php($isSuperAdmin = (session('bbh_admin_user.role') ?? null) === 'super_admin')
    @php($shortFingerprint = fn ($value) => strlen((string) $value) > 14 ? substr((string) $value, 0, 5) . '....' . substr((string) $value, -5) : (string) $value)

    @if (session('formMessage') || $errors->any())
        <div class="admin-alert {{ $errors->any() ? 'admin-alert-danger' : 'admin-alert-success' }}">
            <p class="font-semibold">{{ $errors->any() ? 'Gagal' : 'Sukses' }}</p>
            @if ($errors->any())
                <p class="mt-1 theme-muted">Periksa kembali konfigurasi kunci digital sebelum melanjutkan.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @else
                <p class="mt-1">{{ session('formMessage') }}</p>
            @endif
        </div>
    @endif

    @if (! empty($apiFailureMessage))
        <div class="admin-alert admin-alert-danger">
            <p class="font-semibold">Data Tidak Dapat Dimuat</p>
            <p class="mt-1 theme-muted">{{ $apiFailureMessage }}</p>
        </div>
    @endif

    <x-panel title="Manajemen RSA Key">
        <x-slot:actions>
            <a class="ui-btn ui-btn-primary" href="{{ route('admin.resource.create', ['resource' => 'rsa-keys']) }}">
                <x-icons name="plus" class="h-4 w-4" />
                {{ $hasRecords ? 'Rotasi RSA Key' : 'Generate RSA Key' }}
            </a>
        </x-slot:actions>

        @if ($isSuperAdmin)
            <section class="admin-list-toolbar mb-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <form class="w-full lg:max-w-md" method="get">
                        <label class="relative block">
                            <x-icons name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                            <input class="ui-input pl-10" type="search" name="search" value="{{ request('search') }}" placeholder="Cari RSA Key...">
                        </label>
                    </form>

                    @if (request()->filled('search'))
                        <a class="ui-btn ui-btn-soft" href="{{ url()->current() }}">Reset</a>
                    @endif
                </div>
            </section>
        @endif

        @if ($hasRecords)
            <div class="overflow-hidden rounded-[18px] border border-[var(--app-border)]">
                <table class="min-w-full divide-y divide-[var(--app-border)] text-left text-sm">
                    <thead class="bg-[var(--app-surface-soft)] text-xs uppercase tracking-[0.08em] text-[var(--app-muted)]">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Key Identifier</th>
                            <th class="px-5 py-4 font-semibold">Pemilik</th>
                            <th class="px-5 py-4 font-semibold">Fingerprint</th>
                            <th class="px-5 py-4 font-semibold">Dibuat</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--app-border)] bg-[var(--app-surface)]">
                        @foreach ($rows as $record)
                            @php($cells = $record['cells'] ?? [])
                            @php($raw = $record['raw'] ?? [])
                            @php($status = $cells[5] ?? '-')
                            @php($isActive = $status === 'Aktif')
                            @php($isDisabled = $status === 'Dinonaktifkan')
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[var(--app-text)]">{{ $cells[0] ?? '-' }}</div>
                                </td>
                                <td class="max-w-[260px] px-5 py-4 text-[var(--app-muted)]">{{ $cells[1] ?? '-' }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-[var(--app-muted)]" title="{{ $cells[4] ?? '-' }}">{{ $shortFingerprint($cells[4] ?? '-') }}</td>
                                <td class="px-5 py-4 text-[var(--app-muted)]">{{ isset($raw['created_at']) ? substr((string) $raw['created_at'], 0, 10) : '-' }}</td>
                                <td class="px-5 py-4">
                                    <span class="audit-badge {{ $isActive ? 'audit-badge-success' : ($isDisabled ? 'audit-badge-danger' : 'audit-badge-neutral') }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($isActive)
                                        <form method="POST" action="{{ route('admin.resource.action', ['resource' => 'rsa-keys', 'id' => $record['id'], 'action' => 'deactivate']) }}" onsubmit="return confirm('Nonaktifkan RSA Key ini? Key tidak akan digunakan untuk penerbitan sertifikat baru.');">
                                            @csrf
                                            <button type="submit" class="ui-btn ui-btn-danger-soft">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    @elseif ($isDisabled)
                                        <span class="text-xs font-medium text-[var(--app-muted)]">Tidak tersedia</span>
                                    @else
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('admin.resource.action', ['resource' => 'rsa-keys', 'id' => $record['id'], 'action' => 'activate']) }}">
                                                @csrf
                                                <button type="submit" class="ui-btn ui-btn-secondary">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="admin-detail-item text-center text-sm text-[var(--app-muted)]">
                {{ request()->filled('search') ? 'RSA Key tidak ditemukan.' : 'Belum ada RSA Key yang tersedia. Generate kunci terlebih dahulu agar sertifikat dapat ditandatangani secara digital.' }}
            </div>
        @endif
    </x-panel>
</x-layouts.admin>
