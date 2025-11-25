<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrawEvent;
use Illuminate\Http\JsonResponse;

/**
 * Chain verification controller for public and authenticated endpoints.
 *
 * Provides tamper-proof verification of the event chain integrity,
 * allowing anyone to verify that the audit trail has not been modified.
 */
final class ChainVerificationController extends Controller
{
    /**
     * Verify the entire chain integrity (public, read-only).
     * Anyone can call this to verify the system's integrity.
     *
     * @return JsonResponse
     */
    public function verifyPublic(): JsonResponse
    {
        $results = DrawEvent::verifyChainIntegrity();

        return response()->json([
            'verified_at' => now()->toIso8601String(),
            'chain_status' => $results['is_valid'] ? 'valid' : 'invalid',
            'total_events' => $results['total_events'],
            'verified_events' => $results['verified_events'],
            'unchained_events' => $results['unchained_events'],
            'failed_events' => $results['failed_events'],
            'has_broken_links' => count($results['broken_links']) > 0,
            'has_invalid_hashes' => count($results['invalid_hashes']) > 0,
        ]);
    }

    /**
     * Verify chain integrity for a specific competition (public, read-only).
     *
     * @param  string  $competitionId  Competition UUID
     * @return JsonResponse
     */
    public function verifyCompetitionPublic(string $competitionId): JsonResponse
    {
        $results = DrawEvent::verifyChainIntegrity($competitionId);

        return response()->json([
            'verified_at' => now()->toIso8601String(),
            'competition_id' => $competitionId,
            'chain_status' => $results['is_valid'] ? 'valid' : 'invalid',
            'total_events' => $results['total_events'],
            'verified_events' => $results['verified_events'],
            'unchained_events' => $results['unchained_events'],
            'failed_events' => $results['failed_events'],
            'has_broken_links' => count($results['broken_links']) > 0,
            'has_invalid_hashes' => count($results['invalid_hashes']) > 0,
        ]);
    }

    /**
     * Verify the entire chain with detailed output (operator API, authenticated).
     *
     * @return JsonResponse
     */
    public function verifyOperator(): JsonResponse
    {
        $results = DrawEvent::verifyChainIntegrity();

        return response()->json([
            'verified_at' => now()->toIso8601String(),
            'chain_status' => $results['is_valid'] ? 'valid' : 'invalid',
            'summary' => [
                'total_events' => $results['total_events'],
                'verified_events' => $results['verified_events'],
                'unchained_events' => $results['unchained_events'],
                'failed_events' => $results['failed_events'],
            ],
            'broken_links' => $results['broken_links'],
            'invalid_hashes' => $results['invalid_hashes'],
        ]);
    }

    /**
     * Verify chain for a specific competition with detailed output (operator API, authenticated).
     *
     * @param  string  $competitionExternalId  Competition external ID
     * @return JsonResponse
     */
    public function verifyCompetitionOperator(string $competitionExternalId): JsonResponse
    {
        // Find competition by external_id
        $competition = \App\Models\Competition::where('external_id', $competitionExternalId)
            ->first();

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        $results = DrawEvent::verifyChainIntegrity($competition->id);

        return response()->json([
            'verified_at' => now()->toIso8601String(),
            'competition' => [
                'id' => $competition->id,
                'external_id' => $competition->external_id,
                'title' => $competition->title,
            ],
            'chain_status' => $results['is_valid'] ? 'valid' : 'invalid',
            'summary' => [
                'total_events' => $results['total_events'],
                'verified_events' => $results['verified_events'],
                'unchained_events' => $results['unchained_events'],
                'failed_events' => $results['failed_events'],
            ],
            'broken_links' => $results['broken_links'],
            'invalid_hashes' => $results['invalid_hashes'],
        ]);
    }

    /**
     * Verify the entire chain with administrative details (internal/regulator).
     *
     * @return JsonResponse
     */
    public function verifyRegulator(): JsonResponse
    {
        $results = DrawEvent::verifyChainIntegrity();

        // Get unchained events for monitoring
        $unchainedEvents = DrawEvent::unchained()
            ->orderBy('sequence')
            ->get(['id', 'sequence', 'event_type', 'competition_id', 'created_at']);

        return response()->json([
            'verified_at' => now()->toIso8601String(),
            'chain_status' => $results['is_valid'] ? 'valid' : 'invalid',
            'summary' => [
                'total_events' => $results['total_events'],
                'verified_events' => $results['verified_events'],
                'unchained_events' => $results['unchained_events'],
                'failed_events' => $results['failed_events'],
            ],
            'broken_links' => $results['broken_links'],
            'invalid_hashes' => $results['invalid_hashes'],
            'pending_chain_processing' => $unchainedEvents->map(fn ($e) => [
                'id' => $e->id,
                'sequence' => $e->sequence,
                'event_type' => $e->event_type,
                'competition_id' => $e->competition_id,
                'age_seconds' => now()->diffInSeconds($e->created_at),
            ]),
        ]);
    }

    /**
     * Verify chain for a specific competition (internal/regulator).
     *
     * @param  string  $competitionId  Competition UUID
     * @return JsonResponse
     */
    public function verifyCompetitionRegulator(string $competitionId): JsonResponse
    {
        $competition = \App\Models\Competition::find($competitionId);

        if (! $competition) {
            return response()->json([
                'error' => [
                    'code' => 'COMPETITION_NOT_FOUND',
                    'message' => 'Competition not found.',
                ],
            ], 404);
        }

        $results = DrawEvent::verifyChainIntegrity($competitionId);

        // Get unchained events for this competition
        $unchainedEvents = DrawEvent::unchained()
            ->where('competition_id', $competitionId)
            ->orderBy('sequence')
            ->get(['id', 'sequence', 'event_type', 'created_at']);

        return response()->json([
            'verified_at' => now()->toIso8601String(),
            'competition' => [
                'id' => $competition->id,
                'external_id' => $competition->external_id,
                'title' => $competition->title,
                'operator_id' => $competition->operator_id,
            ],
            'chain_status' => $results['is_valid'] ? 'valid' : 'invalid',
            'summary' => [
                'total_events' => $results['total_events'],
                'verified_events' => $results['verified_events'],
                'unchained_events' => $results['unchained_events'],
                'failed_events' => $results['failed_events'],
            ],
            'broken_links' => $results['broken_links'],
            'invalid_hashes' => $results['invalid_hashes'],
            'pending_chain_processing' => $unchainedEvents->map(fn ($e) => [
                'id' => $e->id,
                'sequence' => $e->sequence,
                'event_type' => $e->event_type,
                'age_seconds' => now()->diffInSeconds($e->created_at),
            ]),
        ]);
    }
}



