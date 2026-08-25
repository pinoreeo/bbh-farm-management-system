@php($copy = $publicCopy ?? \App\Support\PublicSiteCopy::current())

<section id="verifikasi" class="relative flex min-h-[400px] scroll-mt-[72px] items-center justify-center overflow-hidden bg-[#182430] px-6 py-10 text-white sm:px-8 lg:min-h-[430px] lg:scroll-mt-[84px] lg:px-10">
    <div class="mx-auto w-full max-w-[820px] text-center">
        <h2 class="bbh-heading">{{ $copy['verification']['title'] }}</h2>
        <p class="mx-auto mt-4 max-w-xl bbh-text font-medium text-white/65">{{ $copy['verification']['copy'] }}</p>

        <form class="mx-auto mt-8 grid max-w-[780px] gap-2 sm:grid-cols-[minmax(0,1fr)_auto] lg:grid-cols-[minmax(0,1fr)_auto_auto]" method="post" action="{{ route('verification.submit') }}" enctype="multipart/form-data">
            @csrf
            <label class="sr-only" for="certificate_number">{{ $copy['verification']['placeholder'] }}</label>
            <div class="flex min-h-12 overflow-hidden rounded-lg border border-white/12 bg-[#101820] text-white shadow-[0_18px_42px_rgb(0_0_0_/_22%)]">
                <input id="certificate_number" class="min-w-0 flex-1 bg-transparent px-4 bbh-text font-semibold outline-none placeholder:text-white/42 focus:placeholder:text-white/30" type="text" name="certificate_number" value="{{ old('certificate_number') }}" placeholder="{{ $copy['verification']['placeholder'] }}" data-certificate-input>
            </div>
            <label class="inline-flex h-12 cursor-pointer items-center justify-center gap-2 rounded-lg border border-white/16 bg-white/[.06] px-5 bbh-text font-extrabold text-white/86 transition hover:border-white/28 hover:bg-white/[.1] hover:text-white sm:min-w-[136px]">
                <x-icons name="file" class="h-4 w-4" />
                <span>{{ $copy['verification']['browse'] }}</span>
                <input type="file" name="pdf" accept="application/pdf,.pdf" hidden data-pdf-only>
            </label>
            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-[#007d12] px-8 bbh-text font-extrabold text-white shadow-[0_18px_38px_rgb(0_0_0_/_18%)] transition hover:bg-[#006f10] sm:col-span-2 lg:col-span-1" type="submit">
                <x-icons name="search" class="h-4 w-4" />
                <span>{{ $copy['verification']['button'] }}</span>
            </button>
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
