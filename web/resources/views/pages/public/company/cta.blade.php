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

<section id="kegiatan" class="scroll-mt-[72px] bg-white px-6 py-10 sm:px-8 lg:scroll-mt-[84px] lg:px-10">
    <div class="mx-auto max-w-[1012px]">
        <h2 class="bbh-heading text-center">{{ $copy['cta']['title'] }}</h2>
        <div class="mt-8 grid gap-8 md:grid-cols-3 md:gap-10">
            @foreach ($copy['cta']['cards'] as $item)
                @php
                    $targetUrl = match ($item[3]) {
                        'visit' => $whatsappBaseUrl.'?text='.rawurlencode($visitMessage),
                        'partnership' => $whatsappBaseUrl.'?text='.rawurlencode($partnershipMessage),
                        default => '#verifikasi',
                    };
                    $external = in_array($item[3], ['visit', 'partnership'], true);
                @endphp
                <div class="flex h-full flex-col">
                    <h3 class="bbh-h3 font-extrabold">{{ $item[0] }}</h3>
                    <p class="mt-4 bbh-text font-medium text-[#626c65]">{{ $item[1] }}</p>
                    <div class="mt-auto pt-8">
                        <a href="{{ $targetUrl }}" @if ($external) target="_blank" rel="noopener noreferrer" @endif class="bbh-small inline-flex rounded-full bg-[#007d12] px-6 py-2 font-extrabold text-white transition hover:bg-[#006f10]">{{ $item[2] }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
