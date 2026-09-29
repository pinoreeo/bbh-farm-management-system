@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="galeri" class="scroll-mt-[72px] border-b border-[#dfe9d9] px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="bbh-company-container">
        <h2 class="bbh-heading">{{ $copy['gallery']['title'] }}</h2>
        <p class="bbh-text mt-3">{{ $copy['gallery']['copy'] }}</p>
    </div>
    <div class="bbh-company-container relative mt-6" data-gallery-carousel>
        <div class="overflow-hidden">
            <div class="bbh-gallery-track" data-gallery-track>
                @foreach ($copy['gallery']['items'] as $item)
                    <figure class="bbh-gallery bbh-gallery-slide relative shrink-0 overflow-hidden">
                        <img src="{{ asset($item[0]) }}" alt="{{ $item[1] }}" class="bbh-gallery-photo w-full object-cover {{ $item[3] }}" loading="lazy">
                        <figcaption class="pt-4">
                            <h3 class="bbh-h3">{{ $item[1] }}</h3>
                            <p class="mt-2 max-w-xl bbh-text">{{ $item[2] }}</p>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>

        <button type="button" class="bbh-gallery-control absolute left-3 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-[#dfe9d9] bg-white/95 text-[var(--bbh-action)] shadow-none transition hover:bg-[#f7faf4]" aria-label="{{ $copy['gallery']['previous'] }}" data-gallery-prev>
            <x-icons name="chevron-down" class="h-5 w-5 rotate-90" />
        </button>
        <button type="button" class="bbh-gallery-control absolute right-3 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-[#dfe9d9] bg-white/95 text-[var(--bbh-action)] shadow-none transition hover:bg-[#f7faf4]" aria-label="{{ $copy['gallery']['next'] }}" data-gallery-next>
            <x-icons name="chevron-down" class="h-5 w-5 -rotate-90" />
        </button>

        <div class="mt-4 flex items-center justify-center gap-2" aria-label="{{ $copy['gallery']['position'] }}" data-gallery-dots></div>
    </div>
</section>
