@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="verifikasi" class="relative scroll-mt-[72px] overflow-hidden bg-white px-6 py-12 text-[var(--bbh-text)] sm:px-8 lg:scroll-mt-[84px] lg:px-10 lg:py-16">
    <div class="mx-auto w-full max-w-[860px] text-center">
        <h2 class="bbh-heading">{{ $copy['verification']['title'] }}</h2>
        <p class="mx-auto mt-4 max-w-xl bbh-text text-[var(--bbh-muted)]">{{ $copy['verification']['copy'] }}</p>

        <form class="mx-auto mt-8 grid max-w-[780px] gap-2 rounded-[14px] border border-[#dfe9d9] bg-white p-2 shadow-none sm:grid-cols-[minmax(0,1fr)_auto] lg:grid-cols-[minmax(0,1fr)_auto_auto]" method="post" action="{{ route('verification.submit') }}" enctype="multipart/form-data">
            @csrf
            <label class="sr-only" for="certificate_number">{{ $copy['verification']['placeholder'] }}</label>
            <div class="flex min-h-10 overflow-hidden rounded-[10px] border border-[#dfe9d9] bg-white text-[var(--bbh-text)] transition focus-within:border-[var(--bbh-action)] focus-within:ring-2 focus-within:ring-[color:rgb(15_107_58_/_10%)]">
                <input id="certificate_number" class="min-w-0 flex-1 bg-transparent px-4 bbh-text font-medium outline-none placeholder:text-[#8b948e]" type="text" name="certificate_number" value="{{ old('certificate_number') }}" placeholder="{{ $copy['verification']['placeholder'] }}" data-certificate-input>
            </div>
            <label class="bbh-public-action-secondary h-10 cursor-pointer gap-2 sm:min-w-[126px]">
                <x-icons name="file" class="h-4 w-4" />
                <span>{{ $copy['verification']['browse'] }}</span>
                <input type="file" name="pdf" accept="application/pdf,.pdf" hidden data-pdf-only>
            </label>
            <button class="bbh-public-action h-10 gap-2 sm:col-span-2 lg:col-span-1" type="submit">
                <x-icons name="search" class="h-4 w-4" />
                <span>{{ $copy['verification']['button'] }}</span>
            </button>
        </form>
        <p class="mx-auto mt-3 hidden max-w-[720px] text-left bbh-text font-medium text-[var(--bbh-action)]" data-pdf-filename></p>
        <p class="mx-auto mt-3 hidden max-w-[720px] rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-left bbh-text text-red-800" data-pdf-error></p>

        @php($publicError = $verificationError ?? session('verificationError'))
        @if ($errors->any() || $publicError)
            @php($message = $errors->first() ?: $publicError)
            <div class="mx-auto mt-4 max-w-[720px] rounded-lg border border-red-300 bg-red-50 px-4 py-4 text-left bbh-text text-red-800">
                <p class="font-semibold">{{ $copy['verification']['failed'] }}</p>
                <p class="mt-1">{{ $message }}</p>
            </div>
        @endif
    </div>
</section>
