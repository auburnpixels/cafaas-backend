<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @class FreeEntry
 *
 * Tracks free entries (postal, promotional, etc) for competitions
 */
final class FreeEntry extends Model
{
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
     * Indicates if the model should use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'competition_id',
        'operator_id',
        'user_reference',
        'reason',
        'submitted_by',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
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
     * Get the competition this free entry belongs to.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the operator this free entry belongs to.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Scope a query to entries for a specific competition.
     */
    public function scopeForCompetition($query, string $competitionId)
    {
        return $query->where('competition_id', $competitionId);
    }

    /**
     * Scope a query to entries by reason.
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope a query to entries submitted by a specific actor.
     */
    public function scopeSubmittedBy($query, string $submittedBy)
    {
        return $query->where('submitted_by', $submittedBy);
    }
}
