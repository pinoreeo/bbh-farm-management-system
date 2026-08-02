@props(['mobile' => false])

<a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3" @if($mobile) data-mobile-sidebar-link @endif>
    <img class="h-9 w-9 shrink-0 object-contain" src="{{ asset('logo-main.webp') }}" alt="Logo Bumiku Bumimu Hijau Farm">
    <div class="min-w-0">
        <p class="admin-sidebar-brand truncate text-sm font-bold tracking-tight">BBH Farm</p>
        <p class="admin-sidebar-muted text-[10px] font-medium uppercase tracking-widest">Dashboard</p>
    </div>
</a>
