<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @class DrawEvent
 */
final class DrawEvent extends Model
{
    /**
     * Indicates if the model should use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'sequence',
        'competition_id',
        'prize_id',
        'operator_id',
        'event_type',
        'event_payload',
        'event_hash',
        'previous_event_hash',
        'previous_hash',
        'current_hash',
        'is_chained',
        'actor_type',
        'actor_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_payload' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    /**
     * Get the competition associated with this event.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the operator associated with this event.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Get the prize associated with this event.
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    /**
     * Get the actor (user/admin) who triggered this event.
     */
    public function actor(): MorphTo
    {
        return $this->morphTo('actor');
    }

    /**
     * Scope a query to events for a specific competition.
     */
    public function scopeForCompetition($query, string $competitionId)
    {
        return $query->where('competition_id', $competitionId);
    }

    /**
     * Scope a query to events of a specific type.
     */
    public function scopeByType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope a query to events within a date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope a query to only include unchained events.
     */
    public function scopeUnchained($query)
    {
        return $query->where('is_chained', false);
    }

    /**
     * Scope a query to only include chained events.
     */
    public function scopeChained($query)
    {
        return $query->where('is_chained', true);
    }

    /**
     * Generate a tamper-proof hash for an event.
     *
     * @param  array  $data  The event data to hash
     * @param  string|null  $previousHash  The hash of the previous event in the chain
     * @return string SHA256 hash
     */
    public static function generateHash(array $data, ?string $previousHash = null): string
    {
        // Build the data string for hashing
        $hashData = [
            'event_type' => $data['event_type'] ?? '',
            'competition_id' => $data['competition_id'] ?? '',
            'event_payload' => json_encode($data['event_payload'] ?? []),
            'created_at' => $data['created_at'] ?? now()->toIso8601String(),
            'previous_hash' => $previousHash ?? '',
        ];

        $dataString = implode('|', $hashData);

        return hash('sha256', $dataString);
    }

    /**
     * Get the hash of the last event (for chain linking).
     *
     * @param  string|null  $competitionId  Optional competition ID to scope the query
     * @return string|null The last event hash or null if no events exist
     */
    public static function getLastEventHash(?string $competitionId = null): ?string
    {
        $query = self::orderBy('created_at', 'desc')->orderBy('id', 'desc');

        if ($competitionId !== null) {
            $query->where('competition_id', $competitionId);
        }

        $lastEvent = $query->first();

        return $lastEvent?->event_hash;
    }

    /**
     * Log a new event to the system.
     *
     * @param  string  $eventType  The type of event (e.g., 'raffle.created')
     * @param  array  $payload  The event-specific data
     * @param  string|null  $competitionId  Optional competition ID (UUID)
     * @param  array  $context  Additional context (actor, IP, etc.)
     * @param  string|null  $prizeId  Optional prize ID (UUID)
     * @return DrawEvent The created event
     */
    public static function logEvent(
        string $eventType,
        array $payload,
        ?string $competitionId = null,
        array $context = [],
        ?string $prizeId = null
    ): DrawEvent {
        // Check if event logging is enabled
        if (! config('raffaly.draw_events.enabled', true)) {
            // Return a dummy event for testing purposes
            $event = new self;
            $event->id = (string) Str::uuid();

            return $event;
        }

        // Use database transaction with locking to prevent race conditions
        return \DB::transaction(function () use ($eventType, $payload, $competitionId, $context, $prizeId) {
            // Lock the last event to prevent concurrent writes from getting the same previous hash and sequence
            $lastEvent = static::orderBy('sequence', 'desc')
                ->lockForUpdate()
                ->first();

            // Get the previous event hash for chain linking (globally, not per-competition)
            $previousHash = null;
            if (config('raffaly.draw_events.chain_hashing', true)) {
                $previousHash = $lastEvent?->event_hash;
            }

            // Get next sequence number
            $nextSequence = $lastEvent ? $lastEvent->sequence + 1 : 1;

            $createdAt = now();

            // Get operator_id from competition if competition_id is provided
            $operatorId = null;
            if ($competitionId) {
                $competition = Competition::find($competitionId);
                $operatorId = $competition?->operator_id;
            }

            // Prepare event data
            $eventData = [
                'event_type' => $eventType,
                'competition_id' => $competitionId,
                'event_payload' => $payload,
                'created_at' => $createdAt->toIso8601String(),
            ];

            // Generate hash
            $eventHash = static::generateHash($eventData, $previousHash);

            // Create the event
            $event = static::create([
                'id' => (string) Str::uuid(),
                'sequence' => $nextSequence,
                'competition_id' => $competitionId,
                'prize_id' => $prizeId,
                'operator_id' => $operatorId,
                'event_type' => $eventType,
                'event_payload' => $payload,
                'event_hash' => $eventHash,
                'previous_event_hash' => $previousHash,
                'actor_type' => $context['actor_type'] ?? null,
                'actor_id' => $context['actor_id'] ?? null,
                'ip_address' => $context['ip_address'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
                'created_at' => $createdAt,
            ]);

            return $event;
        });
    }

    /**
     * Verify the integrity of the event chain.
     *
     * @param  string|null  $competitionId  Optional competition ID to scope the verification
     * @return array Results of the verification
     */
    public static function verifyChainIntegrity(?string $competitionId = null): array
    {
        $query = self::orderBy('sequence', 'asc');

        if ($competitionId !== null) {
            $query->where('competition_id', $competitionId);
        }

        $events = $query->get();

        $results = [
            'total_events' => $events->count(),
            'verified_events' => 0,
            'failed_events' => 0,
            'unchained_events' => 0,
            'broken_links' => [],
            'invalid_hashes' => [],
        ];

        $previousHash = null;

        foreach ($events as $event) {
            // Skip unchained events (still being processed)
            if (! $event->is_chained) {
                $results['unchained_events']++;
                continue;
            }

            // Verify that the previous hash matches
            if ($event->previous_hash !== $previousHash) {
                $results['broken_links'][] = [
                    'event_id' => $event->id,
                    'sequence' => $event->sequence,
                    'event_type' => $event->event_type,
                    'expected_previous_hash' => $previousHash,
                    'actual_previous_hash' => $event->previous_hash,
                ];
                $results['failed_events']++;
            } else {
                // Verify the event hash itself by recalculating
                $hashInput = json_encode([
                    'id' => $event->id,
                    'sequence' => $event->sequence,
                    'event_type' => $event->event_type,
                    'payload' => $event->event_payload,
                    'competition_id' => $event->competition_id,
                    'operator_id' => $event->operator_id,
                    'prize_id' => $event->prize_id,
                    'actor_type' => $event->actor_type,
                    'actor_id' => $event->actor_id,
                    'ip_address' => $event->ip_address,
                    'user_agent' => $event->user_agent,
                    'previous_hash' => $previousHash,
                    'created_at' => $event->created_at->toIso8601String(),
                ]);

                $expectedHash = hash('sha256', $hashInput);

                if ($expectedHash !== $event->current_hash) {
                    $results['invalid_hashes'][] = [
                        'event_id' => $event->id,
                        'sequence' => $event->sequence,
                        'event_type' => $event->event_type,
                        'expected_hash' => $expectedHash,
                        'actual_hash' => $event->current_hash,
                    ];
                    $results['failed_events']++;
                } else {
                    $results['verified_events']++;
                }
            }

            $previousHash = $event->current_hash;
        }

        $results['is_valid'] = $results['failed_events'] === 0;

        return $results;
    }
}
