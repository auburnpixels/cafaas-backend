<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Services\DrawOrchestrationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Competition $resource
 */
final class CompetitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stats = app(DrawOrchestrationService::class)->getDrawStatistics($this->resource);

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->title,
            'status' => $this->resource->status,
            'external_id' => $this->resource->external_id,
            'max_tickets' => $this->resource->ticket_quantity,
            'draw_at' => $this->resource->draw_at?->format('Y-m-d\TH:i:s.u\Z'),
            'created_at' => $this->resource->created_at->format('Y-m-d\TH:i:s.u\Z'),
            'prizes' => PrizeResource::collection($this->whenLoaded('prizes')),
            'statistics' => $this->formatStatistics($stats),
        ];
    }

    /**
     * Format statistics to only include the required fields.
     *
     * @param array<string, mixed>|null $statistics
     * @return array<string, mixed>|null
     */
    private function formatStatistics(?array $statistics): ?array
    {
        if ($statistics === null) {
            return null;
        }

        return [
            'total_entries' => $statistics['total_entries'] ?? 0,
            'paid_entries' => $statistics['paid_entries'] ?? 0,
            'free_entries' => $statistics['free_entries'] ?? 0,
            'draws_completed' => $statistics['draws_completed'] ?? 0,
        ];
    }
}
