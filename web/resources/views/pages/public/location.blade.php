@php($page = trans('public.location_page'))

<x-layouts.guest :title="$page['meta_title']" :force-light="true">
    <div class="bbh-public min-h-screen overflow-hidden bg-white text-[#101820]">
        <x-public.navbar />

        <main class="pt-[72px] lg:pt-[84px]">
            <section class="bg-[#101820] px-5 py-16 text-white sm:px-8 lg:px-10 lg:py-24">
                <div class="mx-auto max-w-[1184px]">
                    <h1 class="bbh-h1">{{ $page['title'] }}</h1>
                </div>
            </section>

            <section class="bg-white px-5 py-14 sm:px-8 lg:px-10 lg:py-20">
                <div class="mx-auto grid max-w-[1184px] gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                    <div>
                        <svg class="h-9 w-9 text-[#00aa13]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z" />
                        </svg>
                        <h2 class="bbh-h2 mt-5">{{ $page['farm_name'] }}</h2>
                        <p class="bbh-text mt-6 max-w-xl text-[#46524b]">
                            {{ $page['address'] }}
                        </p>
                        <a href="https://www.google.com/maps/search/?api=1&query=Bumiku%20Bumimu%20Hijau%20Farm%20Ajibarang%20Banyumas" target="_blank" rel="noopener noreferrer" class="bbh-text mt-4 inline-flex font-semibold text-[#00aa13] hover:text-[#008c15]">{{ $page['maps_link'] }}</a>
                    </div>

                    <div class="relative min-h-[280px] overflow-hidden rounded-[28px] bg-[#00aa13] text-[#008c15] lg:min-h-[360px]" aria-hidden="true">
                        <span class="absolute left-[12%] top-[14%] h-[88px] w-[240px] rotate-[-9deg] rounded-[24px] border-[10px] border-current"></span>
                        <span class="absolute right-[8%] top-[22%] h-[120px] w-[310px] rotate-[8deg] rounded-[28px] border-[12px] border-current"></span>
                        <span class="absolute bottom-[-14%] left-[18%] h-[220px] w-[420px] rotate-[-5deg] rounded-[40px] border-[16px] border-current"></span>
                        <span class="absolute left-1/2 top-1/2 grid h-24 w-24 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white text-[#00aa13] shadow-[0_18px_42px_rgb(0_0_0_/_18%)]">
                            <svg class="h-12 w-12" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z" />
                            </svg>
                        </span>
                    </div>
                </div>
            </section>
        </main>

        <x-public.footer />
    </div>
</x-layouts.guest>
