<?php

return [
    'paths' => ['api/*', 'login', 'logout', 'refresh'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://admirable-macaron-cbfcb1.netlify.app',
        'http://localhost:4200',
        'https://61df-154-125-148-217.ngrok-free.app',
        'https://3f6c-154-125-148-217.ngrok-free.app'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept'],
    'exposed_headers' => ['Authorization'],
    'max_age' => 0,
    'supports_credentials' => true,
];
