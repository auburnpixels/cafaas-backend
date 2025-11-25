<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Operator\CreateApiKeyRequest;
use App\Http\Requests\Internal\Operator\UpdateDetailsRequest;
use App\Http\Resources\Internal\Operator\ApiKeyResource;
use App\Http\Resources\Internal\Operator\DashboardResource;
use App\Models\Competition;
use App\Models\Complaint;
use App\Models\DrawAudit;
use App\Models\DrawEvent;
use App\Models\Operator;
use App\Models\OperatorApiKey;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly ComplianceService $complianceService
    ) {}

    /**
     * Get operator details and dashboard data
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $operator = Operator::find($user->operator_id);

        if (! $operator) {
            return response()->json(['message' => 'Operator not found.'], 404);
        }

        $compliance = $this->complianceService->getCompetitionCompliance($operator);

        // Get recent competitions (last 5)
        $recentCompetitions = Competition::where('operator_id', $user->operator_id)
            ->with('prizes')
            ->withCount(['tickets', 'prizes', 'complaints'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get complaint stats
        $totalComplaints = Complaint::where('operator_id', $user->operator_id)->count();
        $pendingComplaints = Complaint::where('operator_id', $user->operator_id)
            ->where('status', 'pending')
            ->count();

        return (new DashboardResource([
            'user' => $user,
            'operator' => $operator,
            'compliance' => $compliance,
            'recent_competitions' => $recentCompetitions,
            'stats' => [
                'total_complaints' => $totalComplaints,
                'pending_complaints' => $pendingComplaints,
            ],
        ]))->response();
    }

    /**
     * Update operator details
     */
    public function updateDetails(UpdateDetailsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $operator = Operator::find($user->operator_id);

        if (! $operator) {
            return response()->json(['message' => 'Operator not found.'], 404);
        }

        $validated = $request->validated();

        // Update operator details
        $operator->name = $validated['name'];
        $operator->url = $validated['url'] ?? null;
        $operator->save();

        return response()->json([
            'message' => 'Operator details updated successfully.',
            'operator' => [
                'id' => $operator->id,
                'name' => $operator->name,
                'url' => $operator->url,
                'is_active' => $operator->is_active,
            ],
        ], 200);
    }

    /**
     * Get all competitions for the operator
     */
    public function competitions(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $query = Competition::where('operator_id', $user->operator_id)
            ->with('prizes')
            ->withCount(['tickets', 'drawAudits', 'drawEvents', 'prizes', 'complaints']);

        // Filter by external ID
        if ($request->filled('external_id')) {
            $query->where('external_id', 'LIKE', '%' . $request->input('external_id') . '%');
        }

        // Filter by name/title
        if ($request->filled('name')) {
            $query->where('title', 'LIKE', '%' . $request->input('name') . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->input('per_page', 20);
        $competitions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Enrich each competition with computed fields
        $enrichedData = $competitions->getCollection()->map(function ($competition) {
            // Get compliance detail for this competition
            $compliance = $this->complianceService->getCompetitionComplianceDetail($competition);

            // Check if draw is overdue
            $isDrawOverdue = false;
            if ($competition->draw_at && $competition->draw_at->isPast() && $competition->draw_audits_count === 0) {
                $isDrawOverdue = true;
            }

            return array_merge($competition->toArray(), [
                'entries_count' => $competition->tickets_count,
                'complaints_count' => $competition->complaints_count,
                'compliance_status' => $compliance['compliance_status'] ?? 'good',
                'compliance_percentage' => $compliance['compliance_percentage'] ?? 100,
                'compliance_checks' => $compliance['compliance_checks'] ?? [],
                'is_draw_overdue' => $isDrawOverdue,
                'draw_events_count' => $competition->draw_events_count,
            ]);
        });

        return response()->json([
            'data' => $enrichedData,
            'current_page' => $competitions->currentPage(),
            'per_page' => $competitions->perPage(),
            'total' => $competitions->total(),
            'last_page' => $competitions->lastPage(),
            'from' => $competitions->firstItem(),
            'to' => $competitions->lastItem(),
        ]);
    }

    /**
     * Get competition audits
     */
    public function competitionAudits(Request $request, string $uuid): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        // UUID is now the primary key 'id'
        $competition = Competition::where('id', $uuid)
            ->where('operator_id', $user->operator_id)
            ->firstOrFail();

        $audits = $competition->drawAudits()
            ->with(['prize:id,name', 'winningTicket:id,external_id,number'])
            ->orderBy('drawn_at_utc', 'desc')
            ->paginate(10);

        return response()->json($audits);
    }

    /**
     * Get competition events
     */
    public function competitionEvents(Request $request, string $uuid): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        // UUID is now the primary key 'id'
        $competition = Competition::where('id', $uuid)
            ->where('operator_id', $user->operator_id)
            ->firstOrFail();

        $events = $competition->drawEvents()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($events);
    }

    /**
     * Get compliance summary for the operator
     */
    public function complianceSummary(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $operator = Operator::findOrFail($user->operator_id);
        $summary = $this->complianceService->generateOperatorComplianceDashboard($operator);

        return response()->json($summary);
    }

    /**
     * Get all API keys for the operator
     */
    public function apiKeys(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $apiKeys = OperatorApiKey::where('operator_id', $user->operator_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiKeyResource::collection($apiKeys)
            ->response();
    }

    /**
     * Create a new API key for the operator
     */
    public function createApiKey(CreateApiKeyRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $validated = $request->validated();

        // Generate key and hash
        // Prefix removed as per request
        $key = Str::random(64);
        $hash = hash('sha256', $key);
        $keyData = ['key' => $key, 'hash' => $hash];

        $apiKey = OperatorApiKey::create([
            'operator_id' => $user->operator_id,
            'name' => $validated['name'],
            'key' => $keyData['hash'], // Store hash in 'key' column
            'secret' => $keyData['key'], // Store encrypted plain text key
        ]);

        // Temporarily set the plain text key on the model so the resource can include it
        // This is only done for the response and is not persisted as 'key' column doesn't exist (it's masked usually)
        // The resource will prioritize 'secret' if available, or fallback to this if we override
        $apiKey->key = $keyData['key'];

        return (new ApiKeyResource($apiKey))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Revoke an API key
     */
    public function revokeApiKey(Request $request, int $keyId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        // Use query without global scopes if you had any, or simply find by ID and operator
        $apiKey = OperatorApiKey::where('id', $keyId)
            ->where('operator_id', $user->operator_id)
            ->firstOrFail();

        $apiKey->revoke(); // Use the model method

        return response()->json(['message' => 'API key revoked successfully']);
    }

    /**
     * Get all draw events for the operator with pagination and filtering
     */
    public function drawEvents(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $perPage = min((int) $request->input('per_page', 25), 250);

        $query = DrawEvent::where('operator_id', $user->operator_id)
            ->with('competition:id,title,external_id');

        // Apply filters
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->filled('competition_id')) {
            $competitionIdentifier = $request->input('competition_id');
            // Since competition IDs are now UUIDs, use them directly
            $query->where('competition_id', $competitionIdentifier);
        }

        if ($request->filled('actor_type')) {
            $query->where('actor_type', $request->input('actor_type'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Order by sequence descending (latest first)
        $query->orderBy('sequence', 'desc');

        $result = $query->paginate($perPage);

        return response()->json([
            'data' => collect($result->items())->map(function ($event) {
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
                    'is_chained' => $event->is_chained,
                    'competition' => $event->competition ? [
                        'id' => $event->competition->id,
                        'title' => $event->competition->title,
                        'external_id' => $event->competition->external_id,
                    ] : null,
                ];
            })->values()->toArray(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
        ], 200);
    }

    /**
     * Get filter options for draw events
     */
    public function drawEventsFilters(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        // Get unique event types
        $eventTypes = DrawEvent::where('operator_id', $user->operator_id)
            ->distinct()
            ->pluck('event_type')
            ->values();

        // Get competitions
        $competitions = Competition::where('operator_id', $user->operator_id)
            ->select('id', 'title', 'external_id')
            ->orderBy('title')
            ->get();

        return response()->json([
            'event_types' => $eventTypes,
            'competitions' => $competitions,
            'actor_types' => ['operator', 'system', 'user', 'admin'],
        ], 200);
    }

    /**
     * Get all complaints for the operator
     */
    public function complaints(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $query = Complaint::where('operator_id', $user->operator_id)
            ->with('competition:id,title,external_id');

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by competition if provided
        if ($request->filled('competition')) {
            $query->where('competition_id', $request->input('competition'));
        }

        $complaints = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // Transform the data for the frontend
        $data = $complaints->getCollection()->map(function ($complaint) {
            return [
                'id' => $complaint->id,
                'competition' => $complaint->competition ? $complaint->competition->title : null,
                'competition_id' => $complaint->competition ? $complaint->competition->id : null,
                'external_id' => $complaint->competition ? $complaint->competition->external_id : null,
                'reporter_name' => $complaint->name ?? 'Anonymous',
                'reporter_email' => $complaint->email,
                'category' => $complaint->category,
                'message' => $complaint->message,
                'admin_notes' => $complaint->admin_notes,
                'status' => $complaint->status,
                'created_at' => $complaint->created_at->toIso8601String(),
                'updated_at' => $complaint->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $complaints->currentPage(),
            'per_page' => $complaints->perPage(),
            'total' => $complaints->total(),
            'last_page' => $complaints->lastPage(),
            'from' => $complaints->firstItem(),
            'to' => $complaints->lastItem(),
        ]);
    }

    /**
     * Get all draw audits for the operator
     */
    public function drawAudits(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user->isOperator() || ! $user->operator_id) {
            return response()->json(['message' => 'Unauthorized: Not an operator user.'], 403);
        }

        $query = DrawAudit::where('operator_id', $user->operator_id)
            ->with([
                'competition:id,title,external_id',
                'prize:id,title',
                'winningTicket:id,external_id,number'
            ]);

        // Filter by competition if provided
        if ($request->filled('competition')) {
            $query->where('competition_id', $request->input('competition'));
        }

        // Filter by date range if provided
        if ($request->filled('from_date')) {
            $query->whereDate('drawn_at_utc', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('drawn_at_utc', '<=', $request->input('to_date'));
        }

        $audits = $query->orderBy('drawn_at_utc', 'desc')
            ->paginate(20);

        // Transform the data for the frontend
        $data = $audits->getCollection()->map(function ($audit) {
            return [
                'id' => $audit->id,
                'sequence' => $audit->sequence,
                'competition' => $audit->competition ? [
                    'id' => $audit->competition->id,
                    'title' => $audit->competition->title,
                    'external_id' => $audit->competition->external_id,
                ] : null,
                'prize' => $audit->prize ? [
                    'id' => $audit->prize->id,
                    'title' => $audit->prize->title,
                ] : null,
                'draw_id' => $audit->draw_id,
                'drawn_at_utc' => $audit->drawn_at_utc->toIso8601String(),
                'total_entries' => $audit->total_entries,
                'selected_entry' => $audit->winningTicket ? [
                    'id' => $audit->winningTicket->id,
                    'external_id' => $audit->winningTicket->external_id,
                    'number' => $audit->winningTicket->number,
                ] : null,
                'signature_hash' => $audit->signature_hash,
                'previous_signature_hash' => $audit->previous_signature_hash,
                'pool_hash' => $audit->pool_hash,
                'rng_seed_or_hash' => $audit->rng_seed_or_hash,
                'created_at' => $audit->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $audits->currentPage(),
            'per_page' => $audits->perPage(),
            'total' => $audits->total(),
            'last_page' => $audits->lastPage(),
            'from' => $audits->firstItem(),
            'to' => $audits->lastItem(),
        ]);
    }
}
