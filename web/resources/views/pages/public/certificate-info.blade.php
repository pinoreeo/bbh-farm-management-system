@php($page = trans('public.certificate_page'))

<x-layouts.guest :title="$page['meta_title']" :force-light="true">
    <div class="bbh-public min-h-screen overflow-hidden bg-white text-[#101820]">
        <x-public.navbar />

        <main class="pt-[72px] lg:pt-[84px]">
            <section class="bg-[#101820] px-5 py-16 text-white sm:px-8 lg:px-10 lg:py-20">
                <div class="mx-auto max-w-[1012px]">
                    <h1 class="bbh-h1 max-w-[780px] text-white">{{ $page['title'] }}</h1>
                    <p class="bbh-text mt-5 max-w-3xl text-white/68">{{ $page['intro'] }}</p>
                </div>
            </section>

            <section class="bg-white px-5 py-14 sm:px-8 lg:px-10 lg:py-16">
                <article class="bbh-text mx-auto max-w-[980px] text-[#46524b]">
                    <p>{{ $page['paragraphs'][0] }}</p>

                    <p class="mt-4">{{ $page['paragraphs'][1] }}</p>

                    <ol class="mt-2 list-decimal space-y-1 pl-6">
                        @foreach ($page['sign_verify'] as $item)
                            <li><strong class="font-semibold text-[#101820]">{{ $item['term'] }}</strong>, {{ $item['copy'] }}</li>
                        @endforeach
                    </ol>

                    <p class="mt-4">{{ $page['goals_intro'] }}</p>

                    <ol class="mt-2 list-decimal space-y-1 pl-6">
                        @foreach ($page['goals'] as $item)
                            <li><strong class="font-semibold text-[#101820]">{{ $item['term'] }}</strong>, {{ $item['copy'] }}</li>
                        @endforeach
                    </ol>

                    @foreach ($page['closing'] as $paragraph)
                        <p class="mt-4">{{ $paragraph }}</p>
                    @endforeach

                    <h2 class="bbh-h2 pt-7 text-[#101820]">{{ $page['technical_title'] }}</h2>

                    <ul class="mt-2 list-disc space-y-1 pl-6">
                        @foreach ($page['technical_items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>

                    <div class="mt-8">
                        <a href="{{ route('verification') }}#verifikasi" class="bbh-text font-semibold text-[#00aa13] hover:text-[#008c15]">{{ $page['verification_link'] }}</a>
                    </div>
                </article>
            </section>
        </main>

        <x-public.footer />
    </div>
</x-layouts.guest>
