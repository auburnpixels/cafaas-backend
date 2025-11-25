<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operator\DestroyWebhookRequest;
use App\Http\Requests\Api\V1\Operator\ListWebhooksRequest;
use App\Http\Requests\Api\V1\Operator\StoreWebhookRequest;
use App\Http\Requests\Api\V1\Operator\UpdateWebhookRequest;
use App\Http\Resources\Api\V1\WebhookResource;
use App\Models\WebhookSubscription;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for operator webhook management
 */
final class WebhookController extends Controller
{
    /**
     * List webhook subscriptions.
     */
    public function index(ListWebhooksRequest $request): JsonResponse
    {
        $operator = $request->operator;

        $subscriptions = WebhookSubscription::where('user_id', $operator->id)->get();

        return WebhookResource::collection($subscriptions)
            ->response();
    }

    /**
     * Create a webhook subscription.
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $operator = $request->operator;

        $validated = $request->validated();

        // Validate URL is accessible
        if (! WebhookSubscription::validateUrl($validated['url'])) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_URL',
                    'message' => 'The webhook URL must be a valid HTTPS URL.',
                ],
            ], 400);
        }

        $subscription = WebhookSubscription::create([
            'user_id' => $operator->id,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return (new WebhookResource($subscription))
            ->additional(['secret' => $subscription->secret])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a webhook subscription.
     */
    public function update(UpdateWebhookRequest $request, int $id): JsonResponse
    {
        $operator = $request->operator;

        $subscription = WebhookSubscription::where('user_id', $operator->id)
            ->where('id', $id)
            ->first();

        if (! $subscription) {
            return response()->json([
                'error' => [
                    'code' => 'WEBHOOK_NOT_FOUND',
                    'message' => 'Webhook subscription not found.',
                ],
            ], 404);
        }

        $validated = $request->validated();

        if (isset($validated['url']) && ! WebhookSubscription::validateUrl($validated['url'])) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_URL',
                    'message' => 'The webhook URL must be a valid HTTPS URL.',
                ],
            ], 400);
        }

        $subscription->update($validated);

        return (new WebhookResource($subscription))->response();
    }

    /**
     * Delete a webhook subscription.
     */
    public function destroy(DestroyWebhookRequest $request, int $id): JsonResponse
    {
        $operator = $request->operator;

        $subscription = WebhookSubscription::where('user_id', $operator->id)
            ->where('id', $id)
            ->first();

        if (! $subscription) {
            return response()->json([
                'error' => [
                    'code' => 'WEBHOOK_NOT_FOUND',
                    'message' => 'Webhook subscription not found.',
                ],
            ], 404);
        }

        $subscription->delete();

        return response()->json([
            'message' => 'Webhook subscription deleted successfully.',
        ], 200);
    }
}
