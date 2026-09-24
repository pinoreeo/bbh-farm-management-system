@php($page = trans('public.certificate_page'))

<x-layouts.guest :title="$page['meta_title']" :force-light="true">
    <div class="bbh-public min-h-screen overflow-hidden bg-white text-[var(--bbh-text)]">
        <x-public.navbar />

        <main class="bbh-public-page">
            <section class="bbh-public-section">
                <div class="bbh-public-container">
                    <article class="bbh-certificate-article bbh-public-readable text-[var(--bbh-text)]">
                        <h1 class="bbh-page-title">{{ $page['title'] }}</h1>

                        <p class="bbh-certificate-text mt-5">{{ $page['intro'] }}</p>
                        <p class="bbh-certificate-text mt-5">{{ $page['paragraphs'][0] }}</p>

                        <h2 class="bbh-h2 mt-10">{{ $page['process_title'] }}</h2>
                        <p class="bbh-certificate-text mt-6">{{ $page['paragraphs'][1] }}</p>

                        <ol class="bbh-certificate-list mt-4 list-decimal space-y-1 pl-10">
                            @foreach ($page['sign_verify'] as $item)
                                <li><strong>{{ $item['term'] }}</strong>, {{ $item['copy'] }}</li>
                            @endforeach
                        </ol>

                        <h2 class="bbh-h2 mt-10">{{ $page['goals_title'] }}</h2>
                        <p class="bbh-certificate-text mt-6">{{ $page['goals_intro'] }}</p>

                        <ol class="bbh-certificate-list mt-4 list-decimal space-y-1 pl-10">
                            @foreach ($page['goals'] as $item)
                                <li><strong>{{ $item['term'] }}</strong>, {{ $item['copy'] }}</li>
                            @endforeach
                        </ol>

                        <h2 class="bbh-h2 mt-10">{{ $page['verification_note_title'] }}</h2>

                        <div class="mt-5 grid gap-5 border-y border-[#dfe9d9] py-6">
                            @foreach ($page['closing'] as $paragraph)
                                <p class="bbh-certificate-text">{{ $paragraph }}</p>
                            @endforeach
                        </div>

                        <h2 class="bbh-h2 mt-10">{{ $page['technical_title'] }}</h2>

                        <ul class="bbh-certificate-list mt-3 list-disc space-y-1 pl-10">
                            @foreach ($page['technical_items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>

                        <div class="mt-9">
                            <a href="{{ route('verification') }}#verifikasi" class="bbh-public-action">{{ $page['verification_link'] }}</a>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <x-public.footer />
    </div>
</x-layouts.guest>
