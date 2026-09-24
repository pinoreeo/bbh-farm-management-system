@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section class="border-b border-[#dfe9d9] bg-[#f7faf4] px-6 py-12 text-[var(--bbh-text)] sm:px-8 lg:px-10 lg:py-16">
    <div class="mx-auto max-w-[1184px] text-center">
        <h2 class="bbh-heading mx-auto max-w-[900px]">{{ $copy['ecosystem']['title'] }}</h2>
    </div>

    <div class="mx-auto mt-8 grid max-w-[1172px] gap-4 md:grid-cols-3 lg:gap-5">
        @foreach ($copy['ecosystem']['cards'] as $item)
            <article class="flex h-full flex-col rounded-[14px] border border-[#dfe9d9] bg-white p-6 shadow-none">
                <div class="h-1.5 w-14 rounded-full" style="background: {{ $item[0] }};" aria-hidden="true"></div>
                <div class="mt-5 flex flex-1 flex-col">
                    <h3 class="bbh-h3 text-[var(--bbh-text)]">{{ $item[2] }}</h3>
                    <ul class="mt-6 grid gap-3 bbh-text text-[var(--bbh-muted)]">
                        @foreach ($item[3] as $detail)
                            <li class="flex items-center gap-3">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full border border-white shadow-[0_0_0_1px_rgb(16_48_31_/_10%)]" style="background: {{ $item[0] }};"></span>
                                {{ $detail }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="mt-7 border-t border-[#dfe9d9] pt-4 bbh-text font-semibold text-[var(--bbh-text)]">{{ $item[4] }}</div>
            </article>
        @endforeach
    </div>
</section>
