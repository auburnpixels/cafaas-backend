<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @class Complaint
 */
final class Complaint extends Model
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
     * @var string[]
     */
    protected $fillable = [
        'competition_id',
        'operator_id',
        'user_id',
        'email',
        'name',
        'category',
        'message',
        'admin_notes',
        'status',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => 'string',
        'category' => 'string',
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
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reporter's name (from user or provided name)
     *
     * @return string
     */
    public function getReporterNameAttribute()
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->name;
    }

    /**
     * Get the reporter's email (from user or provided email)
     *
     * @return string
     */
    public function getReporterEmailAttribute()
    {
        if ($this->user) {
            return $this->user->email;
        }

        return $this->email;
    }
}
