@props([])

@php
    $adminUser = session('bbh_admin_user', []);
    $initials = collect(explode(' ', trim($adminUser['name'] ?? 'Admin')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => substr($part, 0, 1))
        ->join('') ?: 'AD';
    $notifications = $notifications ?? [];
    $unreadNotifications = collect($notifications)->where('is_read', false)->count();
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
            <a class="admin-icon-button md:hidden" href="{{ route('admin.search') }}" aria-label="Cari data" title="Cari data">
                <x-icons name="search" class="h-6 w-6" />
            </a>
            @if ($isSuperAdmin)
                <a class="admin-icon-button" href="{{ route('admin.activity-logs') }}" aria-label="Log aktivitas">
                    <x-icons name="activity" class="h-6 w-6" />
                </a>
            @endif

            <div class="relative" data-notification-menu>
                <button class="relative admin-icon-button" type="button" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false" data-notification-toggle>
                    <x-icons name="bell" class="h-6 w-6" />
                    @if ($unreadNotifications > 0)
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500"></span>
                    @endif
                </button>

                <div class="admin-notification-menu" data-notification-panel hidden>
                    <div class="admin-notification-header">
                        <p>Notifikasi</p>
                        @if ($unreadNotifications > 0)
                            <span>{{ $unreadNotifications }} baru</span>
                        @endif
                    </div>
                    <div class="admin-notification-list thin-scrollbar">
                        @forelse (array_slice($notifications, 0, 5) as $item)
                            <a class="admin-notification-item" href="{{ $item['url'] ?? route('admin.dashboard') }}" data-read="{{ ($item['is_read'] ?? false) ? 'true' : 'false' }}">
                                <span class="font-medium text-[var(--app-text)]">{{ $item['title'] }}@unless ($item['is_read'] ?? false)<i></i>@endunless</span>
                                <span class="mt-1 block leading-snug text-[var(--app-muted)]">{{ $item['body'] }}</span>
                                <span class="mt-1 block text-xs italic text-[var(--app-muted)]/80">{{ $item['time'] }}</span>
                            </a>
                        @empty
                            <div class="px-4 py-5 text-sm text-[var(--app-muted)]">
                                Belum ada pengingat berdasarkan catatan saat ini.
                            </div>
                        @endforelse
                    </div>
                    <a class="admin-notification-footer" href="{{ route('admin.notifications') }}">Lihat semua notifikasi</a>
                </div>
            </div>

            <div class="relative" data-profile-menu>
                <button class="admin-profile-toggle" type="button" aria-label="Buka menu profil" aria-haspopup="true" aria-expanded="false" data-profile-toggle>
                    {{ $initials }}
                </button>

                <div class="admin-profile-menu" data-profile-panel hidden>
                    <div class="admin-profile-menu-head">
                        <p>{{ $adminUser['name'] ?? 'Admin' }}</p>
                        @if (! empty($adminUser['email']))
                            <span>{{ $adminUser['email'] }}</span>
                        @endif
                    </div>

                    <div class="admin-profile-menu-list">
                        <a href="{{ route('admin.profile') }}">
                            <x-icons name="settings" class="h-4 w-4" />
                            <span>Pengaturan</span>
                        </a>
                        <a href="{{ route('admin.notifications') }}">
                            <x-icons name="bell" class="h-4 w-4" />
                            <span>Notifikasi</span>
                        </a>
                    </div>

                    <form method="post" action="{{ route('logout') }}" class="admin-profile-menu-logout">
                        @csrf
                        <button type="submit">
                            <x-icons name="logout" class="h-4 w-4" />
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
