@props(['forceLight' => false, 'title' => null, 'description' => null])

@php
    $pageTitle = isset($title) && $title !== 'Bumiku Bumimu Hijau Farm'
        ? $title . ' - Bumiku Bumimu Hijau Farm'
        : 'Bumiku Bumimu Hijau Farm';
    $pageDescription = $description ?: 'Bumiku Bumimu Hijau Farm menyediakan informasi farm, sertifikat elektronik, dan verifikasi dokumen resmi peternakan.';
    $pageUrl = url()->current();
    $pageImage = asset('logo-main.webp');
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if($forceLight) data-force-theme="light" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    <link rel="icon" type="image/webp" href="{{ asset('logo-main.webp') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
