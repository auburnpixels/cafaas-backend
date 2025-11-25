<?php

declare(strict_types=1);

namespace App\Http\Services;

use App\Jobs\DeliverWebhook;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Service for dispatching webhooks to subscribers
 */
final class WebhookService
{
    /**
     * Dispatch a webhook event to all active subscribers.
     */
    public function dispatch(string $eventType, array $payload): void
    {
        if (! config('webhooks.enabled', true)) {
            return;
        }

        // Check if this event type is enabled
        $eventConfig = config("webhooks.events.{$eventType}", []);
        if (! ($eventConfig['enabled'] ?? false)) {
            Log::info("Webhook event {$eventType} is disabled, skipping dispatch");

            return;
        }

        // Find all active subscriptions for this event type
        $subscriptions = WebhookSubscription::active()
            ->forEvent($eventType)
            ->get();

        if ($subscriptions->isEmpty()) {
            Log::debug("No active subscriptions found for event: {$eventType}");

            return;
        }

        // Add event metadata to payload
        $payload['event'] = $eventType;
        $payload['timestamp'] = now()->toIso8601String();

        // Dispatch webhook delivery jobs for each subscription
        foreach ($subscriptions as $subscription) {
            DeliverWebhook::dispatch($subscription, $payload)
                ->onQueue(config('webhooks.queue.name', 'webhooks'));
        }

        Log::info("Dispatched {$eventType} webhook to {$subscriptions->count()} subscribers");
    }

    /**
     * Generate HMAC signature for webhook payload.
     */
    public function signPayload(string $payload, string $secret): string
    {
        $timestamp = time();
        $algorithm = config('webhooks.signature.algorithm', 'sha256');

        // Sign the payload with timestamp
        $signature = hash_hmac($algorithm, "{$timestamp}.{$payload}", $secret);

        // Format: t=timestamp, v1=signature
        return "t={$timestamp}, v1={$signature}";
    }

    /**
     * Verify a webhook signature.
     *
     * @param  int  $tolerance  Tolerance in seconds for timestamp verification
     */
    public function verifySignature(string $payload, string $signatureHeader, string $secret, int $tolerance = 300): bool
    {
        // Parse the signature header
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            $parts[$key] = $value;
        }

        if (! isset($parts['t']) || ! isset($parts['v1'])) {
            return false;
        }

        $timestamp = (int) $parts['t'];
        $receivedSignature = $parts['v1'];

        // Check timestamp tolerance to prevent replay attacks
        $now = time();
        if (abs($now - $timestamp) > $tolerance) {
            return false;
        }

        // Compute expected signature
        $algorithm = config('webhooks.signature.algorithm', 'sha256');
        $expectedSignature = hash_hmac($algorithm, "{$timestamp}.{$payload}", $secret);

        // Constant-time comparison
        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Create a test webhook subscription for development.
     */
    public function createSubscription(?int $userId, string $url, array $events): WebhookSubscription
    {
        return WebhookSubscription::create([
            'user_id' => $userId,
            'url' => $url,
            'events' => $events,
            'is_active' => true,
        ]);
    }
}
