@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="fokus" class="scroll-mt-[72px] border-b border-[#dfe9d9] bg-white px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="mx-auto max-w-[1012px] text-center">
        <h2 class="bbh-heading">{{ $copy['production']['title'] }}</h2>
    </div>

    <div class="mx-auto mt-8 grid max-w-[1012px] items-stretch gap-5 md:grid-cols-3 lg:gap-6">
        @foreach ([
            ['#f3faef', '#d7e8ce', ...$copy['production']['cards'][0]],
            ['#f8faf7', '#dde7dc', ...$copy['production']['cards'][1]],
            ['#fffaf0', '#eadca7', ...$copy['production']['cards'][2]],
        ] as $item)
            <article class="bbh-stat-card relative h-full overflow-hidden border p-6 text-left shadow-none" style="background: {{ $item[0] }}; border-color: {{ $item[1] }};">
                <div class="relative mx-auto h-[130px] w-[220px]" aria-hidden="true">
                    <span class="absolute bottom-3 left-1/2 h-20 w-36 -translate-x-1/2 rotate-[-8deg] rounded-[16px] bg-[#4264d9]"></span>
                    <span class="absolute bottom-8 left-1/2 h-16 w-32 -translate-x-1/2 rotate-[-8deg] rounded-[12px] bg-[#f1d84f]"></span>
                    @if ($item[4] === 'genetic')
                        <span class="absolute left-1/2 top-0 h-24 w-16 -translate-x-1/2 rounded-t-[32px] bg-[var(--bbh-action)] shadow-none"></span>
                        <span class="absolute left-1/2 top-7 h-14 w-7 -translate-x-1/2 rounded-t-[14px] bg-white"></span>
                    @elseif ($item[4] === 'integrated')
                        <span class="absolute left-[31%] top-4 h-16 w-16 -translate-x-1/2 rounded-full border-[11px] border-white shadow-none"></span>
                        <span class="absolute left-1/2 top-0 h-16 w-16 -translate-x-1/2 rounded-full border-[11px] border-[var(--bbh-action)]"></span>
                        <span class="absolute left-[69%] top-4 h-16 w-16 -translate-x-1/2 rounded-full border-[11px] border-white"></span>
                    @else
                        <span class="absolute left-1/2 top-0 h-24 w-28 -translate-x-1/2 rotate-[4deg] rounded-[16px] bg-white shadow-none"></span>
                        <span class="absolute left-1/2 top-7 h-2 w-16 -translate-x-1/2 rounded-full bg-[var(--bbh-action)]"></span>
                        <span class="absolute left-1/2 top-12 h-2 w-16 -translate-x-1/2 rounded-full bg-[#101820]/25"></span>
                        <span class="absolute left-1/2 top-[68px] h-2 w-11 -translate-x-1/2 rounded-full bg-[#101820]/20"></span>
                    @endif
                </div>
                <h3 class="mt-2 bbh-h3">{{ $item[2] }}</h3>
                <p class="mt-4 bbh-text text-[var(--bbh-muted)]">{{ $item[3] }}</p>
            </article>
        @endforeach
    </div>
</section>
