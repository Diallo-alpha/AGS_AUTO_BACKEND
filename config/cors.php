<?php

return [
    'paths' => ['api/*', 'login', 'logout', 'refresh'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://admirable-macaron-cbfcb1.netlify.app',
        'http://localhost:4200',
        'https://d174-41-83-79-125.ngrok-free.app'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Authorization'],
    'max_age' => 0,
    'supports_credentials' => true,
];
