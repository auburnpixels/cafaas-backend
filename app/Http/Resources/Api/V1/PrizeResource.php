<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Prize $resource
 */
final class PrizeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasBeenDrawn = $this->resource->hasBeenDrawn();
        $audit = $hasBeenDrawn ? $this->resource->drawAudits()->first() : null;

        return [
            'id' => $this->resource->id,
            'external_id' => $this->resource->external_id,
            'title' => $this->resource->title,
            'winner' => [
                'entry' => [
                    'external_id' => $hasBeenDrawn && $audit ? ($audit->winningTicket?->external_id) : null
                ],
            ],
            'draw' => [
                'has_been_drawn' => $hasBeenDrawn,
                'order' =>$this->resource->draw_order,
                'drawn_at' => $hasBeenDrawn && $audit ? $audit->drawn_at_utc->format('Y-m-d\TH:i:s.u\Z') : null,
            ]
        ];
    }
}

