<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth', 'storage/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://nextgenlaptop.click',
        'https://www.nextgenlaptop.click',
        'https://api.nextgenlaptop.click',
        'http://localhost:5173',
        'http://localhost:8000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8000',
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.nextgenlaptop\.click$#',
        '#^http://(localhost|127\.0\.0\.1|192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+)(:\d+)?$#'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
