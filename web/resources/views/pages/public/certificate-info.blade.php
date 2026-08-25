@php($page = trans('public.certificate_page'))

<x-layouts.guest :title="$page['meta_title']" :force-light="true">
    <div class="bbh-public min-h-screen overflow-hidden bg-white text-[#101820]">
        <x-public.navbar />

        <main class="pt-[72px] lg:pt-[84px]">
            <section class="bg-[#101820] px-5 py-16 text-white sm:px-8 lg:px-10 lg:py-20">
                <div class="mx-auto max-w-[1012px]">
                    <h1 class="bbh-h1 max-w-[780px] text-white">{{ $page['title'] }}</h1>
                    <p class="bbh-text mt-5 max-w-3xl text-white/68">{{ $page['intro'] }}</p>
                    <div class="mt-8">
                        <a href="{{ route('verification') }}#verifikasi" class="bbh-hero-action bg-[#007d12] text-white hover:bg-[#006f10]">{{ $page['verification_link'] }}</a>
                    </div>
                </div>
            </section>

            <section class="bg-white px-5 py-14 sm:px-8 lg:px-10 lg:py-16">
                <article class="mx-auto max-w-[980px] text-[#46524b]">
                    <p class="bbh-text max-w-[880px]">{{ $page['paragraphs'][0] }}</p>

                    <p class="bbh-text mt-8 font-semibold text-[#101820]">{{ $page['paragraphs'][1] }}</p>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach ($page['sign_verify'] as $item)
                            <div class="rounded-xl border border-[#dfe7df] bg-[#f8faf6] p-5">
                                <h2 class="bbh-h3 text-[#101820]">{{ $item['term'] }}</h2>
                                <p class="bbh-text mt-3">{{ $item['copy'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <p class="bbh-text mt-10 font-semibold text-[#101820]">{{ $page['goals_intro'] }}</p>

                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        @foreach ($page['goals'] as $item)
                            <div class="rounded-xl border border-[#dfe7df] bg-white p-5 shadow-[0_12px_30px_rgb(16_24_32_/_6%)]">
                                <h2 class="bbh-h3 text-[#101820]">{{ $item['term'] }}</h2>
                                <p class="bbh-text mt-3">{{ $item['copy'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    @foreach ($page['closing'] as $paragraph)
                        <p class="bbh-text mt-6">{{ $paragraph }}</p>
                    @endforeach

                    <div class="mt-10 rounded-2xl border border-[#dfe7df] bg-[#f8faf6] p-6">
                        <h2 class="bbh-h2 text-[#101820]">{{ $page['technical_title'] }}</h2>

                        <ul class="mt-4 grid list-none gap-3 md:grid-cols-3">
                            @foreach ($page['technical_items'] as $item)
                                <li class="bbh-text rounded-lg border border-[#dfe7df] bg-white px-4 py-3 font-semibold text-[#101820]">{{ $item }}</li>
                            @endforeach
                        </ul>

                        <div class="mt-6">
                            <a href="{{ route('verification') }}#verifikasi" class="bbh-text inline-flex rounded-full bg-[#007d12] px-6 py-3 font-extrabold text-white transition hover:bg-[#006f10]">{{ $page['verification_link'] }}</a>
                        </div>
                    </div>
                </article>
            </section>
        </main>

        <x-public.footer />
    </div>
</x-layouts.guest>
