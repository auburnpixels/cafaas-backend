<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Current API version for the Raffaly public API
    |
    */

    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | Enable sandbox mode for testing and development. When enabled,
    | deterministic mock data will be returned for API requests.
    |
    */

    'sandbox' => env('API_SANDBOX', false),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for API endpoints per minute.
    |
    */

    'rate_limits' => [
        'public' => env('API_RATE_LIMIT_PUBLIC', 60),
        'authenticated' => env('API_RATE_LIMIT_AUTHENTICATED', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Headers
    |--------------------------------------------------------------------------
    |
    | Headers to include in all API responses
    |
    */

    'headers' => [
        'X-API-Version' => 'v1',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache TTL for various endpoints in seconds
    |
    */

    'cache' => [
        'audit' => env('API_CACHE_AUDIT', 3600), // 1 hour
        'entries_stats' => env('API_CACHE_ENTRIES', 60), // 1 minute
        'odds' => env('API_CACHE_ODDS', 60), // 1 minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Codes
    |--------------------------------------------------------------------------
    |
    | Standard error codes returned by the API
    |
    */

    'error_codes' => [
        'NOT_FOUND' => 'Resource not found',
        'UNAUTHORIZED' => 'Authentication required',
        'FORBIDDEN' => 'Access denied',
        'RATE_LIMITED' => 'Too many requests',
        'INVALID_PARAMETER' => 'Invalid parameter provided',
        'INTEGRITY_CHECK_FAILED' => 'Data integrity verification failed',
        'SERVER_ERROR' => 'Internal server error',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sandbox Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for sandbox environment
    |
    */

    'sandbox_config' => [
        'base_url' => env('SANDBOX_API_URL', 'https://sandbox.api.raffaly.com'),
        'banner_message' => 'SANDBOX MODE - No real data',
        'fixed_raffle_ids' => [
            'raf_sandbox_001',
            'raf_sandbox_002',
            'raf_sandbox_003',
        ],
    ],
];
