@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section class="bbh-ecosystem-section border-b border-[#dfe9d9] px-6 py-12 sm:px-8 lg:px-10 lg:py-16">
    <div class="bbh-company-container">
        <h2 class="bbh-heading">{{ $copy['ecosystem']['title'] }}</h2>
    </div>

    <div class="bbh-company-container bbh-ecosystem-grid mt-7">
        @foreach ($copy['ecosystem']['cards'] as $item)
            <article class="bbh-ecosystem-item flex h-full flex-col">
                <div class="flex flex-1 flex-col">
                    <h3 class="bbh-h3 text-[var(--bbh-text)]">{{ $item[2] }}</h3>
                    <ul class="mt-4 grid list-disc gap-3 pl-4 bbh-text text-[var(--bbh-muted)]">
                        @foreach ($item[3] as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                </div>
            </article>
        @endforeach
    </div>
</section>
