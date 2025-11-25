<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Services\DrawEventService;
use App\Models\Operator;
use App\Models\OperatorApiKey;
use Illuminate\Http\Request;

/**
 * @class OperatorAuthService
 *
 * Handles operator authentication and API key management
 */
final class OperatorAuthService
{
    public function __construct(
        private readonly DrawEventService $drawEventService
    ) {}

    /**
     * Validate an API key and return the operator.
     *
     * @param  string  $key  The API key from Bearer token
     */
    public function validateApiKey(string $key): ?Operator
    {
        // Extract the key hash
        $keyHash = hash('sha256', $key);

        // Find the API key record
        // Note: column is named 'key' in migration, but we store the hash there
        $apiKey = OperatorApiKey::with('operator')
            ->where('key', $keyHash)
            ->whereNull('revoked_at')
            ->first();

        if (! $apiKey) {
            return null;
        }

        // Check if operator is active
        if (! $apiKey->operator->is_active) {
            return null;
        }

        // Update last used timestamp
        $apiKey->recordUsage();

        return $apiKey->operator;
    }

    /**
     * Generate a new API key for an operator.
     *
     * @param  string  $name  Descriptive name for the key
     * @return array ['key' => string, 'api_key_id' => int]
     */
    public function generateApiKey(Operator $operator, string $name): array
    {
        $keyData = OperatorApiKey::generateKey();

        $apiKey = OperatorApiKey::create([
            'operator_id' => $operator->id,
            'key' => $keyData['hash'], // Store hash in 'key' column
            'name' => $name,
            'created_at' => now(),
        ]);

        return [
            'key' => $keyData['key'],
            'api_key_id' => $apiKey->id,
            'name' => $name,
            'created_at' => $apiKey->created_at->toIso8601String(),
        ];
    }

    /**
     * Rotate an API key (revoke old, generate new).
     *
     * @return array ['key' => string, 'api_key_id' => int]
     */
    public function rotateApiKey(OperatorApiKey $oldKey): array
    {
        // Revoke the old key
        $oldKey->revoke();

        // Generate new key with same name
        $newKeyData = $this->generateApiKey($oldKey->operator, $oldKey->name);

        // Log the rotation
        $this->drawEventService->logOperatorApiRequest(
            $oldKey->operator,
            'api_key_rotated',
            [
                'old_key_id' => $oldKey->id,
                'new_key_id' => $newKeyData['api_key_id'],
            ]
        );

        return $newKeyData;
    }

    /**
     * Revoke an API key.
     */
    public function revokeApiKey(OperatorApiKey $apiKey): bool
    {
        $apiKey->revoke();

        $this->drawEventService->logOperatorApiRequest(
            $apiKey->operator,
            'api_key_revoked',
            ['key_id' => $apiKey->id]
        );

        return true;
    }

    /**
     * Log an API call for rate limiting and monitoring.
     */
    public function logApiCall(Operator $operator, Request $request): void
    {
        $this->drawEventService->logOperatorApiRequest(
            $operator,
            $request->path(),
            [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );
    }

    /**
     * Get API key usage statistics for an operator.
     */
    public function getApiKeyUsageStats(Operator $operator): array
    {
        $apiKeys = $operator->apiKeys()->get();

        return [
            'total_keys' => $apiKeys->count(),
            'active_keys' => $apiKeys->whereNull('revoked_at')->count(),
            'revoked_keys' => $apiKeys->whereNotNull('revoked_at')->count(),
            'last_used' => $apiKeys->max('last_used_at'),
            'keys' => $apiKeys->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'masked_key' => $key->masked_key,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'revoked_at' => $key->revoked_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String(),
                ];
            }),
        ];
    }

    /**
     * Validate that an operator can perform an action.
     */
    public function canPerformAction(Operator $operator, string $action): bool
    {
        // Check if operator is active
        if (! $operator->is_active) {
            return false;
        }

        // Check operator-specific permissions from settings
        $permissions = $operator->getSetting('permissions', []);

        if (empty($permissions)) {
            // By default, active operators can perform all actions
            return true;
        }

        return in_array($action, $permissions);
    }
}
