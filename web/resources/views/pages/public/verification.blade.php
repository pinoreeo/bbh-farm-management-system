@php($publicCopy = \App\Support\PublicSiteCopy::current())

<x-layouts.guest :title="$publicCopy['nav']['home']" :force-light="true">
    <div class="bbh-public bbh-profile min-h-screen overflow-hidden bg-white text-[#101820]">
        <x-public.navbar />

        <main class="pt-[72px] lg:pt-[84px]">
            @include('pages.public.company.hero')
            @include('pages.public.company.core-value')
            @include('pages.public.company.production-standards')
            @include('pages.public.company.cta')
            @include('pages.public.company.gallery')
            @include('pages.public.company.ecosystem')
            @include('pages.public.company.verification-form')
        </main>

        <x-public.footer />
    </div>
</x-layouts.guest>
