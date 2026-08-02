@php($copy = \App\Support\PublicSiteCopy::current())

<header class="bbh-public-nav fixed inset-x-0 top-0 z-50 h-[72px] bg-[#101820] text-white lg:h-[84px]">
    <div class="mx-auto flex h-full max-w-[1840px] items-center justify-between px-5 sm:px-8 lg:px-6 xl:px-8">
        <a href="{{ route('verification') }}#beranda" class="flex min-w-0 items-center gap-3" aria-label="Beranda Bumiku Bumimu Hijau Farm">
            <img src="{{ asset('logo-main.webp') }}" alt="Logo Bumiku Bumimu Hijau Farm" class="h-10 w-10 shrink-0 object-contain lg:h-12 lg:w-12">
            <span class="bbh-small truncate font-extrabold leading-tight">Bumiku Bumimu Hijau Farm</span>
        </a>

        <nav class="hidden items-center gap-5 text-[13px] font-bold xl:gap-7 xl:text-sm lg:flex" aria-label="Navigasi utama">
            <a href="{{ route('verification') }}#beranda" class="transition-colors hover:text-[#5be37d]">{{ $copy['nav']['home'] }}</a>
            <a href="{{ route('verification') }}#tentang" class="transition-colors hover:text-[#5be37d]">{{ $copy['nav']['about'] }}</a>
            <a href="{{ route('verification') }}#fokus" class="transition-colors hover:text-[#5be37d]">{{ $copy['nav']['standards'] }}</a>
            <a href="{{ route('verification') }}#kegiatan" class="transition-colors hover:text-[#5be37d]">{{ $copy['nav']['activities'] }}</a>
            <a href="{{ route('verification') }}#verifikasi" class="transition-colors hover:text-[#5be37d]">{{ $copy['nav']['verification'] }}</a>
            <a href="{{ route('certificate.info') }}" class="transition-colors hover:text-[#5be37d]">{{ $copy['nav']['certificate'] }}</a>
            <a href="{{ route('location') }}" class="transition-colors hover:text-[#5be37d]">{{ $copy['nav']['location'] }}</a>
            <a href="{{ route('login') }}" class="rounded-full border border-white/35 px-4 py-2 transition-colors hover:border-white hover:bg-white hover:text-[#101820]">{{ $copy['nav']['login'] }}</a>
        </nav>

        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/25 lg:hidden" aria-label="Buka menu" aria-expanded="false" data-public-menu-button>
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
        </button>
    </div>

    <nav class="hidden border-t border-white/10 bg-[#101820] px-5 pb-6 pt-4 text-sm font-bold shadow-xl lg:hidden" aria-label="Navigasi seluler" data-public-menu>
        <div class="mx-auto grid max-w-[1840px] gap-1">
            <a href="{{ route('verification') }}#beranda" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['home'] }}</a>
            <a href="{{ route('verification') }}#tentang" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['about'] }}</a>
            <a href="{{ route('verification') }}#fokus" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['standards'] }}</a>
            <a href="{{ route('verification') }}#kegiatan" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['activities'] }}</a>
            <a href="{{ route('verification') }}#verifikasi" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['verification'] }}</a>
            <a href="{{ route('certificate.info') }}" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['certificate'] }}</a>
            <a href="{{ route('location') }}" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $copy['nav']['location'] }}</a>
            <a href="{{ route('login') }}" class="mt-2 rounded-full bg-white px-4 py-3 text-center text-[#101820]">{{ $copy['nav']['login'] }}</a>
        </div>
    </nav>
</header>
