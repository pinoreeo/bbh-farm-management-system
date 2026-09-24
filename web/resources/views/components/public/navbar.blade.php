@php
    $copy = \App\Support\PublicSiteCopy::current();
    $isHome = request()->routeIs('verification');
    $isCertificate = request()->routeIs('certificate.info');
    $isLocation = request()->routeIs('location');
@endphp

<header class="bbh-public-nav fixed inset-x-0 top-0 z-50 h-[72px] border-b border-white/10 bg-[#10231a]/97 text-white shadow-none backdrop-blur lg:h-[84px]">
    <div class="mx-auto flex h-full max-w-[1240px] items-center justify-between px-5 sm:px-8 lg:px-8">
        <a href="{{ route('verification') }}#beranda" class="flex min-w-0 items-center gap-3" aria-label="Beranda Bumiku Bumimu Hijau Farm">
            <img src="{{ asset('logo-main.webp') }}" alt="Logo Bumiku Bumimu Hijau Farm" class="h-9 w-9 shrink-0 object-contain lg:h-10 lg:w-10">
            <span class="bbh-small truncate leading-tight">Bumiku Bumimu Hijau Farm</span>
        </a>

        <nav class="hidden items-center gap-5 text-white/72 xl:gap-6 lg:flex" aria-label="Navigasi utama">
            <a href="{{ route('verification') }}#beranda" class="bbh-nav-link {{ $isHome ? 'is-active' : '' }}" data-public-section-link="beranda">{{ $copy['nav']['home'] }}</a>
            <a href="{{ route('verification') }}#tentang" class="bbh-nav-link" data-public-section-link="tentang">{{ $copy['nav']['about'] }}</a>
            <a href="{{ route('verification') }}#verifikasi" class="bbh-nav-link" data-public-section-link="verifikasi">{{ $copy['nav']['verification'] }}</a>
            <a href="{{ route('certificate.info') }}" class="bbh-nav-link {{ $isCertificate ? 'is-active' : '' }}">{{ $copy['nav']['certificate'] }}</a>
            <a href="{{ route('location') }}" class="bbh-nav-link {{ $isLocation ? 'is-active' : '' }}">{{ $copy['nav']['location'] }}</a>
            <a href="{{ route('login') }}" class="bbh-nav-action">{{ $copy['nav']['login'] }}</a>
        </nav>

        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/25 lg:hidden" aria-label="Buka menu" aria-expanded="false" data-public-menu-button>
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
        </button>
    </div>

    <nav class="hidden border-t border-white/10 bg-[#10231a] px-5 pb-6 pt-4 shadow-none lg:hidden" aria-label="Navigasi seluler" data-public-menu>
        <div class="mx-auto grid max-w-[1240px] gap-1">
            <a href="{{ route('verification') }}#beranda" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10 {{ $isHome ? 'is-active' : '' }}" data-public-section-link="beranda">{{ $copy['nav']['home'] }}</a>
            <a href="{{ route('verification') }}#tentang" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10" data-public-section-link="tentang">{{ $copy['nav']['about'] }}</a>
            <a href="{{ route('verification') }}#verifikasi" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10" data-public-section-link="verifikasi">{{ $copy['nav']['verification'] }}</a>
            <a href="{{ route('certificate.info') }}" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10 {{ $isCertificate ? 'is-active' : '' }}">{{ $copy['nav']['certificate'] }}</a>
            <a href="{{ route('location') }}" class="bbh-nav-link rounded-lg px-3 py-3 hover:bg-white/10 {{ $isLocation ? 'is-active' : '' }}">{{ $copy['nav']['location'] }}</a>
            <a href="{{ route('login') }}" class="bbh-nav-action mt-2 text-center">{{ $copy['nav']['login'] }}</a>
        </div>
    </nav>
</header>
