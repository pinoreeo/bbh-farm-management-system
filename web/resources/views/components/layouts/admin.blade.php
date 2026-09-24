@props(['title' => null, 'subtitle' => null, 'skeleton' => 'table', 'pageHeader' => true])

@php
    $pageTitle = isset($title) && $title !== 'Bumiku Bumimu Hijau Farm'
        ? $title . ' - Bumiku Bumimu Hijau Farm'
        : 'Bumiku Bumimu Hijau Farm';
@endphp

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="Dashboard pengelolaan operasional Bumiku Bumimu Hijau Farm.">
    <link rel="icon" type="image/webp" href="{{ asset('logo-main.webp') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[var(--app-bg)] antialiased">
    <x-admin.sidebar />

    <div class="layout-main" data-admin-main>
        <x-admin.topbar />

        <main class="content-wrap">
            @if ($pageHeader)
                <div class="mb-4">
                    <h1 class="admin-page-title">{{ $title ?? 'Dashboard' }}</h1>
                </div>
            @endif

            @if (session('adminApiStatus'))
                <div class="admin-alert admin-alert-warning">
                    {{ session('adminApiStatus') }}
                </div>
            @endif

            {{ $slot }}
        </main>

        <x-admin.page-skeleton :type="$skeleton" />
    </div>
</body>
</html>
