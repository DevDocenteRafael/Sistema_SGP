<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | O front-end (Front_SIPED) roda em origem separada (Vite, porta 5173).
    | A API aceita apenas essas origens; autenticação é via token Sanctum
    | (Authorization: Bearer), sem cookies, por isso supports_credentials
    | fica false.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Produção: CORS_ALLOWED_ORIGINS=https://siped.df.senac.br (separe várias por vírgula).
    // Se o front for servido pelo próprio Laravel (public/build/siped), não há chamada entre origens.
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'CORS_ALLOWED_ORIGINS',
        'http://127.0.0.1:5173,http://localhost:5173'
    ))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
