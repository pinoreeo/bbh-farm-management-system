@props(['label', 'value', 'note' => null, 'tone' => 'neutral'])

<article class="dashboard-stat-card" data-tone="{{ $tone }}">
    <div class="relative flex h-full flex-col justify-between gap-5">
        <div>
            <p class="text-sm font-medium text-[var(--app-muted)]">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold leading-none tracking-tight text-[var(--app-text)]">{{ $value }}</p>
        </div>
        @if ($note)
            <p class="dashboard-stat-note w-fit">{{ $note }}</p>
        @endif
    </div>
    <svg class="dashboard-sparkline" viewBox="0 0 320 64" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 50 C36 46 48 50 72 44 C96 38 112 49 140 43 C168 37 180 31 208 38 C236 45 250 32 276 30 C294 28 306 24 320 22 L320 64 L0 64 Z" fill="currentColor"></path>
        <path d="M0 50 C36 46 48 50 72 44 C96 38 112 49 140 43 C168 37 180 31 208 38 C236 45 250 32 276 30 C294 28 306 24 320 22" fill="none" stroke="currentColor" stroke-width="3"></path>
    </svg>
</article>
