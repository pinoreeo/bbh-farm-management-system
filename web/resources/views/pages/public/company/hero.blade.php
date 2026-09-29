@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="beranda" class="bbh-hero-section relative scroll-mt-[72px] overflow-hidden text-white lg:scroll-mt-[84px]">
    <img src="{{ asset('hero-landing-app.webp') }}" alt="{{ $copy['hero']['goat_alt'] }}" class="bbh-hero-photo" fetchpriority="high">
    <div class="bbh-hero-shade" aria-hidden="true"></div>
    <div class="bbh-hero-inner bbh-company-container relative">
        <div class="bbh-hero-content relative z-10">
            <p class="bbh-hero-brand">{{ $copy['hero']['eyebrow'] }}</p>
            <h1 class="bbh-hero-title">{{ $copy['hero']['title'] }}</h1>
            <p class="bbh-hero-copy mt-5">{{ $copy['hero']['copy'] }}</p>
            <p class="bbh-hero-place mt-6">{{ $copy['hero']['location'] }}</p>
        </div>
    </div>
</section>
