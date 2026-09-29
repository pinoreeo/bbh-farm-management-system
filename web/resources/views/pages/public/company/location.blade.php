@php
    $page = trans('public.location_page');
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=Bumiku%20Bumimu%20Hijau%20Farm%20Ajibarang%20Banyumas';
@endphp

<section id="lokasi" class="scroll-mt-[72px] border-b border-[#d4ded1] bg-white px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-14">
    <div class="bbh-company-container">
        <h2 class="bbh-heading">{{ $page['title'] }}</h2>

        <div class="mt-6 grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:gap-10">
            <div>
                <h3 class="bbh-h3">{{ $page['farm_name'] }}</h3>
                <p class="bbh-text mt-4 max-w-xl text-[var(--bbh-muted)]">{{ $page['address'] }}</p>
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="bbh-public-action mt-6">
                    {{ $page['maps_link'] }}
                </a>
            </div>

            <div class="relative h-[280px] overflow-hidden rounded-lg border border-[#d4ded1] bg-[#f3f6ef] lg:h-[300px]">
                <iframe
                    class="absolute inset-0 h-full w-full"
                    src="https://www.google.com/maps?q=Bumiku%20Bumimu%20Hijau%20Farm%20Ajibarang%20Banyumas&output=embed"
                    title="{{ $page['title'] }}"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        </div>
    </div>
</section>
