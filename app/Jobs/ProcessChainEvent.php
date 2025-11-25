<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DrawEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Process blockchain chain events asynchronously.
 *
 * This job implements a single-writer pattern using Redis locks to ensure
 * that only one job processes the chain at a time, preventing race conditions
 * and maintaining strict ordering.
 */
final class ProcessChainEvent implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The maximum number of exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     *
     * @param  string  $eventId  The UUID of the event to process
     */
    public function __construct(
        public string $eventId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Global chain lock (Redis) - only one job can process the chain at a time
        $lock = Cache::lock('global_draw_chain_lock', 10);

        if (! $lock->get()) {
            // Could not acquire lock → requeue for later
            $this->release(2);

            return;
        }

        try {
            $this->applyChain();
        } finally {
            $lock->release();
        }
    }

    /**
     * Apply chain processing to the next unchained event.
     */
    protected function applyChain(): void
    {
        // Find the earliest unchained event
        $next = DrawEvent::where('is_chained', false)
            ->orderBy('sequence')
            ->first();

        if (! $next) {
            return; // Nothing to process
        }

        DB::transaction(function () use ($next) {
            // Get previous chained event (globally, not per-competition)
            $prev = DrawEvent::where('is_chained', true)
                ->orderBy('sequence', 'desc')
                ->lockForUpdate()
                ->first();

            $previousHash = $prev?->current_hash;

            // Build hash input using the new chain fields
            $hashInput = json_encode([
                'id' => $next->id,
                'sequence' => $next->sequence,
                'event_type' => $next->event_type,
                'payload' => $next->event_payload,
                'competition_id' => $next->competition_id,
                'operator_id' => $next->operator_id,
                'prize_id' => $next->prize_id,
                'actor_type' => $next->actor_type,
                'actor_id' => $next->actor_id,
                'ip_address' => $next->ip_address,
                'user_agent' => $next->user_agent,
                'previous_hash' => $previousHash,
                'created_at' => $next->created_at->toIso8601String(),
            ]);

            $currentHash = hash('sha256', $hashInput);

            // Update the event with chain information
            $next->update([
                'previous_hash' => $previousHash,
                'current_hash' => $currentHash,
                'is_chained' => true,
                // Also update the old fields for backward compatibility
                'previous_event_hash' => $previousHash,
                'event_hash' => $currentHash,
            ]);
        });

        // Continue chaining the NEXT event if present
        $nextUnchained = DrawEvent::where('is_chained', false)
            ->orderBy('sequence')
            ->first();

        if ($nextUnchained) {
            self::dispatch($nextUnchained->id)->onQueue('chain');
        }
    }
}
