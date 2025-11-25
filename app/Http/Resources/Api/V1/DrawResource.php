<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for individual draw result within a competition draw response
 */
final class DrawResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $audit = $this->resource['audit'];
        $prize = $this->resource['prize'];
        $winnerTicket = $this->resource['winner_ticket'];

        return [
            'id' => $winnerTicket->draw_id ?? null,
            'drawn_at' => $audit->drawn_at_utc->format('Y-m-d\TH:i:s.u\Z'),
            'total_entries' => $audit->total_entries,
            'signature_hash' => $audit->signature_hash,
            'prize' => [
                'id' => $prize->id,
                'external_id' => $prize->external_id,
                'title' => $prize->title,
            ],
            'winner' => [
                'entry' => [
                    'id' => $winnerTicket->id ?? null,
                    'external_id' => $winnerTicket->external_id ?? null,
                    'number' => $winnerTicket->number ?? null,
                ]
            ],
        ];
    }
}

