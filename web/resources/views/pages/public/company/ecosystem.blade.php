@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section class="flex min-h-[176px] items-center bg-[#101820] px-6 py-10 text-center text-white sm:px-8 lg:h-[176px] lg:min-h-0 lg:px-10 lg:py-0">
    <h2 class="bbh-heading mx-auto max-w-[1184px]">{{ $copy['ecosystem']['title'] }}</h2>
</section>

<section class="bg-[#101820] px-6 py-10 text-white sm:px-8 lg:px-10">
    <div class="mx-auto grid max-w-[1172px] gap-4 md:grid-cols-3 lg:gap-5">
        @foreach ($copy['ecosystem']['cards'] as $item)
            <article class="flex h-full flex-col rounded-2xl border border-white/10 bg-white/[.045] p-6 shadow-[0_18px_42px_rgb(0_0_0_/_14%)]">
                <div class="h-1.5 w-14 rounded-full" style="background: {{ $item[0] }};" aria-hidden="true"></div>
                <div class="mt-5 flex flex-1 flex-col">
                    <h3 class="bbh-h3 font-extrabold text-white">{{ $item[2] }}</h3>
                    <ul class="mt-6 grid gap-3 bbh-text font-semibold text-white/78">
                        @foreach ($item[3] as $detail)
                            <li class="flex items-center gap-3">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full border border-white/35" style="background: {{ $item[0] }};"></span>
                                {{ $detail }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="mt-7 border-t border-white/10 pt-4 bbh-text font-extrabold text-white/88">{{ $item[4] }}</div>
            </article>
        @endforeach
    </div>
</section>
