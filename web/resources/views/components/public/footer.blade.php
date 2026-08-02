@php
    $copy = \App\Support\PublicSiteCopy::current();
    $currentLocale = \App\Support\PublicSiteCopy::locale();
    $languages = \App\Support\PublicSiteCopy::languages();
    $whatsappAdmin = preg_replace('/\D+/', '', (string) config('services.bbh.whatsapp_admin')) ?? '';
    if (str_starts_with($whatsappAdmin, '0')) {
        $whatsappAdmin = '62'.substr($whatsappAdmin, 1);
    }

    $contactMessage = $copy['footer']['contact_message'];
    $contactUrl = $whatsappAdmin ? 'https://wa.me/'.$whatsappAdmin.'?text='.rawurlencode($contactMessage) : '#kontak';
@endphp

<footer id="kontak" class="bbh-public-footer scroll-mt-[84px] bg-[#101820] px-5 pb-4 pt-7 text-white sm:px-8 lg:px-10">
    <div class="mx-auto max-w-[1184px]">
        <div class="flex items-center gap-3">
            <img src="{{ asset('logo-main.webp') }}" alt="Logo Bumiku Bumimu Hijau Farm" class="h-10 w-10 object-contain">
            <p class="bbh-small font-extrabold">Bumiku Bumimu Hijau Farm</p>
        </div>

        <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr]">
            <div>
                <p class="bbh-small font-extrabold">{{ $copy['footer']['farm'] }}</p>
                <div class="mt-3 grid gap-2 text-xs text-white/60">
                    <a href="{{ route('verification') }}#beranda" class="hover:text-white">{{ $copy['nav']['home'] }}</a>
                    <a href="{{ route('verification') }}#tentang" class="hover:text-white">{{ $copy['nav']['about'] }}</a>
                    <a href="{{ route('verification') }}#fokus" class="hover:text-white">{{ $copy['nav']['standards'] }}</a>
                    <a href="{{ route('verification') }}#kegiatan" class="hover:text-white">{{ $copy['nav']['activities'] }}</a>
                </div>
            </div>
            <div>
                <p class="bbh-small font-extrabold">{{ $copy['footer']['services'] }}</p>
                <div class="mt-3 grid gap-2 text-xs text-white/60">
                    <a href="{{ route('verification') }}#verifikasi" class="hover:text-white">{{ $copy['nav']['verification'] }}</a>
                    <a href="{{ route('certificate.info') }}" class="hover:text-white">{{ $copy['nav']['certificate'] }}</a>
                    <a href="{{ route('login') }}" class="hover:text-white">{{ $copy['nav']['login'] }}</a>
                </div>
            </div>
            <div>
                <p class="bbh-small font-extrabold">{{ $copy['footer']['contact'] }}</p>
                <div class="mt-3 grid gap-2 text-xs text-white/60">
                    <a href="{{ route('location') }}" class="hover:text-white">{{ $copy['footer']['location'] }}</a>
                    <a href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-white">{{ $copy['footer']['contact_link'] }}</a>
                </div>
            </div>
        </div>

        <div class="bbh-footer-meta mt-5 flex flex-col gap-4 border-t border-white/10 pt-4 text-white/45 lg:flex-row lg:items-center lg:justify-between">
            <p class="bbh-small text-white/45">&copy; {{ date('Y') }} {{ $copy['footer']['copyright'] }}</p>
            <details class="group relative sm:w-[320px]">
                <summary class="inline-flex h-10 w-full cursor-pointer list-none items-center justify-between rounded-full border border-white/18 px-4 text-left text-sm font-normal text-white transition hover:border-white/35 hover:bg-white/5">
                    <span class="inline-flex items-center gap-3">
                        <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m5 8 6 6" />
                            <path d="m4 14 6-6 2-3" />
                            <path d="M2 5h12" />
                            <path d="M7 2h1" />
                            <path d="m22 22-5-10-5 10" />
                            <path d="M14 18h6" />
                        </svg>
                        {{ $languages[$currentLocale] }}
                    </span>
                    <svg class="h-4 w-4 text-white/80 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </summary>
                <div class="absolute bottom-12 right-0 z-20 w-full overflow-hidden rounded-2xl border border-white/10 bg-[#182430] py-2 shadow-[0_18px_48px_rgb(0_0_0_/_32%)]">
                    @foreach ($languages as $locale => $label)
                        <a href="{{ \App\Support\PublicSiteCopy::switchUrl($locale) }}" class="block px-4 py-3 text-sm text-white/75 transition hover:bg-white/10 hover:text-white {{ $currentLocale === $locale ? 'bg-white/10 text-white' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </details>
        </div>
    </div>
</footer>
