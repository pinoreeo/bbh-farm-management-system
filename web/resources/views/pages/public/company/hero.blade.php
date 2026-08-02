@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="beranda" class="bbh-hero-section relative scroll-mt-[72px] overflow-hidden bg-[#00aa13] text-white lg:h-[711px] lg:scroll-mt-[84px]">
    <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[52%] text-[#007d12] lg:block" aria-hidden="true">
        <span class="absolute right-[-8%] top-[78px] h-[238px] w-[91%] border-[5px] border-current"></span>
        <span class="absolute right-[5%] top-[20px] h-[90px] w-[74%] border-[5px] border-b-0 border-current"></span>
        <span class="absolute right-[6%] top-[183px] h-[310px] w-[25%] border-[5px] border-current"></span>
        <span class="absolute right-[39%] top-[183px] h-[310px] w-[25%] border-[5px] border-current"></span>
        <span class="absolute bottom-0 right-[-8%] h-[205px] w-[104%] border-[5px] border-current"></span>
    </div>

    <div class="bbh-hero-inner relative mx-auto h-full max-w-[1184px] px-6 py-10 sm:px-8 lg:px-0 lg:py-12">
        <div class="relative z-10 max-w-[760px]">
            <h1 class="bbh-hero-title max-w-[760px] text-white">{{ $copy['hero']['title'] }}</h1>
            <p class="bbh-hero-copy mt-6 max-w-[560px] text-white/88">{{ $copy['hero']['copy'] }}</p>
        </div>

        <img src="{{ asset('hero-goats-cutout.webp') }}" alt="{{ $copy['hero']['goat_alt'] }}" class="bbh-goat-cutout relative z-20 max-w-none lg:absolute lg:-bottom-[3px] lg:-right-[220px] lg:m-0 lg:w-[900px]">
    </div>
</section>
