<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Complaint $resource
 */
final class ComplaintResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'competition_id' => $this->when(
                isset($this->additional['competition_external_id']),
                $this->additional['competition_external_id'] ?? null
            ),
            'category' => $this->resource->category,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
