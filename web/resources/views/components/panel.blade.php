@props(['title', 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'admin-panel ui-card p-4 lg:p-5']) }}>
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-[var(--app-text)]">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-sm text-[var(--app-muted)]">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $actions ?? '' }}
    </div>

    {{ $slot }}
</section>
