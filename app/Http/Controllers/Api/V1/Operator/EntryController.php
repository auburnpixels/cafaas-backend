<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operator\DeleteEntryRequest;
use App\Http\Requests\Api\V1\Operator\StoreEntryRequest;
use App\Http\Requests\Api\V1\Operator\StoreFreeEntryRequest;
use App\Http\Resources\Api\V1\EntryResource;
use App\Http\Services\DrawEventService;
use App\Models\Checkout;
use App\Models\Competition;
use App\Models\FreeEntry;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for operator entry management
 */
final class EntryController extends Controller
{
    public function __construct(
        private readonly DrawEventService $drawEventService
    ) {}

    /**
     * Create a new paid entry.
     */
    public function store(StoreEntryRequest $request, string $competitionExternalId): JsonResponse
    {
        $operator = $request->operator;

        $validated = $request->validated();

        // Find competition
        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $competitionExternalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        // Check if external_id already exists
        if (Ticket::where('competition_id', $competition->id)
            ->where('external_id', $validated['external_id'])
            ->exists()) {
            return response()->json([
                'error' => [
                    'code' => 'DUPLICATE_ENTRY_ID',
                    'message' => 'An entry with this external_id already exists.',
                ],
            ], 409);
        }

        // Create ticket/entry
        $ticket = Ticket::create([
            'free' => false,
            'operator_id' => $operator->id,
            'competition_id' => $competition->id,
            'external_id' => $validated['external_id'],
            'number' => $competition->tickets()->count() + 1,
            'user_reference' => $validated['user_reference'] ?? null,
            'question_answered_correctly' => $validated['question_answered_correctly'],
        ]);

        // Log event
        $this->drawEventService->logOperatorEntryCreated($competition, $operator, [
            'is_free' => false,
            'external_id' => $validated['external_id'],
            'user_reference' => $validated['user_reference'],
        ]);

        return (new EntryResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Create a new free entry.
     */
    public function storeFreeEntry(StoreFreeEntryRequest $request, string $competitionExternalId): JsonResponse
    {
        $operator = $request->operator;

        $validated = $request->validated();

        // Find competition
        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $competitionExternalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        // Check if external_id already exists
        if (Ticket::where('competition_id', $competition->id)
            ->where('external_id', $validated['external_id'])
            ->exists()) {
            return response()->json([
                'error' => [
                    'code' => 'DUPLICATE_ENTRY_ID',
                    'message' => 'An entry with this external_id already exists.',
                ],
            ], 409);
        }

        // Create free ticket/entry
        $ticket = Ticket::create([
            'free' => true,
            'ticket_price' => 0,
            'operator_id' => $operator->id,
            'competition_id' => $competition->id,
            'number' => $competition->tickets()->count() + 1,
            'external_id' => $validated['external_id'],
            'question_answered_correctly' => $validated['question_answered_correctly'],
        ]);

        // Create free entry record for tracking
        FreeEntry::create([
            'submitted_by' => 'operator',
            'operator_id' => $operator->id,
            'competition_id' => $competition->id,
            'reason' => $validated['reason'] ?? 'postal',
            'user_reference' => $validated['user_reference'],
        ]);

        // Log event
        $this->drawEventService->logOperatorEntryCreated($competition, $operator, [
            'is_free' => true,
            'user_reference' => $validated['user_reference'],
            'external_id' => $validated['external_id'],
        ]);

        return (new EntryResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete an entry.
     */
    public function destroy(DeleteEntryRequest $request, string $competitionExternalId, string $entryExternalId): JsonResponse
    {
        $operator = $request->operator;

        // Find competition
        $competition = Competition::where('operator_id', $operator->id)
            ->where('external_id', $competitionExternalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        // Find ticket/entry
        $ticket = Ticket::where('competition_id', $competition->id)
            ->where('external_id', $entryExternalId)
            ->first();

        if (! $ticket) {
            return response()->json([
                'error' => [
                    'code' => 'ENTRY_NOT_FOUND',
                    'message' => 'Entry not found.',
                ],
            ], 404);
        }

        // Check if entry was already drawn as a winner
        $hasWon = \DB::table('draw_audits')
            ->where('competition_id', $competition->id)
            ->where('selected_entry_id', $ticket->external_id)
            ->exists();

        if ($hasWon) {
            return response()->json([
                'error' => [
                    'code' => 'ENTRY_IS_WINNER',
                    'message' => 'Cannot delete an entry that has been drawn as a winner.',
                ],
            ], 422);
        }

        $validated = $request->validated();

        // Build reason string
        $reason = $validated['reason'];
        if (isset($validated['notes'])) {
            $reason .= ': '.$validated['notes'];
        }

        // Log event before deletion
        $this->drawEventService->logEntryDeleted($ticket, $reason);

        // Delete the ticket/entry
        $ticket->delete();

        return response()->json([
            'message' => 'Entry deleted successfully.',
            'reason' => $validated['reason'],
            'competition' => [
                'id' => $competition->id,
                'external_id' => $competition->external_id,
            ],
            'entry' => [
                'id' => $ticket->id,
                'external_id' => $entryExternalId,
            ],
        ]);
    }
}
