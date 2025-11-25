<?php

declare(strict_types=1);

namespace App\Http\Services;

use App\Models\Competition;
use App\Models\DrawAudit;
use App\Models\Complaint;
use App\Models\DrawEvent;
use App\Models\Prize;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DrawEventChainService;
use Illuminate\Support\Facades\Request;

/**
 * @class DrawEventService
 *
 * Centralized service for logging all raffle-related events with tamper-proof hash chaining.
 * Now uses DrawEventChainService for high-performance async event logging.
 */
final class DrawEventService
{
    /**
     * @var DrawEventChainService
     */
    private DrawEventChainService $chainService;

    public function __construct(DrawEventChainService $chainService)
    {
        $this->chainService = $chainService;
    }
    /**
     * Get the current environment (production, staging, local, etc.).
     */
    protected function getEnvironment(): string
    {
        return config('app.env', 'production');
    }

    /**
     * Hash an email address for PII protection.
     */
    protected function hashEmail(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    /**
     * Calculate a hash of the entries pool for a competition/prize.
     * This creates a tamper-evident record of which entries were eligible for the draw.
     *
     * @param  Competition  $competition
     * @param  Prize|null  $prize
     * @return string SHA256 hash of the ordered entry pool
     */
    protected function calculateEntriesPoolHash(Competition $competition, ?Prize $prize = null): string
    {
        // Use the single source of truth for eligible tickets
        $tickets = $competition->eligibleTicketsForDraw($prize)
            ->orderBy('number', 'asc')
            ->get(['id', 'number']);

        // Create a string representation of the entry pool
        $entryPoolData = $tickets->map(function ($ticket) {
            return $ticket->id . ':' . $ticket->number;
        })->implode('|');

        return hash('sha256', $entryPoolData);
    }

    /**
     * Get the current actor context (user/admin info, IP, user agent).
     */
    protected function getContext(?User $actor = null): array
    {
        $context = [];

        // Add occurred_at timestamp
        $context['occurred_at'] = now()->toIso8601String();

        // Get actor information
        if ($actor) {
            $context['actor_type'] = $actor->is_admin ?? false ? 'admin' : 'user';
            $context['actor_id'] = $actor->id;
        } elseif (auth()->check()) {
            $user = auth()->user();
            $context['actor_type'] = $user->is_admin ?? false ? 'admin' : 'user';
            $context['actor_id'] = $user->id;
        } else {
            $context['actor_type'] = 'system';
        }

        // Get IP address and user agent if configured
        if (config('raffaly.draw_events.store_ip_address', true)) {
            $context['ip_address'] = Request::ip();
        }

        if (config('raffaly.draw_events.store_user_agent', true)) {
            $context['user_agent'] = Request::userAgent();
        }

        return $context;
    }

    // ==========================================
    // RAFFLE LIFECYCLE EVENTS
    // ==========================================

    /**
     * Log when a raffle is created.
     */
    public function logRaffleCreated(Competition $competition, array $details = []): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'name' => $competition->name,
            'type' => $competition->type,
            'status' => $competition->status,
            'ticket_price' => $competition->ticket_price,
            'available_tickets' => $competition->available_tickets,
            'max_tickets' => $competition->ticket_quantity,
            'ending_at' => $competition->ending_at?->toIso8601String(),
            'draw_at' => $competition->draw_at?->toIso8601String(),
            'host_id' => $competition->user_id,
            'environment' => $this->getEnvironment(),
            'details' => $details,
        ];

        return $this->chainService->logEvent(
            'raffle.created',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a raffle is updated.
     */
    public function logRaffleUpdated(Competition $competition, array $changes): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'name' => $competition->name,
            'changes' => $changes,
            'updated_fields' => array_keys($changes),
            'environment' => $this->getEnvironment(),
        ];

        // Add status changes if present
        if (isset($changes['status'])) {
            $payload['previous_status'] = $changes['status']['old'] ?? null;
            $payload['new_status'] = $changes['status']['new'] ?? $competition->status;
        }

        return $this->chainService->logEvent(
            'raffle.updated',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a raffle is published (goes live).
     */
    public function logRafflePublished(Competition $competition): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'name' => $competition->name,
            'status' => $competition->status,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'raffle.published',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a raffle is closed (entry window ends).
     */
    public function logRaffleClosed(Competition $competition, int $totalEntries): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'name' => $competition->name,
            'total_entries' => $totalEntries,
            'tickets_bought' => $competition->tickets_bought,
            'max_tickets' => $competition->ticket_quantity,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'raffle.closed',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext()
        );
    }

    // ==========================================
    // ENTRY & PARTICIPANT EVENTS
    // ==========================================

    /**
     * Log when an entry/ticket is created.
     */
    public function logEntryCreated(Ticket $ticket, bool $isFree = false, ?User $user = null): DrawEvent
    {
        // Get email from user, ticket relationship, or checkout
        $email = $user?->email
            ?? $ticket->user?->email
            ?? $ticket->checkout?->checkoutContactEmail
            ?? $ticket->checkout?->email;

        $payload = [
            'competition_id' => $ticket->competition_id,
            'competition_external_id' => $ticket->competition?->external_id,
            'operator_id' => $ticket->competition?->operator_id,
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->number,
            'ticket_external_id' => $ticket->external_id,
            'user_id' => $user?->id ?? $ticket->user_id,
            'user_reference' => $ticket->user_reference,
            'user_email_hash' => $email ? $this->hashEmail($email) : null,
            'is_free' => $isFree,
            'is_guest' => is_null($user) && is_null($ticket->user_id),
            'ticket_price' => $ticket->ticket_price,
            'checkout_id' => $ticket->checkout_id,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'entry.created',
            $payload,
            $ticket->competition_id,
            $ticket->competition?->operator_id,
            null,
            $this->getContext($user)
        );
    }

    /**
     * Log when an entry/ticket is deleted.
     */
    public function logEntryDeleted(Ticket $ticket, string $reason): DrawEvent
    {
        $email = $ticket->user?->email
            ?? $ticket->checkout?->checkoutContactEmail
            ?? $ticket->checkout?->email;

        $payload = [
            'competition_id' => $ticket->competition_id,
            'competition_external_id' => $ticket->competition?->external_id,
            'operator_id' => $ticket->competition?->operator_id,
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->number,
            'ticket_external_id' => $ticket->external_id,
            'user_id' => $ticket->user_id,
            'user_reference' => $ticket->user_reference,
            'user_email_hash' => $email ? $this->hashEmail($email) : null,
            'is_guest' => is_null($ticket->user_id),
            'reason' => $reason,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'entry.deleted',
            $payload,
            $ticket->competition_id,
            $ticket->competition?->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a paid entry is converted to a free entry.
     */
    public function logEntryConvertedToFree(Ticket $ticket, string $source): DrawEvent
    {
        $email = $ticket->user?->email
            ?? $ticket->checkout?->checkoutContactEmail
            ?? $ticket->checkout?->email;

        $payload = [
            'competition_id' => $ticket->competition_id,
            'competition_external_id' => $ticket->competition?->external_id,
            'operator_id' => $ticket->competition?->operator_id,
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->number,
            'ticket_external_id' => $ticket->external_id,
            'user_id' => $ticket->user_id,
            'user_reference' => $ticket->user_reference,
            'user_email_hash' => $email ? $this->hashEmail($email) : null,
            'is_guest' => is_null($ticket->user_id),
            'source' => $source,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'entry.converted_to_free',
            $payload,
            $ticket->competition_id,
            $ticket->competition?->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a user reaches their entry limit.
     */
    public function logEntryLimitReached(?User $user, Competition $competition, int $maxAllowed, ?string $email = null): DrawEvent
    {
        $emailToHash = $user?->email ?? $email;

        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'user_id' => $user?->id,
            'user_email_hash' => $emailToHash ? $this->hashEmail($emailToHash) : null,
            'max_allowed' => $maxAllowed,
            'is_guest' => is_null($user),
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'entry.limit_reached',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext($user)
        );
    }

    // ==========================================
    // DRAW EVENTS (CORE COMPLIANCE)
    // ==========================================

    /**
     * Log when a random seed is generated for the draw.
     */
    public function logDrawSeedGenerated(Competition $competition, string $seed, ?Prize $prize = null): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'prize_id' => $prize?->id,
            'prize_external_id' => $prize?->external_id,
            'prize_name' => $prize?->name,
            'seed' => $seed,
            'seed_hash' => hash('sha256', $seed),
            'seed_algorithm' => config('raffaly.rng.algorithm', 'random_bytes'),
            'rng_version' => config('raffaly.rng.version', '1.0'),
            'entropy_sources' => ['server'],
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'draw.seed_generated',
            $payload,
            $competition->id,
            $competition->operator_id,
            $prize?->id,
            $this->getContext()
        );
    }

    /**
     * Log when the draw process starts.
     *
     * @param  Competition  $competition
     * @param  int  $entriesCount
     * @param  Prize|null  $prize
     * @param  string|null  $entriesPoolHash  Pre-calculated hash to avoid recalculation (recommended)
     * @return DrawEvent
     */
    public function logDrawStarted(Competition $competition, int $entriesCount, ?Prize $prize = null, ?string $entriesPoolHash = null): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'name' => $competition->name,
            'prize_id' => $prize?->id,
            'prize_external_id' => $prize?->external_id,
            'prize_name' => $prize?->name,
            'prize_draw_sequence' => $prize?->draw_order,
            'entries_count' => $entriesCount,
            'entries_pool_hash' => $entriesPoolHash ?? $this->calculateEntriesPoolHash($competition, $prize),
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'draw.started',
            $payload,
            $competition->id,
            $competition->operator_id,
            $prize?->id,
            $this->getContext()
        );
    }

    /**
     * Log when randomization is run.
     *
     * @param  Competition  $competition
     * @param  string  $seedHash
     * @param  array  $rngOutput  Structured output with type and selection details
     * @param  Prize|null  $prize
     * @return DrawEvent
     */
    public function logRandomizationRun(Competition $competition, string $seedHash, array $rngOutput, ?Prize $prize = null): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'prize_id' => $prize?->id,
            'prize_external_id' => $prize?->external_id,
            'prize_name' => $prize?->name,
            'seed_hash' => $seedHash,
            'rng_output' => $rngOutput,
            'rng_output_hash' => hash('sha256', json_encode($rngOutput)),
            'rng_algorithm' => config('raffaly.rng.algorithm', 'random_bytes'),
            'rng_version' => config('raffaly.rng.version', '1.0'),
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'draw.randomization_run',
            $payload,
            $competition->id,
            $competition->operator_id,
            $prize?->id,
            $this->getContext()
        );
    }

    /**
     * Log when a draw is completed and a winner is selected.
     *
     * @param  Competition  $competition
     * @param  Ticket|null  $winningTicket
     * @param  Prize|null  $prize
     * @param  string|null  $entriesPoolHash  Pre-calculated hash to avoid recalculation (recommended)
     * @return DrawEvent
     */
    public function logDrawCompleted(Competition $competition, ?Ticket $winningTicket = null, ?Prize $prize = null, ?string $entriesPoolHash = null): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'prize_id' => $prize?->id,
            'prize_external_id' => $prize?->external_id,
            'prize_name' => $prize?->name,
            'winning_ticket_id' => $winningTicket?->id,
            'winning_ticket_number' => $winningTicket?->number,
            'total_entries_used' => $competition->eligibleTicketsForDraw($prize)->count(),
            'entries_pool_hash' => $entriesPoolHash ?? $this->calculateEntriesPoolHash($competition, $prize),
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'draw.completed',
            $payload,
            $competition->id,
            $competition->operator_id,
            $prize?->id,
            $this->getContext()
        );
    }

    /**
     * Log when a draw audit record is created.
     *
     * @param  DrawAudit  $audit
     * @param  Prize|null  $prize
     * @param  string|null  $entriesPoolHash  Pre-calculated hash to avoid recalculation (recommended)
     * @return DrawEvent
     */
    public function logDrawAuditCreated(DrawAudit $audit, ?Prize $prize = null, ?string $entriesPoolHash = null): DrawEvent
    {
        $payload = [
            'competition_id' => $audit->competition->id,
            'competition_external_id' => $audit->competition->external_id,
            'operator_id' => $audit->competition->operator_id,
            'audit_id' => $audit->draw_id,
            'prize_id' => $prize?->id ?? $audit->prize_id,
            'prize_external_id' => $prize?->external_id,
            'prize_name' => $prize?->name,
            'total_entries' => $audit->total_entries,
            'selected_entry_id' => $audit->selected_entry_id,
            'signature_hash' => $audit->signature_hash,
            'entries_pool_hash' => $entriesPoolHash ?? $this->calculateEntriesPoolHash($audit->competition, $prize),
            'seed_hash' => $audit->seed_hash ?? null,
            'rng_algorithm' => config('raffaly.rng.algorithm', 'random_bytes'),
            'rng_version' => config('raffaly.rng.version', '1.0'),
            'environment' => $this->getEnvironment(),
            'occurred_at' => $audit->drawn_at_utc->toIso8601String(),
        ];

        return $this->chainService->logEvent(
            'draw.audit_created',
            $payload,
            $audit->competition->id,
            $audit->competition->operator_id,
            $prize?->id ?? $audit->prize_id,
            $this->getContext()
        );
    }

    /**
     * Log when draw results are published.
     */
    public function logDrawPublished(Competition $competition, string $auditUrl = ''): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'name' => $competition->name,
            'audit_url' => $auditUrl,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'draw.published',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext()
        );
    }

    // ==========================================
    // COMPLIANCE & INTEGRITY EVENTS
    // ==========================================

    /**
     * Log when a compliance check is run.
     */
    public function logComplianceCheckRun(Competition $competition, string $status, array $results = []): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'check_status' => $status,
            'results' => $results,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'compliance.check_run',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a free entry is created (parity compliance).
     */
    public function logFreeEntryCreated(Ticket $ticket, string $method): DrawEvent
    {
        $email = $ticket->user?->email
            ?? $ticket->checkout?->checkoutContactEmail
            ?? $ticket->checkout?->email;

        $payload = [
            'competition_id' => $ticket->competition_id,
            'competition_external_id' => $ticket->competition?->external_id,
            'operator_id' => $ticket->competition?->operator_id,
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->number,
            'ticket_external_id' => $ticket->external_id,
            'method' => $method,
            'user_id' => $ticket->user_id,
            'user_reference' => $ticket->user_reference,
            'user_email_hash' => $email ? $this->hashEmail($email) : null,
            'is_guest' => is_null($ticket->user_id),
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'free_entry.created',
            $payload,
            $ticket->competition_id,
            $ticket->competition?->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a responsible play limit is applied.
     */
    public function logResponsiblePlayLimitApplied(?User $user, Competition $competition, int $limit, ?string $email = null): DrawEvent
    {
        $emailToHash = $user?->email ?? $email;

        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'user_id' => $user?->id,
            'user_email_hash' => $emailToHash ? $this->hashEmail($emailToHash) : null,
            'limit_applied' => $limit,
            'is_guest' => is_null($user),
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'responsible_play.limit_applied',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext($user)
        );
    }

    /**
     * Log when a complaint is submitted.
     */
    public function logComplaintSubmitted(Complaint $complaint): DrawEvent
    {
        $payload = [
            'complaint_id' => $complaint->id,
            'competition_id' => $complaint->competition_id,
            'competition_external_id' => $complaint->competition?->external_id,
            'operator_id' => $complaint->competition?->operator_id,
            'category' => $complaint->category,
            'user_id' => $complaint->user_id,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'complaint.submitted',
            $payload,
            $complaint->competition_id,
            $complaint->competition?->operator_id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when a complaint is resolved.
     */
    public function logComplaintResolved(Complaint $complaint, string $outcome): DrawEvent
    {
        $payload = [
            'complaint_id' => $complaint->id,
            'competition_id' => $complaint->competition_id,
            'competition_external_id' => $complaint->competition?->external_id,
            'operator_id' => $complaint->competition?->operator_id,
            'outcome' => $outcome,
            'status' => $complaint->status,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'complaint.resolved',
            $payload,
            $complaint->competition_id,
            $complaint->competition?->operator_id,
            null,
            $this->getContext()
        );
    }

    // ==========================================
    // ADMINISTRATIVE EVENTS
    // ==========================================

    /**
     * Log when an admin makes a manual override.
     */
    public function logAdminManualOverride(
        Competition $competition,
        string $field,
        $oldValue,
        $newValue,
        User $admin
    ): DrawEvent {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $competition->operator_id,
            'field_changed' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'admin_id' => $admin->id,
            'admin_email_hash' => $this->hashEmail($admin->email),
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'admin.manual_override',
            $payload,
            $competition->id,
            $competition->operator_id,
            null,
            $this->getContext($admin)
        );
    }

    /**
     * Log when a system cron job runs.
     */
    public function logSystemCronRan(string $jobName, float $duration, array $results = []): DrawEvent
    {
        $payload = [
            'job_name' => $jobName,
            'duration_seconds' => $duration,
            'results' => $results,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'system.cron_ran',
            $payload,
            null,
            null,
            null,
            ['actor_type' => 'system']
        );
    }

    /**
     * Log when system integrity is verified.
     */
    public function logSystemIntegrityVerified(bool $status, array $details = []): DrawEvent
    {
        $payload = [
            'verification_status' => $status ? 'pass' : 'fail',
            'details' => $details,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'system.integrity_verified',
            $payload,
            null,
            null,
            null,
            ['actor_type' => 'system']
        );
    }

    /**
     * Log when a daily digest is created.
     */
    public function logDailyDigestCreated(string $date, string $digestHash, int $eventCount): DrawEvent
    {
        $payload = [
            'date' => $date,
            'digest_hash' => $digestHash,
            'event_count' => $eventCount,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'system.daily_digest_created',
            $payload,
            null,
            null,
            null,
            ['actor_type' => 'system']
        );
    }

    // ==========================================
    // OPERATOR API EVENTS
    // ==========================================

    /**
     * Log when an operator makes an API request.
     * Note: Be careful with the $data parameter - avoid logging PII or sensitive information.
     * Consider sanitizing or summarizing the data before logging.
     */
    public function logOperatorApiRequest(\App\Models\Operator $operator, string $endpoint, array $data = []): DrawEvent
    {
        $payload = [
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'endpoint' => $endpoint,
            'method' => request()->method(),
            'data' => $data,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'operator.api_request',
            $payload,
            null,
            $operator->id,
            null,
            $this->getContext()
        );
    }

    /**
     * Log when an operator creates a competition.
     */
    public function logCompetitionCreatedByOperator(Competition $competition, \App\Models\Operator $operator): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'name' => $competition->name,
            'max_tickets' => $competition->ticket_quantity,
            'draw_at' => $competition->draw_at?->toIso8601String(),
            'environment' => $this->getEnvironment(),
        ];

        // operator_id will be automatically populated from competition
        return $this->chainService->logEvent(
            'operator.competition_created',
            $payload,
            $competition->id,
            $operator->id,
            null,
            ['actor_type' => 'operator', 'actor_id' => $operator->id]
        );
    }

    /**
     * Log when an operator requests a draw.
     */
    public function logDrawRequestedByOperator(Competition $competition, \App\Models\Operator $operator, ?Prize $prize = null): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'prize_id' => $prize?->id,
            'prize_external_id' => $prize?->external_id,
            'prize_name' => $prize?->name,
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'name' => $competition->name,
            'total_entries' => $competition->tickets()->count(),
            'environment' => $this->getEnvironment(),
        ];

        // operator_id will be automatically populated from competition
        return $this->chainService->logEvent(
            'operator.draw_requested',
            $payload,
            $competition->id,
            $operator->id,
            $prize?->id,
            ['actor_type' => 'operator', 'actor_id' => $operator->id]
        );
    }

    /**
     * Log when a prize is created.
     */
    public function logPrizeCreated(Competition $competition, Prize $prize, \App\Models\Operator $operator): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $operator->id,
            'prize_id' => $prize->id,
            'prize_external_id' => $prize->external_id,
            'prize_name' => $prize->name,
            'draw_order' => $prize->draw_order,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'prize.created',
            $payload,
            $competition->id,
            $operator->id,
            $prize->id,
            ['actor_type' => 'operator', 'actor_id' => $operator->id]
        );
    }

    /**
     * Log when a prize is updated.
     */
    public function logPrizeUpdated(Competition $competition, Prize $prize, \App\Models\Operator $operator, array $changes): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $operator->id,
            'prize_id' => $prize->id,
            'prize_external_id' => $prize->external_id,
            'prize_name' => $prize->name,
            'changes' => $changes,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'prize.updated',
            $payload,
            $competition->id,
            $operator->id,
            $prize->id,
            ['actor_type' => 'operator', 'actor_id' => $operator->id]
        );
    }

    /**
     * Log when a prize is deleted.
     */
    public function logPrizeDeleted(Competition $competition, Prize $prize, \App\Models\Operator $operator): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $operator->id,
            'prize_id' => $prize->id,
            'prize_external_id' => $prize->external_id,
            'prize_name' => $prize->name,
            'environment' => $this->getEnvironment(),
        ];

        return $this->chainService->logEvent(
            'prize.deleted',
            $payload,
            $competition->id,
            $operator->id,
            $prize->id,
            ['actor_type' => 'operator', 'actor_id' => $operator->id]
        );
    }

    /**
     * Log when an operator creates an entry.
     */
    public function logOperatorEntryCreated(Competition $competition, \App\Models\Operator $operator, array $details = []): DrawEvent
    {
        $payload = [
            'competition_id' => $competition->id,
            'competition_external_id' => $competition->external_id,
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'entry_external_id' => $details['external_id'] ?? null,
            'is_free' => $details['is_free'] ?? false,
            'user_reference' => $details['user_reference'] ?? null,
            'environment' => $this->getEnvironment(),
        ];

        // operator_id will be automatically populated from competition
        return $this->chainService->logEvent(
            'operator.entry_created',
            $payload,
            $competition->id,
            $operator->id,
            null,
            ['actor_type' => 'operator', 'actor_id' => $operator->id]
        );
    }
}
