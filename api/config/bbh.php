<?php

return [
    'openssl_conf' => env('OPENSSL_CONF'),

    'admin' => [
        'name' => env('BBH_ADMIN_NAME', 'Super Admin'),
        'email' => env('BBH_ADMIN_EMAIL', 'superadmin@bbhfarm.domain'),
        'password' => env('BBH_ADMIN_PASSWORD'),
    ],

    'farm' => [
        'name' => env('BBH_FARM_NAME', 'Bumiku Bumimu Hijau Farm'),
        'address' => env('BBH_FARM_ADDRESS', 'Ajibarang'),
        'phone' => env('BBH_FARM_PHONE'),
        'email' => env('BBH_FARM_EMAIL', 'admin@bbhfarm.domain'),
    ],

    'public_web_url' => env('BBH_PUBLIC_WEB_URL'),
    'public_default_locale' => env('BBH_PUBLIC_DEFAULT_LOCALE', 'id-id'),

    'pdf' => [
        'browser_path' => env('BBH_PDF_BROWSER_PATH'),
    ],
];
