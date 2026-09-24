@props([
    'adminUser' => [],
    'initials' => 'AD',
    'mobile' => false,
])

<div class="admin-sidebar-user-wrap">
    <div class="admin-sidebar-user-row">
        <a href="{{ route('admin.profile') }}" class="flex min-w-0 flex-1 items-center gap-3" @if($mobile) data-mobile-sidebar-link @endif>
            <div class="admin-sidebar-user-avatar">{{ $initials }}</div>
            <div class="min-w-0 leading-tight">
                <p class="admin-sidebar-brand truncate text-sm font-medium">{{ $adminUser['name'] ?? 'Demo Admin' }}</p>
                <p class="admin-sidebar-muted truncate text-xs">{{ $adminUser['role'] ?? 'Admin' }}</p>
            </div>
        </a>

        <form method="post" action="{{ route('logout') }}" class="shrink-0">
            @csrf
            <button class="admin-sidebar-logout" type="submit" aria-label="Keluar" title="Keluar">
                <x-icons name="logout" class="h-4 w-4" />
            </button>
        </form>
    </div>
</div>
