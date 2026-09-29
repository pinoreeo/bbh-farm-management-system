@php
    $copy = $publicCopy ?? \App\Support\PublicSiteCopy::current();
    $whatsappAdmin = preg_replace('/\D+/', '', (string) config('services.bbh.whatsapp_admin')) ?? '';
    if (str_starts_with($whatsappAdmin, '0')) {
        $whatsappAdmin = '62'.substr($whatsappAdmin, 1);
    }

    $whatsappBaseUrl = 'https://wa.me/'.($whatsappAdmin ?: '');
    $visitMessage = $copy['cta']['visit_message'];
    $partnershipMessage = $copy['cta']['partnership_message'];
@endphp

<section id="kegiatan" class="scroll-mt-[72px] border-b border-[#dfe9d9] px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="bbh-company-container">
        <h2 class="bbh-heading">{{ $copy['cta']['title'] }}</h2>
        <div class="bbh-service-grid mt-7">
            @foreach ($copy['cta']['cards'] as $item)
                @php
                    $targetUrl = match ($item[3]) {
                        'visit' => $whatsappBaseUrl.'?text='.rawurlencode($visitMessage),
                        'partnership' => $whatsappBaseUrl.'?text='.rawurlencode($partnershipMessage),
                        default => '#verifikasi',
                    };
                    $external = in_array($item[3], ['visit', 'partnership'], true);
                @endphp
                <article class="bbh-service-item flex h-full flex-col">
                    <h3 class="bbh-h3">{{ $item[0] }}</h3>
                    <p class="mt-4 bbh-text text-[var(--bbh-muted)]">{{ $item[1] }}</p>
                    <div class="mt-auto pt-5">
                        <a href="{{ $targetUrl }}" @if ($external) target="_blank" rel="noopener noreferrer" @endif class="bbh-service-link">{{ $item[2] }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
