<?php

namespace App\Providers;

use App\Events\AccessLinkExpired;
use App\Events\CompetitionAccepted;
use App\Events\CompetitionDisputed;
use App\Events\CompetitionDrawn;
use App\Events\CompetitionDrawTimeChanged;
use App\Events\CompetitionEnded;
use App\Events\CompetitionProductShipped;
use App\Events\CompetitionRevoked;
use App\Events\CompetitionSecondPlaceWinnerChosen;
use App\Events\CompetitionThirdPlaceWinnerChosen;
use App\Events\CompetitionTicketsBought;
use App\Events\CompetitionWinnerChosen;
use App\Events\FollowerNew;
use App\Events\PrizeAccepted;
use App\Events\PrizeDisputed;
use App\Events\PrizeDrawn;
use App\Events\PrizeRevoked;
use App\Events\SubscriberNew;
use App\Listeners\SendAccessLinkExpiredNotification;
use App\Listeners\SendCompetitionAcceptedNotification;
use App\Listeners\SendCompetitionDisputedToHostNotification;
use App\Listeners\SendCompetitionDisputedToUserNotification;
use App\Listeners\SendCompetitionDrawnNotification;
use App\Listeners\SendCompetitionDrawnNotificationHost;
use App\Listeners\SendCompetitionDrawTimeChangedNotification;
use App\Listeners\SendCompetitionEndedNotification;
use App\Listeners\SendCompetitionEndedNotificationHost;
use App\Listeners\SendCompetitionProductShippedNotification;
use App\Listeners\SendCompetitionRevokedNotification;
use App\Listeners\SendCompetitionSecondPlaceWinnerChosenNotification;
use App\Listeners\SendCompetitionThirdPlaceWinnerChosenNotification;
use App\Listeners\SendCompetitionTicketsBoughtNotification;
use App\Listeners\SendCompetitionWinnerChosenNotification;
use App\Listeners\SendPrizeAcceptedNotification;
use App\Listeners\SendPrizeDisputedToHostNotification;
use App\Listeners\SendPrizeDisputedToRaffalyNotification;
use App\Listeners\SendPrizeDisputedToUserNotification;
use App\Listeners\SendPrizeDrawnNotification;
use App\Listeners\SendPrizeRevokedNotification;
use App\Listeners\TriggerZapierFollowerNew;
use App\Listeners\TriggerZapierSubscriberNew;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AccessLinkExpired::class => [
            SendAccessLinkExpiredNotification::class,
        ],

        FollowerNew::class => [
            TriggerZapierFollowerNew::class,
        ],

        SubscriberNew::class => [
            TriggerZapierSubscriberNew::class,
        ],

        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        CompetitionTicketsBought::class => [
            SendCompetitionTicketsBoughtNotification::class,
        ],

        CompetitionEnded::class => [
            SendCompetitionEndedNotification::class,
            SendCompetitionEndedNotificationHost::class,
        ],

        CompetitionDrawn::class => [
            SendCompetitionDrawnNotification::class,
            SendCompetitionDrawnNotificationHost::class,
        ],

        PrizeDrawn::class => [
            SendPrizeDrawnNotification::class,
        ],

        CompetitionAccepted::class => [
            SendCompetitionAcceptedNotification::class,
        ],

        PrizeAccepted::class => [
            SendPrizeAcceptedNotification::class,
        ],

        CompetitionRevoked::class => [
            SendCompetitionRevokedNotification::class,
        ],

        PrizeRevoked::class => [
            SendPrizeRevokedNotification::class,
        ],

        CompetitionDisputed::class => [
            SendCompetitionDisputedToUserNotification::class,
            SendCompetitionDisputedToHostNotification::class,
        ],

        PrizeDisputed::class => [
            SendPrizeDisputedToUserNotification::class,
            SendPrizeDisputedToHostNotification::class,
            SendPrizeDisputedToRaffalyNotification::class,
        ],

        CompetitionWinnerChosen::class => [
            SendCompetitionWinnerChosenNotification::class,
        ],

        CompetitionSecondPlaceWinnerChosen::class => [
            SendCompetitionSecondPlaceWinnerChosenNotification::class,
        ],

        CompetitionThirdPlaceWinnerChosen::class => [
            SendCompetitionThirdPlaceWinnerChosenNotification::class,
        ],

        CompetitionProductShipped::class => [
            SendCompetitionProductShippedNotification::class,
        ],

        CompetitionDrawTimeChanged::class => [
            SendCompetitionDrawTimeChangedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
