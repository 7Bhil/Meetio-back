<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout'],

    // AJOUTEZ l'origine Flutter (web) et votre IP
    'allowed_origins' => [
        'http://localhost:*',
        'http://127.0.0.1:*',
        'http://localhost:5173',    // Vite/React
        'http://localhost:3000',    // Next.js
        'http://localhost:5353',    // Flutter Web par défaut
        'http://localhost:42171',    // Flutter Web (port dynamique)
        'http://127.0.0.1:5353',    // Flutter Web
        'http://localhost',         // Générique
        'http://192.168.1.100',     // Votre IP
        'http://192.168.1.100:8000', // Laravel lui-même
    ],

    'allowed_origins_patterns' => [],

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
