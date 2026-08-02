@props([
    'title',
    'rows' => [],
    'description' => null,
    'photoUrl' => null,
    'class' => '',
])

<section {{ $attributes->merge(['class' => trim('bbh-result-card px-6 py-6 sm:px-8 sm:py-8 '.$class)]) }}>
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="bbh-result-card-title">{{ $title }}</h2>
            @if ($description)
                <p class="mt-2 text-sm font-semibold leading-6 text-[#626c65]">{{ $description }}</p>
            @endif
        </div>
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" alt="Foto kambing" class="h-28 w-28 rounded-[20px] border object-cover" style="border-color: var(--result-border);">
        @endif
    </div>

    <dl class="mt-6 grid gap-4">
        @foreach ($rows as $row)
            <div class="bbh-result-row flex justify-between gap-6 border-b last:border-b-0 last:pb-0">
                <dt>{{ $row[0] }}</dt>
                <dd class="max-w-[360px] truncate text-right">{{ $row[1] ?: '-' }}</dd>
            </div>
        @endforeach
    </dl>
</section>
