<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operator\CloseCompetitionRequest;
use App\Http\Requests\Api\V1\Operator\PublishCompetitionRequest;
use App\Http\Requests\Api\V1\Operator\StoreCompetitionRequest;
use App\Http\Requests\Api\V1\Operator\UpdateCompetitionRequest;
use App\Http\Resources\Api\V1\CompetitionDetailResource;
use App\Http\Resources\Api\V1\CompetitionResource;
use App\Http\Resources\Api\V1\DrawAuditResource;
use App\Http\Resources\Api\V1\DrawEventResource;
use App\Http\Services\DrawEventService;
use App\Models\Competition;
use App\Models\Operator;
use App\Services\DrawOrchestrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * API Controller for operator competition management
 */
final class CompetitionController extends Controller
{
    public function __construct(
        private readonly DrawEventService $drawEventService,
        private readonly DrawOrchestrationService $drawOrchestrationService
    ) {}

    /**
     * Create a new competition.
     */
    public function store(StoreCompetitionRequest $request): JsonResponse
    {
        $operator = $request->operator;

        $validated = $request->validated();

        // Create competition and prizes in transaction
        $competition = \DB::transaction(function () use ($operator, $validated) {
            // Create competition
            $competition = Competition::create([
                'uuid' => Str::uuid(),
                'name' => $validated['name'],
                'operator_id' => $operator->id,
                'draw_at' => $validated['draw_at'],
                'external_id' => $validated['external_id'],
                'ticket_quantity' => $validated['max_tickets'],
                'status' => $validated['status'] ?? Competition::STATUS_ACTIVE,
            ]);

            // Create prizes
            foreach ($validated['prizes'] as $index => $prizeData) {
                $prize = $competition->prizes()->create([
                    'uuid' => Str::uuid(),
                    'draw_order' => $index + 1,
                    'name' => $prizeData['name'],
                    'external_id' => $prizeData['external_id'],
                ]);

                // Log prize creation
                $this->drawEventService->logPrizeCreated($competition, $prize, $operator);
            }

            return $competition;
        });

        // Log competition creation event
        $this->drawEventService->logCompetitionCreatedByOperator($competition, $operator);

        // Load prizes for response
        $competition->load('prizes');

        return (new CompetitionResource($competition))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get competition details.
     */
    public function show(Request $request, string $externalId): JsonResponse
    {
        $operator = $request->operator;

        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $externalId)
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

        // Load prizes for response
        $competition->load('prizes');

        return (new CompetitionResource($competition))
            ->response();
    }

    /**
     * Get competition statistics.
     */
    public function stats(Request $request, string $externalId): JsonResponse
    {
        $operator = $request->operator;

        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $externalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        $stats = $this->drawOrchestrationService->getDrawStatistics($competition);

        return response()->json([
            'total_entries' => $stats['total_entries'] ?? 0,
            'paid_entries' => $stats['paid_entries'] ?? 0,
            'free_entries' => $stats['free_entries'] ?? 0,
            'draws_completed' => $stats['draws_completed'] ?? 0,
        ]);
    }

    /**
     * Get competition audits.
     */
    public function audits(Request $request, string $externalId): JsonResponse
    {
        $operator = $request->operator;

        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $externalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        $audits = $competition->drawAudits()
            ->orderBy('drawn_at_utc', 'desc')
            ->get();

        return response()->json([
            'competition_id' => $externalId,
            'total_draws' => $audits->count(),
            'audits' => DrawAuditResource::collection($audits),
        ]);
    }

    /**
     * Get competition events.
     */
    public function events(Request $request, string $externalId): JsonResponse
    {
        $operator = $request->operator;

        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $externalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        $events = $competition->drawEvents()
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'competition_id' => $externalId,
            'total_events' => $events->count(),
            'events' => DrawEventResource::collection($events),
        ]);
    }

    /**
     * Close a competition (end entry period).
     */
    public function close(CloseCompetitionRequest $request, string $externalId): JsonResponse
    {
        $operator = $request->operator;

        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $externalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        // Check if competition can be closed
        if ($competition->status !== Competition::STATUS_ACTIVE) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'Only active competitions can be closed.',
                ],
            ], 422);
        }

        // Get total entries count
        $totalEntries = $competition->tickets()->count();

        // Update status
        $competition->update(['status' => Competition::STATUS_AWAITING_DRAW]);

        // Log event
        $this->drawEventService->logRaffleClosed($competition, $totalEntries);

        // Load prizes for response
        $competition->load('prizes');

        return (new CompetitionResource($competition))
            ->response();
    }

    /**
     * Update a competition.
     */
    public function update(UpdateCompetitionRequest $request, string $externalId): JsonResponse
    {
        $operator = $request->operator;

        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $externalId)
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

        if (!in_array($competition->status, [Competition::STATUS_ACTIVE])) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_ACTIVE',
                    'message' => 'You are only able to edit an active competition.',
                ],
            ], 422);
        }

        $validated = $request->validated();

        // Track changes for audit
        $changes = [];
        $updateData = [];

        if (isset($validated['external_id']) && $validated['external_id'] !== $competition->external_id) {
            $changes['external_id'] = ['old' => $competition->external_id, 'new' => $validated['external_id']];
            $updateData['external_id'] = $validated['external_id'];
        }

        if (isset($validated['name']) && $validated['name'] !== $competition->name) {
            $changes['name'] = ['old' => $competition->name, 'new' => $validated['name']];
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['max_tickets']) && $validated['max_tickets'] !== $competition->ticket_quantity) {
            $changes['ticket_quantity'] = ['old' => $competition->ticket_quantity, 'new' => $validated['max_tickets']];
            $updateData['ticket_quantity'] = $validated['max_tickets'];
        }

        if (isset($validated['draw_at']) && $validated['draw_at'] !== $competition->draw_at?->toIso8601String()) {
            $changes['draw_at'] = ['old' => $competition->draw_at?->toIso8601String(), 'new' => $validated['draw_at']];
            $updateData['draw_at'] = $validated['draw_at'];
        }

        if (isset($validated['status']) && $validated['status'] !== $competition->status) {
            $changes['status'] = ['old' => $competition->status, 'new' => $validated['status']];
            $updateData['status'] = $validated['status'];
        }

        // Handle prize updates if provided (only allowed if status is unpublished or active)
        $prizeChanges = [];
        if (isset($validated['prizes'])) {
            if (!in_array($competition->status, [Competition::STATUS_ACTIVE])) {
                return response()->json([
                    'error' => [
                        'code' => 'INVALID_STATUS_FOR_PRIZE_UPDATE',
                        'message' => 'Prizes can only be modified when competition status is "unpublished" or "active".',
                    ],
                ], 422);
            }

            // Check if any prizes to be deleted/updated have been drawn
            $existingPrizes = $competition->prizes->keyBy('external_id');
            $newPrizeIds = array_column($validated['prizes'], 'external_id');

            foreach ($existingPrizes as $externalPrizeId => $prize) {
                if (!in_array($externalPrizeId, $newPrizeIds) && $prize->hasBeenDrawn()) {
                    return response()->json([
                        'error' => [
                            'code' => 'CANNOT_DELETE_DRAWN_PRIZE',
                            'message' => "Prize '{$externalPrizeId}' has been drawn and cannot be deleted.",
                        ],
                    ], 422);
                }
            }

            // Sync prizes
            \DB::transaction(function () use ($competition, $validated, &$prizeChanges, $operator) {
                $newPrizes = collect($validated['prizes']);
                $existingPrizes = $competition->prizes->keyBy('external_id');

                // Track changes
                foreach ($newPrizes as $index => $prizeData) {
                    $externalPrizeId = $prizeData['external_id'];

                    if ($existingPrizes->has($externalPrizeId)) {
                        // Update existing prize
                        $prize = $existingPrizes->get($externalPrizeId);
                        $prizeUpdates = [];

                        if ($prize->name !== $prizeData['name']) {
                            $prizeUpdates['name'] = $prizeData['name'];
                            $prizeChanges[] = ['action' => 'updated', 'prize_id' => $externalPrizeId, 'name' => $prizeData['name']];
                        }

                        if ($prize->draw_order !== ($index + 1)) {
                            $prizeUpdates['draw_order'] = $index + 1;
                        }

                        if (!empty($prizeUpdates)) {
                            $prize->update($prizeUpdates);
                            $this->drawEventService->logPrizeUpdated($competition, $prize, $operator, $prizeUpdates);
                        }
                    } else {
                        // Create new prize
                        $prize = $competition->prizes()->create([
                            'uuid' => Str::uuid(),
                            'draw_order' => $index + 1,
                            'name' => $prizeData['name'],
                            'external_id' => $externalPrizeId,
                        ]);
                        $prizeChanges[] = ['action' => 'created', 'prize_id' => $externalPrizeId, 'name' => $prizeData['name']];
                        $this->drawEventService->logPrizeCreated($competition, $prize, $operator);
                    }
                }

                // Delete removed prizes
                $newPrizeIds = $newPrizes->pluck('external_id');
                foreach ($existingPrizes as $externalPrizeId => $prize) {
                    if (!$newPrizeIds->contains($externalPrizeId)) {
                        $prizeChanges[] = ['action' => 'deleted', 'prize_id' => $externalPrizeId];
                        $this->drawEventService->logPrizeDeleted($competition, $prize, $operator);
                        $prize->delete();
                    }
                }
            });

            if (!empty($prizeChanges)) {
                $changes['prizes'] = $prizeChanges;
            }
        }

        // Update competition
        if (!empty($updateData)) {
            $competition->update($updateData);

            // Log event
            $this->drawEventService->logRaffleUpdated($competition, $changes);
        }

        // Reload prizes for response
        $competition->load('prizes');

        return (new CompetitionResource($competition))
            ->response();
    }
}
