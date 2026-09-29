@props(['title' => null, 'subtitle' => null, 'titleIcon' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'admin-panel ui-card'.($padded ? ' p-4 lg:p-5' : '')]) }}>
    @if ($title || isset($actions))
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                @if ($title)
                    <h2 class="admin-section-title">
                        @if ($titleIcon)
                            <x-icons :name="$titleIcon" class="admin-section-title-icon h-4 w-4" />
                        @endif
                        <span>{{ $title }}</span>
                    </h2>
                @endif
                @if ($subtitle)
                    <p class="mt-1 text-sm text-[var(--app-muted)]">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </div>
    @endif

    {{ $slot }}
</section>
