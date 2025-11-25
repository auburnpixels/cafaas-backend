<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Services\DrawEventService;
use App\Http\Services\WebhookService;
use App\Models\Competition;
use App\Models\DrawAudit;
use App\Models\Operator;
use App\Models\Prize;
use App\Models\Ticket;
use Illuminate\Support\Str;

/**
 * @class DrawOrchestrationService
 *
 * Orchestrates the draw process for competitions with multi-prize support
 */
final class DrawOrchestrationService
{
    public function __construct(
        private readonly DrawEventService $drawEventService,
        private readonly WebhookService $webhookService
    ) {}

    /**
     * Execute a draw for a specific prize.
     *
     * @param  Operator|null  $operator  The operator requesting the draw
     * @param  array  $excludedTicketIds  Ticket IDs to exclude from eligible pool
     * @return array ['success' => bool, 'audit' => DrawAudit, 'winner_ticket' => Ticket, 'prize' => Prize]
     */
    public function executeDrawForPrize(
        Prize $prize,
        Competition $competition,
        ?Operator $operator = null,
        array $excludedTicketIds = []
    ): array {
        // Log draw start for this prize
        $this->drawEventService->logDrawStarted($competition, $competition->tickets()->count(), $prize);

        if ($operator) {
            $this->drawEventService->logDrawRequestedByOperator($competition, $operator, $prize);
        }

        // Validate eligible pool (excluding previous winners)
        $eligibleTickets = $this->validateEligiblePool($competition, $excludedTicketIds);

        // Generate RNG seed
        $seed = $this->generateSeed($competition, $prize);
        $seedHash = hash('sha256', $seed);
        $this->drawEventService->logDrawSeedGenerated($competition, $seed, $prize);

        // Select winner
        $winningTicket = $this->selectWinner($eligibleTickets, $seed);

        // Log randomization
        $this->drawEventService->logRandomizationRun($competition, $seedHash, [
            'prize_id' => $prize->external_id,
            'total_pool' => $eligibleTickets->count(),
            'selected_index' => $eligibleTickets->search(fn ($t) => $t->id === $winningTicket->id),
        ], $prize);

        // Create audit record
        $audit = $this->createAuditRecord($competition, $prize, $eligibleTickets, $seedHash, $winningTicket);

        // Log draw completion
        $this->drawEventService->logDrawCompleted($competition, $winningTicket, $prize);

        // Log audit creation
        $this->drawEventService->logDrawAuditCreated($audit, $prize);

        // Dispatch webhook if operator has subscriptions
        if ($operator) {
            $this->dispatchDrawWebhook($competition, $prize, $audit, $winningTicket);
        }

        return [
            'success' => true,
            'audit' => $audit,
            'winner_ticket' => $winningTicket,
            'prize' => $prize,
            'draw_id' => $audit->draw_id,
        ];
    }

    /**
     * Execute draws for all undrawn prizes in a competition.
     *
     * @param  Operator|null  $operator  The operator requesting the draw
     * @return array Array of draw results, one per prize
     */
    public function executeDrawsForAllPrizes(Competition $competition, ?Operator $operator = null): array
    {
        // Get all undrawn prizes ordered by draw_order
        $prizes = $competition->prizes()->undrawn()->orderedByDraw()->get();

        if ($prizes->isEmpty()) {
            throw new \Exception('No undrawn prizes found for this competition');
        }

        $results = [];
        $excludedTicketIds = [];

        foreach ($prizes as $prize) {
            try {
                $result = $this->executeDrawForPrize($prize, $competition, $operator, $excludedTicketIds);
                $results[] = $result;

                // Exclude this winner from subsequent draws
                if ($result['winner_ticket']->id ?? null) {
                    $excludedTicketIds[] = $result['winner_ticket']->id;
                }
            } catch (\Exception $e) {
                // Log error but continue with next prize
                \Log::error("Failed to draw prize {$prize->external_id}: {$e->getMessage()}");
                throw $e; // Re-throw to halt the process
            }
        }

        return $results;
    }

    /**
     * Execute a draw for a competition (legacy support - draws first undrawn prize).
     *
     * @deprecated Use executeDrawForPrize or executeDrawsForAllPrizes instead
     * @param  Operator|null  $operator  The operator requesting the draw
     * @return array ['success' => bool, 'audit' => DrawAudit, 'winner_ticket' => Ticket]
     */
    public function executeDrawForCompetition(Competition $competition, ?Operator $operator = null): array
    {
        // Get first undrawn prize
        $prize = $competition->prizes()->undrawn()->orderedByDraw()->first();

        if (!$prize) {
            throw new \Exception('No undrawn prizes found for this competition');
        }

        return $this->executeDrawForPrize($prize, $competition, $operator);
    }

    /**
     * Validate and retrieve eligible tickets for the draw.
     *
     * @param  array  $excludedTicketIds  Ticket IDs to exclude from the pool
     */
    public function validateEligiblePool(Competition $competition, array $excludedTicketIds = []): \Illuminate\Support\Collection
    {
        // Get all tickets for completed checkouts with correct answers
        $query = $competition->tickets()->eligibleForDraw();

        // Exclude specific ticket IDs (e.g., previous winners)
        if (!empty($excludedTicketIds)) {
            $query->whereNotIn('id', $excludedTicketIds);
        }

        $tickets = $query->get();

        // Filter out any tickets that shouldn't be eligible
        // (e.g., refunded, disputed, etc.)
        $eligibleTickets = $tickets->filter(function ($ticket) {
            // Add business logic here if needed
            return true;
        });

        return $eligibleTickets;
    }

    /**
     * Select a winner using CSPRNG.
     *
     * @param  string  $seed  Seed for additional entropy
     */
    public function selectWinner(\Illuminate\Support\Collection $eligibleTickets, string $seed): Ticket|null
    {
        $count = $eligibleTickets->count();

        if ($count === 0) {
            return null;
        }

        // Use PHP's cryptographically secure random_int
        $selectedIndex = random_int(0, $count - 1);

        // Get the ticket at the selected index
        $winningTicket = $eligibleTickets->values()->get($selectedIndex);

        return $winningTicket;
    }

    /**
     * Generate a cryptographically secure seed for the draw.
     */
    protected function generateSeed(Competition $competition, ?Prize $prize = null): string
    {
        // Combine multiple entropy sources
        $entropy = [
            $competition->id,
            $prize?->id ?? 'no-prize',
            $prize?->external_id ?? 'no-prize-id',
            now()->format('Y-m-d H:i:s.u'),
            random_bytes(32),
            microtime(true),
        ];

        return hash('sha256', implode('|', $entropy));
    }

    /**
     * Create an audit record for the draw.
     */
    protected function createAuditRecord(
        Competition $competition,
        Prize $prize,
        \Illuminate\Support\Collection $eligibleTickets,
        string $seedHash,
        Ticket $winningTicket = null
    ): DrawAudit {
        // Generate pool hash
        $poolHash = DrawAudit::generatePoolHash($eligibleTickets);

        // Get previous audit signature for chain linking
        $previousSignature = DrawAudit::getLastAuditSignature();

        // Generate draw ID
        $drawId = Str::uuid()->toString();

        // Generate signature
        $signature = DrawAudit::generateSignature(
            $competition->id,
            $drawId,
            now()->format('Y-m-d H:i:s'),
            $eligibleTickets->count(),
            $seedHash,
            $winningTicket->id ?? null,
            $previousSignature
        );

        // Create audit record
        $audit = DrawAudit::create([
            'competition_id' => $competition->id,
            'prize_id' => $prize->id,
            'draw_id' => $drawId,
            'drawn_at_utc' => now(),
            'total_entries' => $eligibleTickets->count(),
            'rng_seed_or_hash' => $seedHash,
            'pool_hash' => $poolHash,
            'selected_entry_id' => $winningTicket->id ?? null,
            'signature_hash' => $signature,
            'previous_signature_hash' => $previousSignature,
        ]);

        return $audit;
    }

    /**
     * Dispatch webhook notification for draw completion.
     */
    protected function dispatchDrawWebhook(
        Competition $competition,
        Prize $prize,
        DrawAudit $audit,
        Ticket $winningTicket = null
    ): void {
        $payload = [
            'competition_id' => $competition->external_id ?? $competition->id,
            'competition_uuid' => $competition->id,
            'prize_id' => $prize->external_id,
            'prize_name' => $prize->name,
            'draw_id' => $audit->draw_id,
            'winner_entry_id' => $winningTicket->external_id ?? $winningTicket->id ?? null,
            'winner_ticket_number' => $winningTicket->number ?? null,
            'total_entries' => $audit->total_entries,
            'drawn_at' => $audit->drawn_at_utc->toIso8601String(),
            'signature_hash' => $audit->signature_hash,
            'audit_url' => route('api.v1.raffles.audit', ['uuid' => $competition->id]),
        ];

        $this->webhookService->dispatch('draw.completed', $payload);
    }

    /**
     * Get draw statistics for a competition.
     */
    public function getDrawStatistics(Competition $competition): array
    {
        $totalTickets = $competition->tickets()->count();
        $freeTickets = $competition->tickets()->where('free', true)->count();
        $paidTickets = $totalTickets - $freeTickets;

        $audits = $competition->drawAudits()->count();

        return [
            'competition_id' => $competition->external_id ?? $competition->id,
            'total_entries' => $totalTickets,
            'paid_entries' => $paidTickets,
            'free_entries' => $freeTickets,
            'draws_completed' => $audits,
            'has_winner' => $audits > 0,
            'last_draw_at' => $competition->drawAudits()->latest('drawn_at_utc')->first()?->drawn_at_utc,
        ];
    }
}
