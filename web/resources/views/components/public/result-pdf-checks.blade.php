@props(['rows' => []])

<section class="bbh-result-card mt-7 px-6 py-6 sm:px-8 sm:py-8">
    <h2 class="bbh-result-card-title">Pemeriksaan PDF</h2>
    <div class="mt-6 grid gap-4 text-sm md:grid-cols-3">
        @foreach ($rows as $row)
            <div class="bbh-result-mini-card">
                <p class="font-semibold leading-6 text-[#626c65]">{{ $row[0] }}</p>
                <p class="bbh-result-value mt-2 truncate">{{ $row[1] }}</p>
            </div>
        @endforeach
    </div>
</section>
