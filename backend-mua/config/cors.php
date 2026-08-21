<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Backend ini di-consume oleh SPA terpisah (frontend-mua di Vercel) dan
    | memakai bearer token Sanctum, bukan cookie. Origin dibatasi ke domain
    | frontend yang sah lewat env `CORS_ALLOWED_ORIGINS` (dipisah koma).
    | Karena auth token-based, `supports_credentials` dibiarkan false.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:3000')),
    ))),

    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', '')),
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Payment-Access-Token'],

    'max_age' => 0,

    'supports_credentials' => false,

];
