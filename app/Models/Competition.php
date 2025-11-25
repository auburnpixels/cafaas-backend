<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @class Competition
 *
 * Cleaned version - removed legacy code for drops, access raffles, charity donations,
 * affiliate systems, shipping, and other unused features. Focused on operator API.
 */
final class Competition extends Model
{
    // Status Constants
    const STATUS_UNPUBLISHED = 'unpublished';

    const STATUS_ACTIVE = 'active';

    const STATUS_AWAITING_DRAW = 'awaiting_draw';

    const STATUS_COMPLETED = 'completed';

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
     * @var string[]
     */
    protected $casts = [
        'draw_at' => 'datetime',
        'ending_at' => 'datetime',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'external_id',
        'status',
        'draw_at',
        'ending_at',
        'summary',
        'details',
        'ticket_quantity',
        'operator_id',
        'category_id',
        'user_id',
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the operator that owns this competition (if operator-owned).
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Get free entries for this competition.
     */
    public function freeEntries()
    {
        return $this->hasMany(FreeEntry::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function checkouts()
    {
        return $this->hasMany(Checkout::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function drawAudits()
    {
        return $this->hasMany(DrawAudit::class, 'competition_id', 'id');
    }

    /**
     * Get prizes for this competition.
     */
    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function drawEvents()
    {
        return $this->hasMany(DrawEvent::class, 'competition_id', 'id');
    }

    /**
     * Scope a query to only include active competitions.
     *
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include unpublished competitions.
     *
     * @return mixed
     */
    public function scopeUnpublished($query)
    {
        return $query->where('status', self::STATUS_UNPUBLISHED);
    }

    /**
     * Scope a query to exclude active competitions.
     *
     * @return mixed
     */
    public function scopeNonActive($query)
    {
        return $query->where('status', '!=', self::STATUS_ACTIVE);
    }

    /**
     * Get eligible tickets for a draw.
     * This is the single source of truth for which tickets can be included in a draw.
     *
     * @param  Prize|null  $prize  Optional prize for prize-specific filtering
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eligibleTicketsForDraw(?Prize $prize = null)
    {
        $query = $this->tickets()
            ->where('question_answered_correctly', true);

        // Future: Add additional eligibility filters here
        // - Exclude voided entries
        // - Filter by prize-specific rules
        // - Exclude specific ticket types if needed

        return $query;
    }
}
