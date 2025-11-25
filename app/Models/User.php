<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @class User
 */
final class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'email',
        'credit',
        'tiktok',
        'youtube',
        'website',
        'balance',
        'twitter',
        'linkedin',
        'password',
        'username',
        'facebook',
        'api_token',
        'biography',
        'stripe_id',
        'instagram',
        'text_color',
        'commission',
        'body_color',
        'panel_color',
        'phone_number',
        'header_image',
        'mailerlite_id',
        'profile_image',
        'shipping_costs',
        'highlight_color',
        'squarespace_token',
        'email_verified_at',
        'subscribed_newsletter',
        'phone_number_verified',
        'access_link_commission',
        'role',
        'operator_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get all users as options.
     */
    public static function options(): \Illuminate\Support\Collection
    {
        return self::orderBy('name', 'ASC')
            ->get(['id', 'name'])
            ->map(function ($user) {
                return (object) [
                    'value' => $user->id,
                    'name' => '#'.$user->id.' - '.$user->name,
                ];
            });
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmail);
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token, $this));
    }

    /**
     * Get the operator this user belongs to.
     */
    public function operator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Check if the user is an operator.
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Check if the user is a regulator.
     */
    public function isRegulator(): bool
    {
        return $this->role === 'regulator';
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function timezone(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Timezone::class, 'id', 'timezone_id');
    }

    public function tickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class)->orderByDesc('id');
    }

    public function hostRatings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class, 'host_id');
    }

    public function app(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function shippingAddress(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ShippingAddress::class);
    }

    public function follows(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UserFollow::class, 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\BelongsTo|object|null
     */
    public function hasFollow(User $user): ?\Illuminate\Database\Eloquent\Model
    {
        return $this
            ->follows()
            ->whereHas('host', function ($query) use ($user) {
                $query->where('id', $user->id);
            })
            ->first();
    }

    public function followers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserFollow::class, 'host_id', 'id');
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('id');
    }

    public function competitions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Competition::class);
    }

    public function complaints(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function domains(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function affiliateLinks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AffiliateLink::class);
    }

    public function hasVerifiedPhoneNumber(): bool
    {
        return $this->phone_number_verified === true;
    }

    public function balanceTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getEmailAttribute($value): string
    {
        return strtolower($value);
    }

    /**
     * @return int|mixed
     */
    public function getBalanceAmountAttribute(): int
    {
        $depositTransactionsSum = $this->balanceTransactions()->where('type', 'deposit')->sum('amount');
        $guaranteeTransactionsSum = $this->balanceTransactions()->where('type', 'guarantee')->sum('amount');
        $withdrawalTransactionsSum = $this->balanceTransactions()->where('type', 'withdrawal')->sum('amount');

        return ($guaranteeTransactionsSum + $depositTransactionsSum) - $withdrawalTransactionsSum;
    }

    public function getPendingCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_UNPUBLISHED])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getPendingDropsCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->drops()
            ->whereIn('status', [Competition::STATUS_UNPUBLISHED])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getActiveCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_ACTIVE])
            ->get();
    }

    public function getActiveDropsCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->drops()
            ->whereIn('status', [Competition::STATUS_ACTIVE])
            ->get();
    }

    public function getActiveCompetitionsQueryAttribute(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_ACTIVE]);
    }

    public function getPastCompetitionsQueryAttribute(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_AWAITING_DRAW, Competition::STATUS_COMPLETED]);
    }

    public function getNonPendingCompetitionsAttribute(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereNotIn('status', [Competition::STATUS_UNPUBLISHED]);
    }

    public function getNonPendingDropsCompetitionsAttribute(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this
            ->competitions()
            ->drops()
            ->whereNotIn('status', [Competition::STATUS_UNPUBLISHED]);
    }

    public function getAwaitingDrawCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_AWAITING_DRAW])
            ->get();
    }

    public function getAwaitingDrawDropsCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->drops()
            ->whereIn('status', [Competition::STATUS_AWAITING_DRAW])
            ->get();
    }

    public function getUnacceptedCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_AWAITING_ACCEPTANCE])
            ->orderByDesc('draw_at')
            ->get();
    }

    public function getUnacceptedDropsCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->drops()
            ->whereIn('status', [Competition::STATUS_AWAITING_ACCEPTANCE])
            ->orderByDesc('draw_at')
            ->get();
    }

    public function getEndedCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_ENDED])
            ->orderByDesc('draw_at')
            ->get();
    }

    public function getEndedDropsCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->drops()
            ->whereIn('status', [Competition::STATUS_ENDED])
            ->orderByDesc('draw_at')
            ->get();
    }

    public function getRejectedCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->traditional()
            ->whereIn('status', [Competition::STATUS_REJECTED])
            ->orderByDesc('draw_at')
            ->get();
    }

    public function getRejectedDropsCompetitionsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->competitions()
            ->drops()
            ->whereIn('status', [Competition::STATUS_REJECTED])
            ->orderByDesc('draw_at')
            ->get();
    }

    public function getLiveTicketsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->tickets()
            ->whereHas('competition', function ($query) {
                $query->traditional();
                $query->where('status', Competition::STATUS_ACTIVE);
            })
            ->get();
    }

    public function getWonTicketsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->tickets()
            ->whereHas('competition', function ($query) {
                $query->traditional();
                $query->where('status', Competition::STATUS_ENDED);
                $query->whereHas('winningTicket', function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('id', $this->id);
                    });
                });
            })
            ->whereHas('user', function ($query) {
                $query->where('id', auth()->user()->id);
            })
            ->get();
    }

    public function getWonUnclaimedTicketsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->tickets()
            ->whereHas('competition', function ($query) {
                $query->traditional();
                $query->where('status', Competition::STATUS_AWAITING_ACCEPTANCE);
                $query->whereHas('winningTicket', function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('id', $this->id);
                    });
                });
            })
            ->whereHas('user', function ($query) {
                $query->where('id', auth()->user()->id);
            })
            ->get();
    }

    public function getLostTicketsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->tickets()
            ->whereHas('competition', function ($query) {
                $query->traditional();
                $query->where(function ($query) {
                    $query->where('status', Competition::STATUS_AWAITING_ACCEPTANCE);
                    $query->orWhere('status', Competition::STATUS_ENDED);
                });
                $query->whereHas('winningTicket', function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('id', '!=', $this->id);
                    });
                });
            })
            ->get();
    }

    public function getAwaitingDrawTicketsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->tickets()
            ->whereHas('competition', function ($query) {
                $query->traditional();
                $query->where('status', Competition::STATUS_AWAITING_DRAW);
            })
            ->get();
    }

    public function getEndedTicketsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this
            ->tickets()
            ->whereHas('competition', function ($query) {
                $query->traditional();
                $query->where('status', Competition::STATUS_ENDED);
            })
            ->get();
    }

    public function getRatingAttribute(): float
    {
        $reviewRatings = $this->reviews->sum('rating');
        $summedRating = $this->hostRatings->sum('rating');
        $summedRating = ($reviewRatings + $summedRating);

        $reviews = $this->reviews->count();
        $hostRatings = $this->hostRatings->count();
        $reviews = ($reviews + $hostRatings);

        if ($reviews === 0) {
            return 0;
        }

        return $summedRating / $reviews;
    }

    public function getFirstNameAttribute(): ?string
    {
        $explodedName = explode(' ', $this->name);

        return $explodedName[0] ?? null;
    }

    public function getLastNameAttribute(): ?string
    {
        $explodedName = explode(' ', $this->name);

        return $explodedName[1] ?? null;
    }

    public function competitionFreeTicketContinuations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CompetitionFreeTicketContinuation::class);
    }

    public function getCompetitionFreeTicketContinuationForCompetitionAttribute(Competition $competition): mixed
    {
        return $this->competitionFreeTicketContinuations->first(function ($competitionFreeTicketContinuation) {
            return $competitionFreeTicketContinuation->id === $competitionFreeTicketContinuation->id;
        });
    }

    public function getShippingAddressStringAttribute(): string
    {
        if (! $this->shippingAddress) {
            return '';
        }

        return
            $this->shippingAddress->address_line_1.
            ($this->shippingAddress->address_line_2 ? ', '.$this->shippingAddress->address_line_2 : '').
            ', '.$this->shippingAddress->city.
            ($this->shippingAddress->state ? ', '.$this->shippingAddress->state : '').
            ', '.$this->shippingAddress->zip_code.
            ', '.$this->shippingAddress->country;
    }

    public function webhookSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }

    public function getFollowerNewWebhookSubscriptionAttribute(): mixed
    {
        return $this->webhookSubscriptions->first(function ($webhookSubscription) {
            return $webhookSubscription->event === 'follower.new';
        });

    }

    public function getSubscriberNewWebhookSubscriptionAttribute(): mixed
    {
        return $this->webhookSubscriptions->first(function ($webhookSubscription) {
            return $webhookSubscription->event === 'subscriber.new';
        });
    }

    public function getPrimaryDomainAttribute(): string
    {
        // If we have no domains, use the username profile.
        if ($this->domains->count() === 0) {
            return str_replace(['https://', 'http://'], '', config('app.url').'/'.$this->username);
        }

        // Loop as domains and check for a primary.
        foreach ($this->domains as $domain) {
            if ($domain->primary && ($domain->primary === true)) {
                return $domain->domain;
            }
        }

        // Default to the user profile.
        return str_replace(['https://', 'http://'], '', config('app.url').'/'.$this->username);
    }

    public function getWinnersCountAttribute(): int
    {
        $reviewCount = $this->reviews->count();

        // Prize model removed - returning only review count
        return $reviewCount;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'operator_id' => $this->operator_id,
        ];
    }
}
