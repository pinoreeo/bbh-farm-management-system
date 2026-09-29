<x-layouts.admin title="RSA Key" skeleton="cards" :page-header="false">
    @php($rows = $records instanceof \Illuminate\Pagination\LengthAwarePaginator ? $records->getCollection() : collect($records ?? []))
    @php($hasRecords = $rows->isNotEmpty())
    @php($isSuperAdmin = (session('bbh_admin_user.role') ?? null) === 'super_admin')
    @php($shortFingerprint = fn ($value) => strlen((string) $value) > 14 ? substr((string) $value, 0, 5) . '....' . substr((string) $value, -5) : (string) $value)

    @if ($errors->any())
        <div class="admin-alert admin-alert-danger">
            <p class="font-semibold">Gagal</p>
            <p class="mt-1 theme-muted">Periksa kembali data RSA Key sebelum melanjutkan.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! empty($apiFailureMessage))
        <div class="admin-alert admin-alert-danger">
            <p class="font-semibold">Data Tidak Dapat Dimuat</p>
            <p class="mt-1 theme-muted">{{ $apiFailureMessage }}</p>
        </div>
    @elseif (! empty($dataTruncated))
        <div class="admin-alert admin-alert-warning">
            <p class="font-semibold">Sebagian Data Belum Ditampilkan</p>
            <p class="mt-1 theme-muted">Jumlah catatan melebihi batas tampilan. Hubungi pengelola sistem bila data yang dicari belum terlihat.</p>
        </div>
    @endif

    <header class="admin-resource-page-header">
        <div>
            <nav class="admin-resource-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>/</span>
                <span>RSA Key</span>
            </nav>
            <h1>RSA Key</h1>
            <p>Kelola kunci digital untuk sertifikat.</p>
        </div>

        <a class="ui-btn ui-btn-primary" href="{{ route('admin.resource.create', ['resource' => 'rsa-keys']) }}">
            <x-icons name="plus" class="h-4 w-4" />
            {{ $hasRecords ? 'Rotasi RSA Key' : 'Generate RSA Key' }}
        </a>
    </header>

    @if ($isSuperAdmin)
        <section class="admin-list-toolbar">
            <div class="admin-list-toolbar-controls">
                <form class="w-full sm:max-w-md" method="get">
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

    @if (empty($apiFailureMessage) && $hasRecords)
        <section class="admin-rsa-key-grid">
            @foreach ($rows as $record)
                @php($cells = $record['cells'] ?? [])
                @php($raw = $record['raw'] ?? [])
                @php($status = $cells[5] ?? '-')
                @php($isActive = $status === 'Aktif')
                @php($isDisabled = $status === 'Dinonaktifkan')
                <article class="admin-rsa-key-card">
                    <header class="admin-rsa-key-card-header">
                        <div class="min-w-0">
                            <p class="admin-resource-card-eyebrow">
                                <x-icons name="key" class="h-4 w-4" />
                                Kunci digital
                            </p>
                            <h2>{{ $cells[0] ?? '-' }}</h2>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="admin-resource-card-status" data-tone="{{ $isActive ? 'positive' : ($isDisabled ? 'negative' : 'neutral') }}">{{ $status }}</span>
                            <details class="admin-row-menu" data-row-menu>
                                <summary aria-label="Buka aksi RSA Key" title="Aksi">
                                    <x-icons name="more" class="h-4 w-4" />
                                </summary>
                                <div class="admin-row-menu-panel">
                                    @if ($isActive)
                                        <form method="POST" action="{{ route('admin.resource.action', ['resource' => 'rsa-keys', 'id' => $record['id'], 'action' => 'deactivate']) }}" data-skeleton-target="table" onsubmit="return confirm('Nonaktifkan RSA Key ini? Key tidak akan digunakan untuk penerbitan sertifikat baru.');">
                                            @csrf
                                            <button type="submit" class="admin-row-menu-action is-danger">
                                                <x-icons name="circle-x" class="h-4 w-4" />
                                                Nonaktifkan key
                                            </button>
                                        </form>
                                    @elseif (! $isDisabled)
                                        <form method="POST" action="{{ route('admin.resource.action', ['resource' => 'rsa-keys', 'id' => $record['id'], 'action' => 'activate']) }}" data-skeleton-target="table">
                                            @csrf
                                            <button type="submit" class="admin-row-menu-action">
                                                <x-icons name="check" class="h-4 w-4" />
                                                Aktifkan key
                                            </button>
                                        </form>
                                    @else
                                        <span class="admin-row-menu-empty">Tidak ada aksi tersedia</span>
                                    @endif
                                </div>
                            </details>
                        </div>
                    </header>

                    <dl class="admin-rsa-key-fields">
                        <div>
                            <dt>Pemilik</dt>
                            <dd>{{ $cells[1] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt>Algoritma</dt>
                            <dd>{{ $cells[2] ?? '-' }} - {{ $cells[3] ?? '-' }} bit</dd>
                        </div>
                        <div class="admin-rsa-key-fingerprint">
                            <dt>Fingerprint SHA-256</dt>
                            <dd title="{{ $cells[4] ?? '-' }}">{{ $shortFingerprint($cells[4] ?? '-') }}</dd>
                        </div>
                        <div>
                            <dt>Dibuat</dt>
                            <dd>{{ isset($raw['created_at']) ? substr((string) $raw['created_at'], 0, 10) : '-' }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </section>
    @elseif (empty($apiFailureMessage))
        <div class="admin-resource-card-empty">
            {{ request()->filled('search') ? 'RSA Key tidak ditemukan.' : 'Belum ada RSA Key. Buat RSA Key untuk penerbitan sertifikat.' }}
        </div>
    @endif

    @if (empty($apiFailureMessage) && $hasRecords && $records instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <x-admin.pagination :paginator="$records" />
    @endif
</x-layouts.admin>
