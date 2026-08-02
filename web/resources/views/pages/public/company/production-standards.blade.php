@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="fokus" class="scroll-mt-[72px] bg-white px-6 py-10 sm:px-8 lg:scroll-mt-[84px] lg:px-10">
    <div class="mx-auto max-w-[1012px] text-center">
        <h2 class="bbh-heading">{{ $copy['production']['title'] }}</h2>
    </div>

    <div class="mx-auto mt-8 grid max-w-[1012px] items-stretch gap-8 md:grid-cols-3 lg:gap-10">
        @foreach ([
            ['#7ccc6c', '#b2e5b8', ...$copy['production']['cards'][0]],
            ['#d48bc8', '#d0b4d4', ...$copy['production']['cards'][1]],
            ['#ffae62', '#d0b4d4', ...$copy['production']['cards'][2]],
        ] as $item)
            <article class="bbh-stat-card relative h-full overflow-hidden p-7 text-left" style="background: {{ $item[0] }}; box-shadow: 0 0 32px 10px {{ $item[1] }};">
                <div class="relative mx-auto h-[130px] w-[220px]" aria-hidden="true">
                    <span class="absolute bottom-3 left-1/2 h-20 w-36 -translate-x-1/2 rotate-[-8deg] rounded-[16px] bg-[#4264d9]"></span>
                    <span class="absolute bottom-8 left-1/2 h-16 w-32 -translate-x-1/2 rotate-[-8deg] rounded-[12px] bg-[#f1d84f]"></span>
                    @if ($item[4] === 'genetic')
                        <span class="absolute left-1/2 top-0 h-24 w-16 -translate-x-1/2 rounded-t-[32px] bg-[#65ef35] shadow-[8px_9px_0_rgb(16_24_32_/_16%)]"></span>
                        <span class="absolute left-1/2 top-7 h-14 w-7 -translate-x-1/2 rounded-t-[14px] bg-white"></span>
                    @elseif ($item[4] === 'integrated')
                        <span class="absolute left-[31%] top-4 h-16 w-16 -translate-x-1/2 rounded-full border-[11px] border-white shadow-[6px_7px_0_rgb(16_24_32_/_15%)]"></span>
                        <span class="absolute left-1/2 top-0 h-16 w-16 -translate-x-1/2 rounded-full border-[11px] border-[#00aa13]"></span>
                        <span class="absolute left-[69%] top-4 h-16 w-16 -translate-x-1/2 rounded-full border-[11px] border-white"></span>
                    @else
                        <span class="absolute left-1/2 top-0 h-24 w-28 -translate-x-1/2 rotate-[4deg] rounded-[16px] bg-white shadow-[8px_9px_0_rgb(16_24_32_/_18%)]"></span>
                        <span class="absolute left-1/2 top-7 h-2 w-16 -translate-x-1/2 rounded-full bg-[#00aa13]"></span>
                        <span class="absolute left-1/2 top-12 h-2 w-16 -translate-x-1/2 rounded-full bg-[#101820]/25"></span>
                        <span class="absolute left-1/2 top-[68px] h-2 w-11 -translate-x-1/2 rounded-full bg-[#101820]/20"></span>
                    @endif
                </div>
                <h3 class="mt-2 bbh-h3 font-extrabold">{{ $item[2] }}</h3>
                <p class="mt-4 bbh-text font-medium text-[#173124]/75">{{ $item[3] }}</p>
            </article>
        @endforeach
    </div>
</section>
