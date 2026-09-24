@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="beranda" class="bbh-hero-section relative scroll-mt-[72px] overflow-hidden bg-[#007f14] text-white lg:scroll-mt-[84px]">
    <div class="bbh-hero-pattern pointer-events-none absolute inset-0 z-0 text-[#006b12] lg:left-auto lg:w-[58%]" aria-hidden="true">
        <span class="absolute right-[-12%] top-[78px] h-[185px] w-[86%] rounded-[34px] border-[4px] border-current"></span>
        <span class="absolute right-[-6%] top-[285px] h-[150px] w-[92%] rounded-[30px] border-[4px] border-current"></span>
        <span class="absolute bottom-[-118px] right-[24%] h-[164px] w-[76%] rounded-[34px] border-[4px] border-current"></span>
    </div>

    <div class="bbh-hero-inner relative mx-auto grid min-h-full max-w-[1184px] items-center gap-7 px-6 pb-0 pt-10 sm:px-8 lg:grid-cols-[minmax(0,0.98fr)_minmax(380px,0.82fr)] lg:px-10 lg:pb-0 lg:pt-16 xl:px-0">
        <div class="bbh-hero-content relative z-10 max-w-[700px]">
            <p class="bbh-hero-brand">Bumiku Bumimu Hijau Farm</p>
            <h1 class="bbh-hero-title max-w-[760px] text-white">{{ $copy['hero']['title'] }}</h1>
            <p class="bbh-hero-copy mt-6 max-w-[560px] text-white/86">{{ $copy['hero']['copy'] }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="#verifikasi" class="bbh-hero-action bbh-hero-action-primary">{{ $copy['hero']['primary_action'] }}</a>
                <a href="{{ route('location') }}" class="bbh-hero-action bbh-hero-action-secondary">{{ $copy['hero']['secondary_action'] }}</a>
            </div>
        </div>

        <div class="bbh-hero-visual pointer-events-none relative z-10">
            <img src="{{ asset('hero-goats-cutout.webp') }}" alt="{{ $copy['hero']['goat_alt'] }}" class="bbh-goat-cutout relative z-10 max-w-none">
        </div>
    </div>
</section>
