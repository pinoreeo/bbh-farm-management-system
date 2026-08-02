@props(['forceLight' => false, 'title' => null])

<!doctype html>
<html lang="id" @if($forceLight) data-force-theme="light" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) && $title !== 'Bumiku Bumimu Hijau Farm' ? $title . ' - Bumiku Bumimu Hijau Farm' : 'Bumiku Bumimu Hijau Farm' }}</title>
    <link rel="icon" type="image/webp" href="{{ asset('logo-main.webp') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
