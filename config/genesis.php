<?php

return [
    'api_key' => env('GENESIS_API_KEY', ''),
    'base_url' => env('GENESIS_BASE_URL', 'https://api.genesis.com/v1/'),

    'cache' => [
        'enabled' => env('GENESIS_CACHE_ENABLED', true),
        'ttl' => env('GENESIS_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('GENESIS_CACHE_PREFIX', 'genesis:'),
    ],

    'queue' => [
        'connection' => env('GENESIS_QUEUE_CONNECTION', 'default'),
        'webhook_queue' => env('GENESIS_WEBHOOK_QUEUE', 'genesis-webhooks'),
        'sync_queue' => env('GENESIS_SYNC_QUEUE', 'genesis-sync'),
    ],

    'guide' => [
        // Один UUID или несколько через запятую для доступа к инструкции
        'uuid' => env('GENESIS_GUIDE_UUID', '7f3b9c4a-8e5d-4a2b-9c1e-3d7a5b9c4f8e'),
    ],
];


