<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => (function (): array {
        $configured = trim((string) env('CORS_ALLOWED_ORIGINS', ''));
        if ($configured !== '') {
            return array_values(array_filter(array_map(
                static fn ($origin) => trim($origin),
                explode(',', $configured)
            )));
        }

        return array_values(array_filter([
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            trim((string) env('FRONTEND_URL', '')),
        ]));
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
