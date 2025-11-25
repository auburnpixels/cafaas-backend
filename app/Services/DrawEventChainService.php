<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessChainEvent;
use App\Models\DrawEvent;
use Illuminate\Support\Str;

/**
 * @class DrawEventChainService
 *
 * High-performance event logging service that queues hash chain processing
 * to keep API responses fast while maintaining tamper-proof audit trails.
 */
final class DrawEventChainService
{
    /**
     * Log an event without computing hashes (fast insert).
     * Chain processing is queued asynchronously.
     *
     * @param  string  $eventType  The type of event
     * @param  array  $payload  The event-specific data
     * @param  string|null  $competitionId  Optional competition ID (UUID)
     * @param  int|null  $operatorId  Optional operator ID
     * @param  string|null  $prizeId  Optional prize ID (UUID)
     * @param  array  $context  Additional context (actor, IP, etc.)
     * @return DrawEvent  The created event (not yet chained)
     */
    public function logEvent(
        string $eventType,
        array $payload,
        ?string $competitionId = null,
        ?string $operatorId = null,
        ?string $prizeId = null,
        array $context = []
    ): DrawEvent {
        // Check if event logging is enabled
        if (! config('raffaly.draw_events.enabled', true)) {
            // Return a dummy event for testing purposes
            $event = new DrawEvent;
            $event->id = (string) Str::uuid();

            return $event;
        }

        // Get the next sequence number atomically
        $nextSequence = $this->getNextSequence();

        $createdAt = now();

        // Create the event without hash computation (fast!)
        $event = DrawEvent::create([
            'id' => (string) Str::uuid(),
            'sequence' => $nextSequence,
            'competition_id' => $competitionId,
            'prize_id' => $prizeId,
            'operator_id' => $operatorId,
            'event_type' => $eventType,
            'event_payload' => $payload,
            'event_hash' => '', // Will be set by job
            'previous_event_hash' => null, // Will be set by job
            'previous_hash' => null, // New chain field
            'current_hash' => null, // New chain field
            'is_chained' => false, // Not yet processed
            'actor_type' => $context['actor_type'] ?? null,
            'actor_id' => $context['actor_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => $createdAt,
        ]);

        // Queue chain processing asynchronously
        ProcessChainEvent::dispatch($event->id)->onQueue('chain');

        return $event;
    }

    /**
     * Get the next sequence number atomically.
     * Uses a database transaction with locking to ensure uniqueness.
     *
     * @return int  The next sequence number
     */
    protected function getNextSequence(): int
    {
        return \DB::transaction(function () {
            // Lock the last event to prevent concurrent sequence allocation
            $lastEvent = DrawEvent::orderBy('sequence', 'desc')
                ->lockForUpdate()
                ->first();

            return $lastEvent ? $lastEvent->sequence + 1 : 1;
        });
    }
}



