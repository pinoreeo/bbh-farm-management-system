@props(['mobile' => false])

<a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3" @if($mobile) data-mobile-sidebar-link @endif>
    <img class="h-10 w-10 shrink-0 object-contain" src="{{ asset('logo-main.webp') }}" alt="Logo Bumiku Bumimu Hijau Farm">
    <div class="min-w-0">
        <p class="admin-sidebar-brand truncate text-[11px] font-medium">BBH Farm</p>
        <p class="admin-sidebar-muted text-[11px] font-medium">Dashboard</p>
    </div>
</a>
