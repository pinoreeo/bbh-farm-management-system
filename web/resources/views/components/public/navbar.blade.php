@php
    $copy = \App\Support\PublicSiteCopy::current();
    $isHome = request()->routeIs('verification');
@endphp

<header class="bbh-public-nav fixed inset-x-0 top-0 z-50 h-[72px] border-b lg:h-[84px]">
    <div class="mx-auto flex h-full max-w-[1280px] items-center justify-between px-5 sm:px-8 lg:px-10">
        <a href="{{ route('verification') }}#beranda" class="flex min-w-0 items-center gap-3" aria-label="Beranda Bumiku Bumimu Hijau Farm">
            <img src="{{ asset('logo-main.webp') }}" alt="Logo Bumiku Bumimu Hijau Farm" class="h-9 w-9 shrink-0 object-contain lg:h-10 lg:w-10">
            <span class="bbh-small truncate leading-tight">Bumiku Bumimu Hijau Farm</span>
        </a>

        <nav class="hidden items-center gap-5 xl:gap-6 lg:flex" aria-label="Navigasi utama">
            <a href="{{ route('verification') }}#beranda" class="bbh-nav-link {{ $isHome ? 'is-active' : '' }}" data-public-section-link="beranda">{{ $copy['nav']['home'] }}</a>
            <a href="{{ route('verification') }}#tentang" class="bbh-nav-link" data-public-section-link="tentang">{{ $copy['nav']['about'] }}</a>
            <a href="{{ route('verification') }}#verifikasi" class="bbh-nav-link" data-public-section-link="verifikasi">{{ $copy['nav']['verification'] }}</a>
            <a href="{{ route('verification') }}#sertifikat" class="bbh-nav-link" data-public-section-link="sertifikat">{{ $copy['nav']['certificate'] }}</a>
            <a href="{{ route('verification') }}#lokasi" class="bbh-nav-link" data-public-section-link="lokasi">{{ $copy['nav']['location'] }}</a>
            <a href="{{ route('login') }}" class="bbh-nav-link">{{ $copy['nav']['login'] }}</a>
        </nav>

        <button type="button" class="bbh-nav-menu-button flex h-10 w-10 items-center justify-center border lg:hidden" aria-label="Buka menu" aria-expanded="false" data-public-menu-button>
            <x-icons name="menu" class="h-5 w-5" />
        </button>
    </div>

    <nav class="bbh-nav-mobile hidden border-t px-5 pb-6 pt-4 lg:hidden" aria-label="Navigasi seluler" data-public-menu>
        <div class="mx-auto grid max-w-[1280px] gap-1">
            <a href="{{ route('verification') }}#beranda" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10 {{ $isHome ? 'is-active' : '' }}" data-public-section-link="beranda">{{ $copy['nav']['home'] }}</a>
            <a href="{{ route('verification') }}#tentang" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10" data-public-section-link="tentang">{{ $copy['nav']['about'] }}</a>
            <a href="{{ route('verification') }}#verifikasi" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10" data-public-section-link="verifikasi">{{ $copy['nav']['verification'] }}</a>
            <a href="{{ route('verification') }}#sertifikat" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10" data-public-section-link="sertifikat">{{ $copy['nav']['certificate'] }}</a>
            <a href="{{ route('verification') }}#lokasi" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10" data-public-section-link="lokasi">{{ $copy['nav']['location'] }}</a>
            <a href="{{ route('login') }}" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['login'] }}</a>
        </div>
    </nav>
</header>
