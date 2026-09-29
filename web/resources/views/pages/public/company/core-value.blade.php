@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="tentang" class="scroll-mt-[72px] border-b border-[#dfe9d9] px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="bbh-company-container bbh-about-grid">
        <div class="bbh-about-intro">
            <p class="bbh-section-label">{{ $copy['nav']['about'] }}</p>
            <h2 class="bbh-heading mt-3">{{ $copy['core']['title'] }}</h2>
            <p class="bbh-text mt-4">{{ $copy['core']['copy'] }}</p>
        </div>
        <div class="bbh-about-points">
            @foreach ($copy['core']['points'] as $point)
                <div class="bbh-about-point">
                    <h3 class="bbh-h3">{{ $point[0] }}</h3>
                    <p class="bbh-text mt-1">{{ $point[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
