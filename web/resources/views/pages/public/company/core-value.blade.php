@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="tentang" class="flex min-h-[176px] scroll-mt-[72px] items-center bg-[#101820] px-6 py-10 text-center text-white sm:px-8 lg:h-[176px] lg:min-h-0 lg:scroll-mt-[84px] lg:px-10 lg:py-0">
    <div class="mx-auto max-w-[1400px]">
        <h2 class="bbh-heading lg:whitespace-nowrap">{{ $copy['core']['title'] }}</h2>
    </div>
</section>

<div class="relative h-10 bg-[#101820]">
    <div class="absolute inset-x-0 bottom-0 z-0 h-10 rounded-t-[36px] bg-white lg:rounded-t-[56px]"></div>
</div>

<section class="bg-white px-6 py-10 sm:px-8 lg:px-10">
    <div class="mx-auto grid max-w-[1012px] gap-8 text-center sm:grid-cols-3 lg:gap-10">
        @foreach ($copy['core']['points'] as $point)
            <div>
                <h3 class="bbh-h3 font-extrabold">{{ $point[0] }}</h3>
                <p class="mt-4 bbh-text font-medium text-[#626c65]">{{ $point[1] }}</p>
            </div>
        @endforeach
    </div>
</section>
