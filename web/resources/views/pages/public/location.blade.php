@php
    $page = trans('public.location_page');
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=Bumiku%20Bumimu%20Hijau%20Farm%20Ajibarang%20Banyumas';
@endphp

<x-layouts.guest :title="$page['meta_title']" :force-light="true">
    <div class="bbh-public min-h-screen overflow-hidden bg-white text-[var(--bbh-text)]">
        <x-public.navbar />

        <main class="bbh-public-page">
            <section class="bbh-public-section">
                <div class="bbh-public-container">
                    <h1 class="bbh-page-title max-w-[760px] text-[var(--bbh-text)]">{{ $page['title'] }}</h1>

                    <div class="mt-10 grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                        <div>
                            <svg class="h-9 w-9 text-[var(--bbh-action)]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z" />
                            </svg>
                            <h2 class="bbh-h2 mt-5">{{ $page['farm_name'] }}</h2>
                            <p class="bbh-text mt-6 max-w-xl text-[var(--bbh-muted)]">
                                {{ $page['address'] }}
                            </p>
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="bbh-public-action mt-6">{{ $page['maps_link'] }}</a>
                        </div>

                        <div class="relative min-h-[280px] overflow-hidden rounded-[14px] border border-[#dfe9d9] bg-[#f7faf4] shadow-none lg:min-h-[360px]">
                            <iframe
                                class="absolute inset-0 h-full w-full"
                                src="https://www.google.com/maps?q=Bumiku%20Bumimu%20Hijau%20Farm%20Ajibarang%20Banyumas&output=embed"
                                title="Peta lokasi {{ $page['farm_name'] }}"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <x-public.footer />
    </div>
</x-layouts.guest>
