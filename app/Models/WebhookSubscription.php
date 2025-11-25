<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @class WebhookSubscription
 */
final class WebhookSubscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'url',
        'secret',
        'events',
        'is_active',
        'failure_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'failure_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            // Generate a random secret if not provided
            if (empty($model->secret)) {
                $model->secret = Str::random(64);
            }
        });
    }

    /**
     * Get the user that owns the webhook subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to subscriptions for a specific event type.
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->whereJsonContains('events', $eventType);
    }

    /**
     * Check if this subscription is subscribed to a given event type.
     */
    public function isSubscribedTo(string $eventType): bool
    {
        return in_array($eventType, $this->events ?? []);
    }

    /**
     * Increment the failure count.
     */
    public function incrementFailureCount(): void
    {
        $this->increment('failure_count');

        // Automatically disable after 10 consecutive failures
        if ($this->failure_count >= 10) {
            $this->update(['is_active' => false]);
        }
    }

    /**
     * Reset the failure count.
     */
    public function resetFailureCount(): void
    {
        $this->update(['failure_count' => 0]);
    }

    /**
     * Validate the URL format.
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && (str_starts_with($url, 'https://') || str_starts_with($url, 'http://'));
    }

    /**
     * Get available event types.
     */
    public static function availableEvents(): array
    {
        return [
            'draw.completed',
            'audit.published',
            'raffle.created',
            'raffle.published',
            'entry.created',
            'complaint.submitted',
        ];
    }
}
