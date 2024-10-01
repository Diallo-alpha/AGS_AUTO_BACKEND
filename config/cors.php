<?php

return [
    'paths' => ['api/*', 'login', 'logout', 'refresh'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://admirable-macaron-cbfcb1.netlify.app'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization'],
    'exposed_headers' => ['Authorization'],
    'max_age' => 0,
    'supports_credentials' => false,
];
