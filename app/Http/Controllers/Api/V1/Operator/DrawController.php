<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operator\RunDrawRequest;
use App\Http\Resources\Api\V1\DrawResource;
use App\Models\Competition;
use App\Models\DrawEvent;
use App\Services\DrawOrchestrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for operator draw management
 */
final class DrawController extends Controller
{
    public function __construct(
        private readonly DrawOrchestrationService $drawOrchestrationService
    ) {}

    /**
     * Run draw for all undrawn prizes in a competition.
     */
    public function runDraw(RunDrawRequest $request, string $competitionExternalId): JsonResponse
    {
        $operator = $request->operator;

        // Find competition
        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $competitionExternalId)
            ->with('prizes')
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        // Check if competition status is awaiting_draw
        if ($competition->status !== Competition::STATUS_AWAITING_DRAW) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'Competition must be in "awaiting_draw" status to run a draw. Current status: '.$competition->status,
                ],
            ], 422);
        }

        try {
            // Draw all undrawn prizes
            $results = $this->drawOrchestrationService->executeDrawsForAllPrizes($competition, $operator);

            if (empty($results)) {
                return response()->json([
                    'error' => [
                        'code' => 'NO_PRIZES_TO_DRAW',
                        'message' => 'No undrawn prizes found for this competition.',
                    ],
                ], 422);
            }

            // Check if all prizes have been drawn
            $remainingUndrawnPrizes = $competition->prizes()->undrawn()->count();

            // Update competition status to completed if all prizes are drawn
            if ($remainingUndrawnPrizes === 0) {
                $competition->update(['status' => Competition::STATUS_COMPLETED]);
            }

            // Add competition reference to each result for the resource
            $resultsWithCompetition = array_map(function ($result) use ($competition) {
                $result['competition'] = $competition;
                return $result;
            }, $results);

            return response()->json([
                'success' => true,
                'competition_id' => $competition->id,
                'competition_external_id' => $competitionExternalId,
                'competition_status' => $competition->status,
                'total_prizes_drawn' => count($results),
                'draws' => DrawResource::collection($resultsWithCompetition),
                'audit_url' => route('api.v1.raffles.audit', ['uuid' => $competition->id]),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DRAW_FAILED',
                    'message' => 'Draw execution failed: '.$e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get all draw events for the operator.
     */
    public function getDrawEvents(Request $request): JsonResponse
    {
        $operator = $request->operator;

        // Fetch all draw events for this operator, ordered by sequence descending (latest first)
        // This ensures the hash chain is visible (each event's hash should match the next event's previous_hash)
        $events = DrawEvent::where('operator_id', $operator->id)
            ->with('competition:id,uuid,name,external_id')
            ->orderBy('sequence', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'sequence' => $event->sequence,
                    'event_type' => $event->event_type,
                    'event_payload' => $event->event_payload,
                    'event_hash' => $event->event_hash,
                    'previous_event_hash' => $event->previous_event_hash,
                    'actor_type' => $event->actor_type,
                    'actor_id' => $event->actor_id,
                    'ip_address' => $event->ip_address,
                    'created_at' => $event->created_at->toIso8601String(),
                    'competition' => $event->competition ? [
                        'uuid' => $event->competition->uuid,
                        'name' => $event->competition->name,
                        'external_id' => $event->competition->external_id,
                    ] : null,
                ];
            }),
        ], 200);
    }
}
