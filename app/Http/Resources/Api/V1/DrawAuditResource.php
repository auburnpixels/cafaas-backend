<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\CompetitionDrawAudit $resource
 */
final class DrawAuditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'draw_id' => $this->resource->draw_id,
            'total_entries' => $this->resource->total_entries,
            'winner_entry_id' => $this->resource->selected_entry_id,
            'rng_seed_hash' => $this->resource->rng_seed_or_hash,
            'signature_hash' => $this->resource->signature_hash,
            'previous_signature_hash' => $this->resource->previous_signature_hash,
            'drawn_at' => $this->resource->drawn_at_utc->toIso8601String(),
        ];
    }
}
