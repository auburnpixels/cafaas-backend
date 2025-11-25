<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Competition
 */
final class RaffleStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'totalEntries' => $this->tickets_bought ?? 0,
        ];
    }
}
