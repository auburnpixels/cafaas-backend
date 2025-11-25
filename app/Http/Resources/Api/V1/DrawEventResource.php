<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\DrawEvent $resource
 */
final class DrawEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'event_type' => $this->resource->event_type,
            'payload' => $this->resource->event_payload,
            'event_hash' => $this->resource->event_hash,
            'is_chained' => $this->resource->is_chained,
            'previous_event_hash' => $this->resource->previous_event_hash,
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
