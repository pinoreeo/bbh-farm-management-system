@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section class="flex min-h-[176px] items-center bg-[#101820] px-6 py-10 text-center text-white sm:px-8 lg:h-[176px] lg:min-h-0 lg:px-10 lg:py-0">
    <h2 class="bbh-heading mx-auto max-w-[1184px]">{{ $copy['ecosystem']['title'] }}</h2>
</section>

<section class="bg-[#101820] px-6 py-10 text-white sm:px-8 lg:px-10">
    <div class="mx-auto grid max-w-[1172px] gap-6 md:grid-cols-3">
        @foreach ($copy['ecosystem']['cards'] as $item)
            <article class="flex h-full flex-col overflow-hidden rounded-[24px]" style="background: {{ $item[1] }};">
                <div class="flex flex-1 flex-col p-8" style="background: {{ $item[0] }}; border-radius: 24px;">
                    <h3 class="bbh-h3 font-extrabold">{{ $item[2] }}</h3>
                    <ul class="mt-6 grid gap-4 bbh-text font-bold text-white/90">
                        @foreach ($item[3] as $detail)
                            <li class="flex items-center gap-4">
                                <span class="h-3 w-3 shrink-0 rounded-full border-2 border-white"></span>
                                {{ $detail }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="px-8 py-4 bbh-text font-extrabold">{{ $item[4] }}</div>
            </article>
        @endforeach
    </div>
</section>
