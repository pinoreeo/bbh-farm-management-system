@props(['name', 'class' => 'h-5 w-5'])

@php
    $tablerAliases = [
        'dashboard' => 'layout-dashboard',
        'goat' => 'paw',
        'female' => 'gender-female',
        'pregnancy' => 'user-heart',
        'birth' => 'baby-carriage',
        'baby' => 'baby-carriage',
        'scale' => 'scale',
        'home' => 'building-warehouse',
        'history' => 'history',
        'calendar' => 'calendar',
        'stethoscope' => 'stethoscope',
        'syringe' => 'vaccine',
        'certificate' => 'certificate',
        'file' => 'file-description',
        'key' => 'key',
        'activity' => 'activity',
        'user' => 'users-group',
        'search' => 'search',
        'filter' => 'filter',
        'plus' => 'plus',
        'bell' => 'bell',
        'settings' => 'settings',
        'logout' => 'logout',
        'edit' => 'edit',
        'eye' => 'eye',
        'arrow-left' => 'arrow-left',
        'save' => 'device-floppy',
        'more' => 'dots',
        'menu' => 'menu-2',
        'check' => 'circle-check',
        'circle-x' => 'circle-x',
        'trash' => 'trash',
        'download' => 'download',
        'moon' => 'moon',
        'sun' => 'sun',
        'qr' => 'qrcode',
        'heart' => 'heart',
        'x' => 'x',
        'map-pin' => 'map-pin',
        'language' => 'language',
        'chevron-down' => 'chevron-down',
    ];

    $tablerIcon = $tablerAliases[$name] ?? 'layout-dashboard';
    $tablerPath = resource_path('icons/tabler/'.$tablerIcon.'.svg');
    $tablerSvg = is_file($tablerPath) ? file_get_contents($tablerPath) : '';
    $tablerPaths = preg_replace('/<\/?svg[^>]*>/', '', $tablerSvg);
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $tablerPaths !!}
</svg>
