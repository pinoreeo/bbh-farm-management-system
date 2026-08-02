@props([
    'adminUser' => [],
    'initials' => 'AD',
    'mobile' => false,
])

<div class="border-t border-[var(--app-border)] p-3">
    <div class="admin-sidebar-card">
        <a href="{{ route('admin.profile') }}" class="mb-2 flex min-w-0 items-center gap-3" @if($mobile) data-mobile-sidebar-link @endif>
            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[var(--app-text)] text-xs font-semibold text-white">{{ $initials }}</div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[var(--app-text)]">{{ $adminUser['name'] ?? 'Demo Admin' }}</p>
                <p class="admin-sidebar-muted truncate text-xs">{{ $adminUser['role'] ?? 'Admin' }}</p>
            </div>
        </a>

        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="ui-btn ui-btn-soft h-10 w-full justify-start" type="submit">
                <x-icons name="logout" class="h-4 w-4" />
                <span>Keluar</span>
            </button>
        </form>
    </div>
</div>
