@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="tentang" class="scroll-mt-[72px] border-b border-[#dfe9d9] bg-[#f7faf4] px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="mx-auto max-w-[1012px] text-center">
        <h2 class="bbh-heading mx-auto max-w-[900px]">{{ $copy['core']['title'] }}</h2>
    </div>

    <div class="relative mx-auto mt-8 grid max-w-[1012px] gap-4 text-center sm:grid-cols-3 lg:gap-5">
        @foreach ($copy['core']['points'] as $point)
            <article class="rounded-[14px] border border-[#dfe9d9] bg-white p-6 shadow-none">
                <h3 class="bbh-h3 text-[var(--bbh-text)]">{{ $point[0] }}</h3>
                <p class="mt-4 bbh-text text-[var(--bbh-muted)]">{{ $point[1] }}</p>
            </article>
        @endforeach
    </div>
</section>
