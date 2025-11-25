<?php

return [
    // TrustPayments is deprecated - Axcess is now the primary payment provider
    // These config values are kept for backward compatibility only
    // Use config('axcess.*') for new integrations

    'test_username' => env('TRUST_PAYMENTS_TEST_USERNAME'),
    'test_password' => env('TRUST_PAYMENTS_TEST_PASSWORD'),
];
