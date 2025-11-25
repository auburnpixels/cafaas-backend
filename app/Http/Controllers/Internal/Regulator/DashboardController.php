<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal\Regulator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Regulator\VerifyIntegrityRequest;
use App\Models\Competition;
use App\Models\CompetitionDrawAudit;
use App\Models\DrawEvent;
use App\Models\Operator;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly ComplianceService $complianceService
    ) {
        $this->middleware('regulator.only');
    }

    /**
     * Get all operators
     */
    public function operators(Request $request): JsonResponse
    {
        $operators = Operator::orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($operators);
    }

    /**
     * Get competitions for a specific operator
     */
    public function operatorCompetitions(Request $request, int $id): JsonResponse
    {
        $operator = Operator::findOrFail($id);

        $competitions = $operator->competitions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($competitions);
    }

    /**
     * Get competition audits (any competition, not restricted to operator)
     */
    public function competitionAudits(Request $request, string $uuid): JsonResponse
    {
        $competition = Competition::where('uuid', $uuid)->firstOrFail();

        $audits = $competition->drawAudits()
            ->with(['prize:id,name', 'winningTicket:id,external_id,number'])
            ->orderBy('drawn_at_utc', 'desc')
            ->paginate(10);

        return response()->json($audits);
    }

    /**
     * Get competition events (any competition, not restricted to operator)
     */
    public function competitionEvents(Request $request, string $uuid): JsonResponse
    {
        $competition = Competition::where('uuid', $uuid)->firstOrFail();

        $events = $competition->drawEvents()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($events);
    }

    /**
     * Get platform-wide compliance dashboard
     */
    public function complianceDashboard(Request $request): JsonResponse
    {
        $dashboard = $this->complianceService->generateRegulatorComplianceDashboard();

        return response()->json($dashboard);
    }

    /**
     * Verify chain integrity across the platform or for a specific competition
     */
    public function verifyIntegrity(VerifyIntegrityRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $competitionId = $validated['competition_id'] ?? null;

        if ($competitionId) {
            // Verify specific competition
            $competition = Competition::where('id', $competitionId)->firstOrFail();
            $result = $this->verifyCompetitionIntegrity($competition);
        } else {
            // Verify entire platform
            $result = $this->verifyPlatformIntegrity();
        }

        return response()->json($result);
    }

    /**
     * Verify integrity for a specific competition
     */
    protected function verifyCompetitionIntegrity(Competition $competition): array
    {
        $events = DrawEvent::where('competition_id', $competition->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $audits = CompetitionDrawAudit::where('competition_id', $competition->id)
            ->orderBy('drawn_at_utc', 'asc')
            ->get();

        $eventIntegrity = $this->verifyEventChain($events);
        $auditIntegrity = $this->verifyAuditChain($audits);

        return [
            'competition' => [
                'uuid' => $competition->id,
                'name' => $competition->name ?? $competition->title,
            ],
            'integrity_check' => [
                'overall_status' => ($eventIntegrity['valid'] && $auditIntegrity['valid']) ? 'VALID' : 'INVALID',
                'event_chain' => $eventIntegrity,
                'audit_chain' => $auditIntegrity,
            ],
        ];
    }

    /**
     * Verify integrity for the entire platform
     */
    protected function verifyPlatformIntegrity(): array
    {
        $allEvents = DrawEvent::orderBy('created_at', 'asc')->get();
        $allAudits = CompetitionDrawAudit::orderBy('drawn_at_utc', 'asc')->get();

        $eventIntegrity = $this->verifyEventChain($allEvents);
        $auditIntegrity = $this->verifyAuditChain($allAudits);

        return [
            'scope' => 'platform',
            'integrity_check' => [
                'overall_status' => ($eventIntegrity['valid'] && $auditIntegrity['valid']) ? 'VALID' : 'INVALID',
                'event_chain' => $eventIntegrity,
                'audit_chain' => $auditIntegrity,
            ],
        ];
    }

    /**
     * Verify the event chain integrity
     */
    protected function verifyEventChain($events): array
    {
        $totalEvents = $events->count();
        $verifiedEvents = 0;
        $brokenLinks = [];

        foreach ($events as $index => $event) {
            // Compute expected hash
            $expectedHash = hash('sha256', $event->event_type.json_encode($event->payload).$event->previous_event_hash.$event->created_at);

            if ($event->event_hash === $expectedHash) {
                $verifiedEvents++;
            } else {
                $brokenLinks[] = [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'expected_hash' => $expectedHash,
                    'actual_hash' => $event->event_hash,
                ];
            }
        }

        return [
            'valid' => $totalEvents === $verifiedEvents,
            'total_events' => $totalEvents,
            'verified_events' => $verifiedEvents,
            'broken_links' => $brokenLinks,
        ];
    }

    /**
     * Verify the audit chain integrity
     */
    protected function verifyAuditChain($audits): array
    {
        $totalAudits = $audits->count();
        $verifiedAudits = 0;
        $brokenLinks = [];

        foreach ($audits as $index => $audit) {
            // Compute expected signature
            $expectedSignature = CompetitionDrawAudit::generateSignature(
                $audit->competition_id,
                $audit->draw_id,
                $audit->drawn_at_utc->format('Y-m-d H:i:s'),
                $audit->total_entries,
                $audit->rng_seed_or_hash,
                $audit->selected_entry_id,
                $audit->previous_signature_hash
            );

            if ($audit->signature_hash === $expectedSignature) {
                $verifiedAudits++;
            } else {
                $brokenLinks[] = [
                    'audit_id' => $audit->id,
                    'draw_id' => $audit->draw_id,
                    'expected_signature' => $expectedSignature,
                    'actual_signature' => $audit->signature_hash,
                ];
            }
        }

        return [
            'valid' => $totalAudits === $verifiedAudits,
            'total_audits' => $totalAudits,
            'verified_audits' => $verifiedAudits,
            'broken_links' => $brokenLinks,
        ];
    }
}
