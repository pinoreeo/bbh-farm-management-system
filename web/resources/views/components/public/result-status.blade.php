@props([
    'isValid' => false,
    'result' => [],
])

@php
    $panelClasses = $isValid
        ? 'border-[#cde4c8] bg-[#f3faef] text-[var(--bbh-text)]'
        : 'border-red-200 bg-red-50 text-red-900';
    $badgeClasses = $isValid ? 'bg-[var(--bbh-action)] text-white' : 'bg-red-100 text-red-800';
@endphp

<section class="bbh-result-status border px-6 py-6 sm:px-8 sm:py-7 {{ $panelClasses }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="bbh-result-status-title">
                {{ $result['status_label'] ?? ($isValid ? 'Valid' : 'Tidak Valid') }}
            </h2>
            <p class="bbh-result-status-text mt-3">
                {{ $result['status_message'] ?? ($isValid ? 'Sertifikat valid.' : 'Sertifikat tidak valid.') }}
            </p>
            @if (! empty($result['public_reason']))
                <p class="bbh-result-status-text mt-3">{{ $result['public_reason'] }}</p>
            @endif
        </div>
        <span class="bbh-result-badge inline-flex w-fit {{ $badgeClasses }}">
            {{ $isValid ? 'Valid' : 'Tidak Valid' }}
        </span>
    </div>
</section>
