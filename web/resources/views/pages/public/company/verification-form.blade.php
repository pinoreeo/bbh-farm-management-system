@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="verifikasi" class="relative flex min-h-[400px] scroll-mt-[72px] items-center justify-center overflow-hidden bg-[#182430] px-6 py-10 text-white sm:px-8 lg:min-h-[430px] lg:scroll-mt-[84px] lg:px-10">
    <div class="mx-auto w-full max-w-[820px] text-center">
        <h2 class="bbh-heading">{{ $copy['verification']['title'] }}</h2>
        <p class="mx-auto mt-4 max-w-xl bbh-text font-medium text-white/65">{{ $copy['verification']['copy'] }}</p>

        <form class="mx-auto mt-8 flex max-w-[720px] flex-col gap-2 sm:flex-row" method="post" action="{{ route('verification.submit') }}" enctype="multipart/form-data">
            @csrf
            <div class="flex min-h-12 flex-1 overflow-hidden rounded-lg border border-white/12 bg-[#101820] text-white shadow-[0_18px_42px_rgb(0_0_0_/_22%)]">
                <input class="min-w-0 flex-1 bg-transparent px-4 bbh-text font-semibold outline-none placeholder:text-white/42 focus:placeholder:text-white/30" type="text" name="certificate_number" value="{{ old('certificate_number') }}" placeholder="{{ $copy['verification']['placeholder'] }}" data-certificate-input>
                <div class="hidden w-px bg-white/12 sm:my-3 sm:block"></div>
                <label class="flex min-w-[132px] cursor-pointer items-center justify-center px-4 bbh-text font-extrabold text-[#b8dc35] transition hover:bg-[#b8dc35]/10 hover:text-white">
                    {{ $copy['verification']['browse'] }}
                    <input type="file" name="pdf" accept="application/pdf,.pdf" hidden data-pdf-only>
                </label>
            </div>
            <button class="inline-flex h-12 items-center justify-center rounded-lg bg-[#00aa13] px-8 bbh-text font-extrabold text-white shadow-[0_12px_26px_rgb(0_170_19_/_24%)] transition hover:bg-[#09bf1d]" type="submit">{{ $copy['verification']['button'] }}</button>
        </form>
        <p class="mx-auto mt-3 hidden max-w-[720px] text-left bbh-text font-semibold text-[#b8dc35]" data-pdf-filename></p>
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
