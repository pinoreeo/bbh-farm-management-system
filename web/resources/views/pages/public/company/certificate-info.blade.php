@php($page = ($publicCopy ?? \App\Support\PublicSiteCopy::current())['certificate_page'])

<section id="sertifikat" class="scroll-mt-[72px] border-b px-6 py-12 sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="bbh-company-container">
        <h2 class="bbh-heading">{{ $page['title'] }}</h2>
        <p class="bbh-text mt-4 max-w-3xl">{{ $page['intro'] }}</p>

        <div class="bbh-certificate-summary mt-7">
            <div>
                <h3 class="bbh-h3">{{ $page['details_title'] }}</h3>
                <ul class="bbh-text mt-3 grid list-disc gap-2 pl-4">
                    @foreach ($page['details'] as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h3 class="bbh-h3">{{ $page['verification_title'] }}</h3>
                <ul class="bbh-text mt-3 grid list-disc gap-2 pl-4">
                    @foreach ($page['methods'] as $method)
                        <li>{{ $method }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <p class="bbh-certificate-note bbh-text mt-6 max-w-4xl">{{ $page['print_note'] }}</p>
    </div>
</section>
