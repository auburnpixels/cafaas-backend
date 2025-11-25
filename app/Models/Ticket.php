<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @class Ticket
 * 
 * Uses soft deletes to maintain referential integrity with draw_events
 * and draw_audits, preserving the complete audit trail for integrity verification.
 */
final class Ticket extends Model
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
     * @var string[]
     */
    protected $fillable = [
        'free',
        'name',
        'number',
        'operator_id',
        'user_reference',
        'competition_id',
        'external_id',
        'question_answered_correctly',
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function competition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function operator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Scope a query to only include tickets eligible for draw.
     */
    public function scopeEligibleForDraw($query): void
    {
        $query->where('question_answered_correctly', true);
    }

    /**
     * Get the entry formatted for external API response.
     */
    public function externalEntryFormat(): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'ticket_number' => $this->number,
            'is_free' => $this->free ?? false,
            'user_reference' => $this->user_reference,
            'is_eligible' => $this->question_answered_correctly ?? false,
            'created_at' => $this->created_at->format('Y-m-d\TH:i:s.u\Z'),
            'competition' => [
                'id' => $this->competition->id,
                'external_id' => $this->competition->external_id,
            ]
        ];
    }
}
