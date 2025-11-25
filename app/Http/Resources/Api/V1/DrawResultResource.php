<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\CompetitionDrawAudit $resource
 * @property \App\Models\Ticket $winnerTicket
 */
final class DrawResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->draw_id,
            'audit_url' => $this->additional['audit_url'],
            'total_entries' => $this->resource->total_entries,
            'drawn_at' => $this->resource->drawn_at_utc->toIso8601String(),
            'competition' => [
                'id' => $this->additional['competition_external_id'],
                'external_id' => $this->additional['competition_external_id'],
            ],
            'winner' => [
                'entry_id' => $this->additional['winner_entry_id'],
                'ticket_number' => $this->additional['winner_ticket_number']
            ],
            'signature' => [
                'hash' => $this->resource->signature_hash,
                'previous_hash' => $this->resource->previous_signature_hash,
            ]
        ];
    }
}
