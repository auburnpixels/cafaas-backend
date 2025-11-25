<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @class Prize
 *
 * Represents a prize within a competition
 *
 * Uses soft deletes to maintain referential integrity with draw_events
 * and preserve the complete audit trail for integrity verification.
 */
final class Prize extends Model
{
    use SoftDeletes;
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
        'competition_id',
        'external_id',
        'name',
        'draw_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'draw_order' => 'integer',
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
        });
    }

    /**
     * Get the competition that owns this prize.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get all draw audits for this prize.
     */
    public function drawAudits(): HasMany
    {
        return $this->hasMany(DrawAudit::class, 'prize_id', 'id');
    }

    /**
     * Get all draw events for this prize.
     */
    public function drawEvents(): HasMany
    {
        return $this->hasMany(DrawEvent::class, 'prize_id', 'id');
    }

    /**
     * Scope a query to only include prizes that have not been drawn.
     */
    public function scopeUndrawn($query): void
    {
        $query->whereDoesntHave('drawAudits');
    }

    /**
     * Scope a query to only include prizes that have been drawn.
     */
    public function scopeDrawn($query): void
    {
        $query->whereHas('drawAudits');
    }

    /**
     * Scope a query to prizes for a specific competition.
     */
    public function scopeForCompetition($query, string $competitionId)
    {
        return $query->where('competition_id', $competitionId);
    }

    /**
     * Scope a query ordered by draw_order.
     */
    public function scopeOrderedByDraw($query)
    {
        return $query->orderBy('draw_order', 'asc');
    }

    /**
     * Check if this prize has been drawn.
     */
    public function hasBeenDrawn(): bool
    {
        return $this->drawAudits()->exists();
    }

    /**
     * Get the winning ticket for this prize (if drawn).
     */
    public function getWinningTicket(): ?Ticket
    {
        $audit = $this->drawAudits()->first();

        return $audit?->winningTicket;
    }
}

