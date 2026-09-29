@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="fokus" class="scroll-mt-[72px] border-b border-[#dfe9d9] px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="bbh-company-container">
        <h2 class="bbh-heading">{{ $copy['production']['title'] }}</h2>
        <p class="bbh-text mt-3 max-w-2xl">{{ $copy['production']['copy'] }}</p>
        <div class="mt-7 grid items-stretch gap-4 md:grid-cols-3 lg:gap-5">
            @foreach ($copy['production']['cards'] as $item)
                <article class="bbh-stat-card relative h-full overflow-hidden border p-6 text-left shadow-none">
                    <h3 class="bbh-h3">{{ $item[0] }}</h3>
                    <p class="mt-3 bbh-text">{{ $item[1] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
