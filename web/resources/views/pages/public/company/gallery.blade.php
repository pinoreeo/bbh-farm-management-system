@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="galeri" class="scroll-mt-[72px] bg-white px-6 py-10 sm:px-8 lg:scroll-mt-[84px] lg:px-10">
    <h2 class="bbh-heading text-center">{{ $copy['gallery']['title'] }}</h2>
    <div class="relative mx-auto mt-8 max-w-[1184px]" data-gallery-carousel>
        <div class="overflow-hidden">
            <div class="bbh-gallery-track" data-gallery-track>
                @foreach ($copy['gallery']['items'] as $item)
                    <figure class="bbh-gallery bbh-gallery-slide relative h-[440px] shrink-0 overflow-hidden bg-[#101820] text-white md:h-[390px] lg:h-[410px]">
                        <img src="{{ asset($item[0]) }}" alt="{{ $item[1] }} di Bumiku Bumimu Hijau Farm" class="h-full w-full object-cover {{ $item[3] }}" loading="lazy">
                        <span class="absolute inset-0 bg-[#101820]/28" aria-hidden="true"></span>
                        <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-[#101820]/95 via-[#101820]/58 to-transparent px-6 pb-8 pt-24 sm:px-8">
                            <h3 class="bbh-h3 font-extrabold">{{ $item[1] }}</h3>
                            <p class="mt-2 max-w-xl bbh-text font-semibold text-white/85">{{ $item[2] }}</p>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>

        <button type="button" class="absolute left-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/92 text-[#101820] shadow-lg transition-transform hover:scale-105" aria-label="{{ $copy['gallery']['previous'] }}" data-gallery-prev>
            <span class="block h-3.5 w-3.5 translate-x-0.5 rotate-[135deg] border-b-[3px] border-r-[3px] border-current" aria-hidden="true"></span>
        </button>
        <button type="button" class="absolute right-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/92 text-[#101820] shadow-lg transition-transform hover:scale-105" aria-label="{{ $copy['gallery']['next'] }}" data-gallery-next>
            <span class="block h-3.5 w-3.5 -translate-x-0.5 rotate-[-45deg] border-b-[3px] border-r-[3px] border-current" aria-hidden="true"></span>
        </button>

        <div class="mt-4 flex items-center justify-center gap-2" aria-label="{{ $copy['gallery']['position'] }}" data-gallery-dots></div>
    </div>
</section>
