<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable webhook delivery globally
    |
    */

    'enabled' => env('WEBHOOKS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Delivery Settings
    |--------------------------------------------------------------------------
    |
    | Configure webhook delivery behavior
    |
    */

    'delivery' => [
        'timeout' => env('WEBHOOK_TIMEOUT', 10), // seconds
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 0), // 0 for MVP (fire-and-forget)
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 60), // seconds between retries
        'max_failures' => env('WEBHOOK_MAX_FAILURES', 10), // auto-disable after this many failures
    ],

    /*
    |--------------------------------------------------------------------------
    | Signature Settings
    |--------------------------------------------------------------------------
    |
    | HMAC signature configuration for webhook security
    |
    */

    'signature' => [
        'algorithm' => 'sha256',
        'header_name' => 'X-Raffaly-Signature',
        'format' => 't={timestamp}, v1={signature}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Types
    |--------------------------------------------------------------------------
    |
    | Available webhook event types that can be subscribed to
    |
    */

    'events' => [
        'draw.completed' => [
            'description' => 'Fired when a raffle draw is completed',
            'enabled' => true,
        ],
        'audit.published' => [
            'description' => 'Fired when a draw audit record is published',
            'enabled' => true,
        ],
        'raffle.created' => [
            'description' => 'Fired when a raffle is created',
            'enabled' => false, // Not in MVP
        ],
        'raffle.published' => [
            'description' => 'Fired when a raffle goes live',
            'enabled' => false, // Not in MVP
        ],
        'entry.created' => [
            'description' => 'Fired when a new entry is created',
            'enabled' => false, // Not in MVP
        ],
        'complaint.submitted' => [
            'description' => 'Fired when a complaint is submitted',
            'enabled' => false, // Not in MVP
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for webhook delivery jobs
    |
    */

    'queue' => [
        'connection' => env('WEBHOOK_QUEUE_CONNECTION', 'redis'),
        'name' => env('WEBHOOK_QUEUE_NAME', 'webhooks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of webhook deliveries
    |
    */

    'logging' => [
        'enabled' => env('WEBHOOK_LOGGING_ENABLED', true),
        'channel' => env('WEBHOOK_LOG_CHANNEL', 'stack'),
        'log_payloads' => env('WEBHOOK_LOG_PAYLOADS', false), // Log full payloads (can be verbose)
    ],
];
