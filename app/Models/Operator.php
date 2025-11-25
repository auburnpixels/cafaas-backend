<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @class Operator
 *
 * Represents an external operator using the CaaS platform API
 */
final class Operator extends Model
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
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'url',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the API keys for this operator.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(OperatorApiKey::class);
    }

    /**
     * Get the active API keys for this operator.
     */
    public function activeApiKeys(): HasMany
    {
        return $this->hasMany(OperatorApiKey::class)->whereNull('revoked_at');
    }

    /**
     * Get competitions owned by this operator.
     */
    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    /**
     * Get users associated with this operator.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get webhook subscriptions for this operator.
     */
    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class, 'user_id');
    }

    /**
     * Scope a query to only include active operators.
     */
    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the operator is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }
}
