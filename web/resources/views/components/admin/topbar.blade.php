@props([])

@php
    $adminUser = session('bbh_admin_user', []);
    $initials = collect(explode(' ', trim($adminUser['name'] ?? 'Admin')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => substr($part, 0, 1))
        ->join('') ?: 'AD';
    $notifications = $notifications ?? [];
    $isSuperAdmin = ($adminUser['role'] ?? null) === 'super_admin';
@endphp

<header class="admin-topbar sticky top-0 z-20 border-b backdrop-blur-xl">
    <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-6">
        <div class="flex items-center gap-3">
            <button class="ui-btn ui-btn-soft h-9 w-9 px-0 lg:hidden" type="button" aria-label="Buka menu" aria-controls="admin-mobile-sidebar" aria-expanded="false" data-mobile-sidebar-open>
                <x-icons name="menu" class="h-5 w-5" />
            </button>
        </div>

        <form class="admin-global-search hidden md:block" method="get" action="{{ route('admin.search') }}" data-skeleton-target="table">
            <label>
                <span class="sr-only">Cari data admin</span>
                <x-icons name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari data..." autocomplete="off">
            </label>
        </form>

        <div class="flex items-center gap-2">
            <button class="admin-icon-button" type="button" aria-label="Ganti tema" data-theme-toggle>
                <x-icons name="moon" class="h-5 w-5 dark:hidden" />
                <x-icons name="sun" class="hidden h-5 w-5 dark:block" />
            </button>

            @if ($isSuperAdmin)
                <a class="admin-icon-button" href="{{ route('admin.activity-logs') }}" aria-label="Log aktivitas">
                    <x-icons name="activity" class="h-5 w-5" />
                </a>
            @endif

            <div class="relative" data-notification-menu>
                <button class="relative admin-icon-button" type="button" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false" data-notification-toggle>
                    <x-icons name="bell" class="h-5 w-5" />
                    @if (count($notifications) > 0)
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500"></span>
                    @endif
                </button>

                <div class="admin-notification-menu" data-notification-panel hidden>
                    <div class="admin-notification-header">
                        <p>Notifikasi</p>
                    </div>
                    <div class="admin-notification-list thin-scrollbar">
                        @forelse ($notifications as $item)
                            <a class="admin-notification-item" href="{{ $item['url'] ?? route('admin.dashboard') }}">
                                <span class="font-medium text-[var(--app-text)]">{{ $item['title'] }}</span>
                                <span class="mt-1 block leading-snug text-[var(--app-muted)]">{{ $item['body'] }}</span>
                                <span class="mt-1 block text-xs italic text-[var(--app-muted)]/80">{{ $item['time'] }}</span>
                            </a>
                        @empty
                            <div class="px-4 py-5 text-sm text-[var(--app-muted)]">
                                Tidak ada notifikasi prioritas saat ini.
                            </div>
                        @endforelse
                    </div>
                    <a class="admin-notification-footer" href="{{ route('admin.dashboard') }}">Buka dashboard</a>
                </div>
            </div>

            <a class="grid h-8 w-8 place-items-center rounded-full bg-[var(--app-accent)] text-xs font-medium text-white transition hover:bg-[var(--app-accent-strong)]" href="{{ route('admin.profile') }}" aria-label="Profil">
                {{ $initials }}
            </a>
        </div>
    </div>
</header>
