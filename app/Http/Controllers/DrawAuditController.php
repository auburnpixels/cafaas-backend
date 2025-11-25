<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompetitionDrawAudit;
use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @class DrawAuditController
 */
final class DrawAuditController extends Controller
{
    /**
     * Display all draw audits as JSON.
     */
    public function indexJson(): JsonResponse
    {
        $audits = CompetitionDrawAudit::with([
            'competition:id,name,slug',
            'prize:id,name',
            'winningTicket:id,number',
        ])
            ->orderBy('id', 'desc')
            ->paginate(50);

        // Transform to explicitly include chain data
        $data = collect($audits->items())->map(function ($audit) {
            return [
                'id' => $audit->id,
                'draw_id' => $audit->draw_id,
                'competition' => $audit->competition ? [
                    'id' => $audit->competition->id,
                    'name' => $audit->competition->name,
                    'slug' => $audit->competition->slug,
                ] : null,
                'prize_name' => $audit->prize?->name,
                'drawn_at_utc' => $audit->drawn_at_utc->toIso8601String(),
                'total_entries' => $audit->total_entries,
                'rng_seed_hash' => $audit->rng_seed_or_hash,
                'winner_entry_id' => $audit->winningTicket?->number,
                'signature_hash' => $audit->signature_hash,
                'previous_signature_hash' => $audit->previous_signature_hash,
                'created_at' => $audit->created_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
            ],
        ]);
    }

    /**
     * Display all draw audits in a Blade view.
     */
    public function index(): View
    {
        $audits = CompetitionDrawAudit::with([
            'competition:id,name,slug,uuid',
            'prize:id,competition_id,name',
            'winningTicket:id,number',
        ])
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('draws-audit.index', [
            'audits' => $audits,
        ]);
    }

    /**
     * Display draw audits for a specific competition by UUID.
     */
    public function show(string $uuid): View
    {
        $audits = CompetitionDrawAudit::query()
            ->whereHas('competition', function ($query) use ($uuid) {
                $query->where('uuid', $uuid);
            })
            ->with([
                'competition:id,name,slug,uuid',
                'prize:id,competition_id,name',
                'winningTicket:id,number',
            ])
            ->orderBy('id', 'desc')
            ->paginate(25);

        // Get competition info for the page name (may be null if competition doesn't exist in Raffaly)
        $competition = $audits->first()?->competition;

        return view('draws-audit.show', [
            'audits' => $audits,
            'competition' => $competition,
            'uuid' => $uuid,
        ]);
    }

    /**
     * Display draw audits for a specific competition as JSON.
     */
    public function showJson(string $uuid): JsonResponse
    {
        $audits = CompetitionDrawAudit::query()
            ->whereHas('competition', function ($query) use ($uuid) {
                $query->where('uuid', $uuid);
            })
            ->with([
                'competition:id,name,slug,uuid',
                'prize:id,competition_id,name',
                'winningTicket:id,number',
            ])
            ->orderBy('id', 'desc')
            ->paginate(50);

        // Transform to explicitly include chain data
        $data = collect($audits->items())->map(function ($audit) {
            return [
                'id' => $audit->id,
                'draw_id' => $audit->draw_id,
                'competition' => $audit->competition ? [
                    'id' => $audit->competition->id,
                    'name' => $audit->competition->name,
                    'slug' => $audit->competition->slug,
                    'uuid' => $audit->competition->uuid,
                ] : null,
                'prize_name' => $audit->prize?->name,
                'drawn_at_utc' => $audit->drawn_at_utc->toIso8601String(),
                'total_entries' => $audit->total_entries,
                'rng_seed_hash' => $audit->rng_seed_or_hash,
                'winner_entry_id' => $audit->winningTicket?->number,
                'signature_hash' => $audit->signature_hash,
                'previous_signature_hash' => $audit->previous_signature_hash,
                'created_at' => $audit->created_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'competition_id' => $uuid,
            ],
        ]);
    }

    /**
     * Display all draw audits on public API with optional operator filter.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = CompetitionDrawAudit::with([
            'competition:id,uuid,name,slug,operator_id',
            'competition.operator:id,uuid,name,slug,url',
            'prize:id,name',
            'winningTicket:id,number',
        ]);

        // Filter by operator if provided (UUID or slug)
        if ($request->has('operator')) {
            $operatorIdentifier = $request->input('operator');
            $query->whereHas('competition.operator', function ($q) use ($operatorIdentifier) {
                $q->where('uuid', $operatorIdentifier)
                    ->orWhere('slug', $operatorIdentifier);
            });
        }

        $audits = $query->orderBy('drawn_at_utc', 'desc')
            ->paginate(50);

        // Transform to include all public data
        $data = collect($audits->items())->map(function ($audit) {
            return [
                'id' => $audit->id,
                'draw_id' => $audit->draw_id,
                'operator' => $audit->competition?->operator ? [
                    'uuid' => $audit->competition->operator->uuid,
                    'name' => $audit->competition->operator->name,
                    'slug' => $audit->competition->operator->slug,
                    'url' => $audit->competition->operator->url,
                ] : null,
                'competition' => $audit->competition ? [
                    'uuid' => $audit->competition->uuid,
                    'name' => $audit->competition->name,
                    'slug' => $audit->competition->slug,
                ] : null,
                'prize_name' => $audit->prize?->name,
                'drawn_at_utc' => $audit->drawn_at_utc->toIso8601String(),
                'total_entries' => $audit->total_entries,
                'winning_ticket' => $audit->winningTicket?->number,
                'pool_hash' => $audit->pool_hash,
                'signature_hash' => $audit->signature_hash,
                'previous_signature_hash' => $audit->previous_signature_hash,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
            ],
        ]);
    }

    /**
     * Download all draw audits as JSON.
     */
    public function downloadJson(Request $request): JsonResponse
    {
        $query = CompetitionDrawAudit::with([
            'competition:id,uuid,name,slug,operator_id',
            'competition.operator:id,uuid,name,slug,url',
            'prize:id,name',
            'winningTicket:id,number',
        ]);

        // Filter by operator if provided
        if ($request->has('operator')) {
            $operatorIdentifier = $request->input('operator');
            $query->whereHas('competition.operator', function ($q) use ($operatorIdentifier) {
                $q->where('uuid', $operatorIdentifier)
                    ->orWhere('slug', $operatorIdentifier);
            });
        }

        $audits = $query->orderBy('drawn_at_utc', 'desc')->get();

        // Transform to include all public data
        $data = $audits->map(function ($audit) {
            return [
                'id' => $audit->id,
                'draw_id' => $audit->draw_id,
                'operator' => $audit->competition?->operator ? [
                    'uuid' => $audit->competition->operator->uuid,
                    'name' => $audit->competition->operator->name,
                    'slug' => $audit->competition->operator->slug,
                    'url' => $audit->competition->operator->url,
                ] : null,
                'competition' => $audit->competition ? [
                    'uuid' => $audit->competition->uuid,
                    'name' => $audit->competition->name,
                    'slug' => $audit->competition->slug,
                ] : null,
                'prize_name' => $audit->prize?->name,
                'drawn_at_utc' => $audit->drawn_at_utc->toIso8601String(),
                'total_entries' => $audit->total_entries,
                'winning_ticket' => $audit->winningTicket?->number,
                'pool_hash' => $audit->pool_hash,
                'signature_hash' => $audit->signature_hash,
                'previous_signature_hash' => $audit->previous_signature_hash,
            ];
        });

        return response()->json([
            'data' => $data,
            'exported_at' => now()->toIso8601String(),
            'total_records' => $data->count(),
        ]);
    }

    /**
     * Get list of all operators for filtering.
     */
    public function getOperators(): JsonResponse
    {
        $operators = Operator::select('id', 'uuid', 'name', 'slug', 'url')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $operators,
        ]);
    }
}
