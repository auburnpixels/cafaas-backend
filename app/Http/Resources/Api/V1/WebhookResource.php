<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\WebhookSubscription $resource
 */
final class WebhookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'url' => $this->resource->url,
            'events' => $this->resource->events,
            'is_active' => $this->resource->is_active,
            'failure_count' => $this->when(
                ! $this->additional['hide_failure_count'] ?? false,
                $this->resource->failure_count
            ),
            'secret' => $this->when(
                $this->additional['include_secret'] ?? false,
                $this->resource->secret
            ),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->when(
                isset($this->resource->updated_at),
                $this->resource->updated_at?->toIso8601String()
            ),
        ];
    }
}
