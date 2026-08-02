@props(['name', 'class' => 'h-5 w-5'])

@php
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        'goat' => '<path d="M7 8.5c1.4-1.7 3-2.5 5-2.5s3.6.8 5 2.5"/><path d="M6.5 8.5 4.5 5M17.5 8.5l2-3.5"/><path d="M5 13c0-3 2.7-5.5 7-5.5S19 10 19 13v1.5c0 2.5-2 4.5-4.5 4.5h-5C7 19 5 17 5 14.5Z"/><path d="M9 13h.01M15 13h.01M10 16h4"/>',
        'scale' => '<path d="M12 3v18"/><path d="M6 7h12"/><path d="m6 7-3 6h6Z"/><path d="m18 7-3 6h6Z"/><path d="M5 21h14"/>',
        'home' => '<path d="m3 10 9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
        'heart' => '<path d="M19.5 5.8a5 5 0 0 0-7.1 0L12 6.2l-.4-.4a5 5 0 0 0-7.1 7.1l.4.4L12 20.4l7.1-7.1.4-.4a5 5 0 0 0 0-7.1Z"/>',
        'calendar' => '<path d="M8 2v4M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/>',
        'female' => '<circle cx="12" cy="7" r="4"/><path d="M12 11v10M8 17h8"/>',
        'pregnancy' => '<path d="M9 4c4 1 7 4 7 9 0 4-2.5 7-6 7-2.8 0-5-2.2-5-5 0-2.3 1.5-4.2 3.6-4.8"/><path d="M14 13c-2 0-3.5 1.3-3.5 3"/>',
        'birth' => '<path d="M12 3c3 2 5 5 5 9a5 5 0 0 1-10 0c0-4 2-7 5-9Z"/><path d="M9 18h6M10 21h4"/>',
        'stethoscope' => '<path d="M6 3v5a4 4 0 0 0 8 0V3"/><path d="M6 3H4M14 3h2"/><path d="M10 12v3a4 4 0 0 0 8 0v-1"/><circle cx="18" cy="13" r="2"/>',
        'syringe' => '<path d="m18 2 4 4"/><path d="m17 7 2-2"/><path d="M6 21 3 18l10-10 3 3Z"/><path d="m8 16 3 3"/><path d="m14 6 4 4"/>',
        'baby' => '<circle cx="12" cy="8" r="4"/><path d="M6 21c.8-4 3-6 6-6s5.2 2 6 6"/><path d="M10 8h.01M14 8h.01"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
        'certificate' => '<path d="M15 2H6a2 2 0 0 0-2 2v16l4-2 4 2 4-2 4 2V7Z"/><path d="M15 2v5h5"/><path d="M8 9h5M8 13h8"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/>',
        'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'key' => '<circle cx="7.5" cy="14.5" r="3.5"/><path d="M11 14.5h10"/><path d="M17 14.5V18"/><path d="M20 14.5V17"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'filter' => '<path d="M3 5h18"/><path d="M6 12h12"/><path d="M10 19h4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.6-4 4.3-6 8-6s6.4 2 8 6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'moon' => '<path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.5 6.5 0 0 0 9.8 9.8Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h2v2h-2zM19 14h2v2h-2zM14 19h2v2h-2zM18 18h3v3h-3z"/><path d="M6 6h1M17 6h1M6 17h1"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $icons[$name] ?? $icons['dashboard'] !!}
</svg>
