<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RaffleStatsResource;
use App\Models\Competition;
use Illuminate\Http\JsonResponse;

/**
 * @class RaffleStatsController
 */
final class RaffleStatsController extends Controller
{
    /**
     * Get raffle statistics including total entries.
     */
    public function show(string $raffle): JsonResponse
    {
        // Handle both numeric ID and UUID
        $competition = is_numeric($raffle)
            ? Competition::findOrFail($raffle)
            : Competition::where('uuid', $raffle)->firstOrFail();

        return (new RaffleStatsResource($competition))->response();
    }
}
