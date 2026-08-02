<?php

return [
    'private_key_path' => env('BBH_SIGNING_PRIVATE_KEY_PATH', storage_path('app/keys/bbh_private.pem')),
    'public_key_path' => env('BBH_SIGNING_PUBLIC_KEY_PATH', storage_path('app/keys/bbh_public.pem')),
    'private_key_passphrase' => env('BBH_SIGNING_PRIVATE_KEY_PASSPHRASE'),
    'signature_scheme' => env('BBH_SIGNATURE_SCHEME', 'RSA-SHA256'),
];
