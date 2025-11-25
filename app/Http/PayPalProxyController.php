<?php

namespace App\Http;

use App\Http\Controllers\Controller;
use App\Http\Services\CheckoutService;
use App\Http\Services\LocationService;
use App\Http\Services\PayPalService;
use App\Http\Services\TicketService;
use App\Models\Checkout;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

/**
 * @class PayPalController
 */
class PayPalProxyController extends Controller
{
    public function createOrder(
        //        string $uuid,
        //        LocationService
        //        $locationService,
        //        CheckoutService $checkoutService,
        //        PayPalService $payPalService
    ) {
        // Set up PayPal.
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        $paypalToken = $provider->getAccessToken();

        // Try to create an order.
        return $provider->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                0 => [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '100.00',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function success(
        Request $request,
        CheckoutService $checkoutService,
        TicketService $ticketService,
        string $uuid
    ) {
        // Get checkout from uuid.
        $checkout = Checkout::where('uuid', $uuid)->first();

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->capturePaymentOrder($request['token']);

        // Handle failed capture.
        if (isset($response['error'])) {
            if (isset($response['error']['links'])) {
                foreach ($response['error']['links'] as $link) {
                    if (isset($link['rel']) && $link['rel'] == 'redirect') {
                        return redirect($link['href']);
                    }
                }
            }

            return redirect()
                ->route('checkout.index', ['uuid' => $checkout->uuid])
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }

        if (isset($response['status']) && ($response['status'] == 'COMPLETED')) {
            // Calculate totals and user credit.
            $totalsAndCredit = $checkoutService->calculateTotalAndUserCredit(
                ($checkout->competition->ticket_price * $checkout->getTicketsForCompetition($checkout->competition)->count()),
                auth()->user(),
                $checkout->discount,
                $checkout->getTicketsForCompetition($checkout->competition)->count()
            );

            $checkout->payment->fill([
                'provider' => 'paypal',
                'metadata' => json_encode($response),
                'payment_reference' => $response['id'],
                'credit_used' => $totalsAndCredit['creditUsed'],
                'amount' => ($response['purchase_units'][0]['payments']['captures'][0]['amount']['value'] * 100),
                'currency_code' => $response['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'],
            ]);
            $checkout->payment->user()->associate(auth()->user());
            $checkout->payment->save();

            // Update user credit amount.
            if (auth()->user()) {
                auth()->user()->update(['credit' => $totalsAndCredit['remainingCredit']]);
            }

            // Get tickets related to the checkout.
            $tickets = Ticket::whereHas('checkout', function ($query) use ($checkout) {
                $query->where('id', $checkout->id);
            })->get();

            // Assign tickets.
            $ticketService->assignTickets(
                $checkout->competition,
                $tickets,
                $checkout->payment,
                null,
                $checkout
            );

            // Mark that the checkout was completed.
            $checkout->update(['completed' => Carbon::now()]);

            // Always populate the new ticket sales amount at the end of assigning ticket.s
            $competition = $checkout->competition->fresh();
            $charityDonation = $competition->computedCharityDonationTotal;
            $totalRevenue = $competition->computedTotalRevenueMinusCharityDonation;

            $competition->update([
                'total_revenue' => $totalRevenue,
                'charity_donation_total' => $charityDonation,
            ]);

            return redirect()->route('checkout.success', ['uuid' => $checkout->uuid]);
        }

        return redirect()
            ->route('checkout.index', ['uuid' => $checkout->uuid])
            ->with('error', $response['message'] ?? 'Something went wrong.');
    }
}
