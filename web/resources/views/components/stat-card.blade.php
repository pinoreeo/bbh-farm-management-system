@props(['label', 'value', 'note' => null, 'tone' => 'neutral', 'trend' => [], 'icon' => 'dashboard'])

@php
    $trendValues = array_values(array_map('intval', is_array($trend) ? $trend : []));
    $trendValues = $trendValues !== [] ? $trendValues : [0, 0];
    $maxTrend = max($trendValues);
    $minTrend = min($trendValues);
    $isFlatTrend = $maxTrend === $minTrend;
    $range = max(1, $maxTrend - $minTrend);
    $step = count($trendValues) > 1 ? 220 / (count($trendValues) - 1) : 0;
    $sparklinePoints = array_map(function (int $item, int $index) use ($isFlatTrend, $minTrend, $range, $step) {
        return [
            'x' => round($step * $index, 2),
            'y' => $isFlatTrend ? 38 : round(62 - ((($item - $minTrend) / $range) * 42), 2),
            'value' => $item,
        ];
    }, $trendValues, array_keys($trendValues));
    $sparklineLine = 'M'.$sparklinePoints[0]['x'].' '.$sparklinePoints[0]['y'];

    for ($index = 1, $total = count($sparklinePoints); $index < $total; $index++) {
        $previous = $sparklinePoints[$index - 1];
        $current = $sparklinePoints[$index];
        $controlOffset = round(($current['x'] - $previous['x']) / 2, 2);
        $sparklineLine .= ' C'.($previous['x'] + $controlOffset).' '.$previous['y'].' '.($current['x'] - $controlOffset).' '.$current['y'].' '.$current['x'].' '.$current['y'];
    }

    $sparklineFirst = $sparklinePoints[0];
    $sparklineLast = $sparklinePoints[array_key_last($sparklinePoints)];
    $sparklineArea = $sparklineLine.' L'.$sparklineLast['x'].' 72 L'.$sparklineFirst['x'].' 72 Z';
    $gradientId = 'statSparkline'.md5($label.$tone.implode(',', $trendValues));
@endphp

<article class="dashboard-stat-card" data-tone="{{ $tone }}">
    <div class="relative flex h-full flex-col justify-between gap-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="admin-meta-text">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold leading-none text-[var(--app-text)]">{{ $value }}</p>
            </div>
            <span class="dashboard-stat-icon" aria-hidden="true">
                <x-icons :name="$icon" class="h-5 w-5" />
            </span>
        </div>
        <div>
            @if ($note)
                <p class="dashboard-stat-note w-fit">{{ $note }}</p>
            @endif
            <svg class="dashboard-stat-sparkline" viewBox="0 0 220 72" preserveAspectRatio="none" role="img" aria-label="Tren {{ $label }}">
                <defs>
                    <linearGradient id="{{ $gradientId }}" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="var(--stat-accent, var(--app-accent))" stop-opacity=".18" />
                        <stop offset="100%" stop-color="var(--stat-accent, var(--app-accent))" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <title>Tren {{ $label }}: {{ implode(', ', $trendValues) }}</title>
                <path class="dashboard-stat-sparkline-area" d="{{ $sparklineArea }}" fill="url(#{{ $gradientId }})" />
                <path class="dashboard-stat-sparkline-line" d="{{ $sparklineLine }}" />
            </svg>
        </div>
    </div>
</article>
