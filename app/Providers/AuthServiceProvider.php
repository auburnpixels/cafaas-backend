<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\App;
use App\Models\Charity;
use App\Models\Competition;
use App\Models\Email;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Question;
use App\Models\ShippingAddress;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Winner;
use App\Policies\AdminPolicy;
use App\Policies\AppPolicy;
use App\Policies\CharityPolicy;
use App\Policies\CompetitionPolicy;
use App\Policies\EmailPolicy;
use App\Policies\FaqPolicy;
use App\Policies\PagePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\ShippingAddressPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use App\Policies\WinnerPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        App::class => AppPolicy::class,
        Admin::class => AdminPolicy::class,
        Charity::class => CharityPolicy::class,
        Competition::class => CompetitionPolicy::class,
        Email::class => EmailPolicy::class,
        Faq::class => FaqPolicy::class,
        Page::class => PagePolicy::class,
        Payment::class => PaymentPolicy::class,
        Product::class => ProductPolicy::class,
        Question::class => QuestionPolicy::class,
        Ticket::class => TicketPolicy::class,
        User::class => UserPolicy::class,
        Winner::class => WinnerPolicy::class,
        ShippingAddress::class => ShippingAddressPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
