@props(['title' => null, 'subtitle' => null, 'skeleton' => 'table'])

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) && $title !== 'Bumiku Bumimu Hijau Farm' ? $title . ' - Bumiku Bumimu Hijau Farm' : 'Bumiku Bumimu Hijau Farm' }}</title>
    <link rel="icon" type="image/webp" href="{{ asset('logo-main.webp') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[var(--app-bg)] antialiased">
    <x-admin.sidebar />

    <div class="layout-main" data-admin-main>
        <x-admin.topbar />

        <main class="content-wrap">
            <div class="mb-5">
                <h1 class="text-2xl font-semibold tracking-tight text-[var(--app-text)]">{{ $title ?? 'Dashboard' }}</h1>
            </div>

            {{ $slot }}
        </main>

        <x-admin.page-skeleton :type="$skeleton" />
    </div>
</body>
</html>
