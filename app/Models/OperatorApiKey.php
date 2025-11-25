<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @class OperatorApiKey
 *
 * Manages API keys for operator authentication
 */
final class OperatorApiKey extends Model
{
    /**
     * Indicates if the model should use timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'operator_id',
        'key',
        'secret',
        'name',
        'last_used_at',
        'revoked_at',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'secret' => 'encrypted',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden.
     *
     * @var array<string>
     */
    protected $hidden = [
        'key',
        'secret',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();
    }

    /**
     * Get the operator that owns this API key.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Generate a new API key.
     *
     * @return array ['key' => string, 'hash' => string]
     */
    public static function generateKey(): array
    {
        // Generate a secure random key without prefix
        $key = Str::random(64);
        $hash = hash('sha256', $key);

        return [
            'key' => $key,
            'hash' => $hash,
        ];
    }

    /**
     * Verify an API key against this record.
     */
    public function verifyKey(string $key): bool
    {
        return hash_equals($this->key, hash('sha256', $key));
    }

    /**
     * Check if the API key is valid (not revoked).
     */
    public function isValid(): bool
    {
        return is_null($this->revoked_at) && $this->operator->is_active;
    }

    /**
     * Revoke this API key.
     */
    public function revoke(): void
    {
        $this->revoked_at = now();
        $this->save();
    }

    /**
     * Update the last used timestamp.
     */
    public function recordUsage(): void
    {
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * Scope a query to only include active (non-revoked) keys.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Scope a query to only include revoked keys.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Get the masked version of the key for display.
     */
    public function getMaskedKeyAttribute(): string
    {
        return '****'.substr($this->key, -8);
    }
}
