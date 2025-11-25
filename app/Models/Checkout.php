<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Services\LocationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @class Checkout
 */
final class Checkout extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'surname',
        'user_id',
        'completed',
        'axcess',
        'completed',
        'expiry_at',
        'free_tickets',
        'competition_id',
        'voucher_code',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'expiry_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function app()
    {
        return $this->belongsTo(App::class);
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function price()
    {
        return $this->belongsTo(TicketPrice::class, 'ticket_price_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discount()
    {
        return $this->belongsTo(Discount::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competitionFreeTicket()
    {
        return $this->belongsTo(CompetitionFreeTicket::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class)->orderBy('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function answer()
    {
        return $this->belongsTo(QuestionAnswer::class, 'question_answer_id');
    }

    /**
     * @return string
     */
    public function expiryAtCountdown($location = null)
    {
        if (is_null($location)) {
            $location = app(LocationService::class)->getLocation();
        }

        if (Carbon::parse($this->expiry_at)->timezone($location && $location->timezone ? $location->timezone : 'UTC')->isPast()) {
            return 'Ended';
        }

        return Carbon::parse($this->expiry_at)->timezone($location && $location->timezone ? $location->timezone : 'UTC')->format('Y-m-d H:i:s');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Relations\HasMany[]
     */
    public function getTicketsForCompetition(Competition $competition, bool $freeTickets = false)
    {
        return $this
            ->tickets()
            ->where('free', false)
            ->whereHas('competition', function ($query) use ($competition) {
                $query->where('id', $competition->id);
            })
            ->whereDoesntHave('competitionFreeTicket')
            ->get();
    }

    /**
     * @return int
     */
    public function getFreeTicketsCountAttribute()
    {
        return $this
            ->tickets()
            ->where('free', false)
            ->whereHas('competitionFreeTicket')
            ->count();
    }

    /**
     * @return bool
     */
    public function getIsGuestCheckoutAttribute()
    {
        return is_null($this->user);
    }

    /**
     * @return mixed
     */
    public function getCheckoutContactEmailAttribute()
    {
        if ($this->isGuestCheckout) {
            return $this->email;
        }

        return $this->user->email;
    }

    /**
     * @return mixed
     */
    public function getCheckoutContactNameAttribute()
    {
        if ($this->isGuestCheckout) {
            return $this->name;
        }

        return $this->user->name;
    }

    /**
     * @return false|mixed|string
     */
    public function getCheckoutContactSurnameAttribute()
    {
        if ($this->isGuestCheckout) {
            return $this->surname;
        }

        $nameExploded = explode(' ', $this->user->name);

        return $nameExploded[1] ?? null;
    }
}
