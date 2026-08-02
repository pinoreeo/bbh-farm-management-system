@php
    $adminUser = session('bbh_admin_user', []);
    $isSuperAdmin = ($adminUser['role'] ?? null) === 'super_admin';
    $groups = [
        'Overview' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard'],
            ['label' => 'Data Kambing', 'route' => 'admin.animals', 'icon' => 'goat'],
            ['label' => 'Catatan Bobot', 'route' => 'admin.weight-records', 'icon' => 'scale'],
            ['label' => 'Kandang & Koloni', 'route' => 'admin.pens', 'icon' => 'home'],
            ['label' => 'Pindah Koloni', 'route' => 'admin.pen-movements', 'icon' => 'history'],
        ],
        'Perkawinan' => [
            ['label' => 'Periode Kawin', 'route' => 'admin.breeding-periods', 'icon' => 'calendar'],
            ['label' => 'Betina Kawin', 'route' => 'admin.breeding-females', 'icon' => 'female'],
            ['label' => 'Kebuntingan', 'route' => 'admin.pregnancy-checks', 'icon' => 'pregnancy'],
            ['label' => 'Kelahiran', 'route' => 'admin.birth-events', 'icon' => 'birth'],
            ['label' => 'Cempe Lahir', 'route' => 'admin.offspring-births', 'icon' => 'baby'],
        ],
        'Perawatan' => [
            ['label' => 'Kesehatan', 'route' => 'admin.health-treatments', 'icon' => 'stethoscope'],
            ['label' => 'Vaksinasi', 'route' => 'admin.vaccinations', 'icon' => 'syringe'],
            ['label' => 'Pascalahir', 'route' => 'admin.postnatal-care', 'icon' => 'baby'],
        ],
        'Dokumen' => [
            ['label' => 'Akte & Sertifikat', 'route' => 'admin.certificates', 'icon' => 'certificate'],
            ['label' => 'RSA Key', 'route' => 'admin.rsa-keys', 'icon' => 'key'],
            ['label' => 'Log Sertifikat', 'route' => 'admin.certificate-logs', 'icon' => 'history'],
        ],
    ];

    if ($isSuperAdmin) {
        $groups = ['Super Admin' => [
            ['label' => 'Manajemen Pengguna', 'route' => 'admin.users', 'icon' => 'key'],
        ]] + $groups;

        $groups['Dokumen'][] = ['label' => 'Log Aktivitas', 'route' => 'admin.activity-logs', 'icon' => 'activity'];
    }

    $initials = collect(explode(' ', trim($adminUser['name'] ?? 'Admin')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => substr($part, 0, 1))
        ->join('') ?: 'AD';
@endphp

<aside class="admin-sidebar-shell fixed left-0 top-0 z-30 hidden h-screen w-[260px] flex-col overflow-hidden border-r lg:flex">
    <div class="flex h-16 items-center gap-3 border-b border-[var(--app-border)] px-4">
        <x-admin.sidebar-brand />
    </div>

    <x-admin.sidebar-menu :groups="$groups" />

    <x-admin.sidebar-user-card :admin-user="$adminUser" :initials="$initials" />
</aside>

<div id="admin-mobile-sidebar" class="fixed inset-0 z-50 hidden lg:hidden" data-mobile-sidebar aria-hidden="true">
    <button class="absolute inset-0 bg-black/45" type="button" aria-label="Tutup menu" data-mobile-sidebar-close></button>

    <aside class="admin-sidebar-shell relative flex h-full w-[260px] max-w-[calc(100vw-3rem)] flex-col overflow-hidden border-r shadow-2xl">
        <div class="flex h-16 items-center justify-between gap-3 border-b border-[var(--app-border)] px-4">
            <x-admin.sidebar-brand mobile />

            <button class="ui-btn ui-btn-soft h-10 w-10 shrink-0 px-0" type="button" aria-label="Tutup menu" data-mobile-sidebar-close>
                <x-icons name="x" class="h-5 w-5" />
            </button>
        </div>

        <x-admin.sidebar-menu :groups="$groups" mobile />
        <x-admin.sidebar-user-card :admin-user="$adminUser" :initials="$initials" mobile />
    </aside>
</div>
