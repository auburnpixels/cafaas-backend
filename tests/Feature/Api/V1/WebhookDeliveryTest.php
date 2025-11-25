<?php

namespace Tests\Feature\Api\V1;

use App\Http\Services\WebhookService;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_webhooks_to_active_subscriptions()
    {
        Queue::fake();

        $subscription = WebhookSubscription::create([
            'user_id' => null,
            'url' => 'https://example.com/webhook',
            'events' => ['draw.completed'],
            'is_active' => true,
        ]);

        $webhookService = app(WebhookService::class);
        $webhookService->dispatch('draw.completed', [
            'raffle_id' => 'test-123',
            'draw_id' => 'draw-456',
        ]);

        Queue::assertPushed(DeliverWebhook::class);
    }

    /** @test */
    public function it_does_not_dispatch_to_inactive_subscriptions()
    {
        Queue::fake();

        WebhookSubscription::create([
            'user_id' => null,
            'url' => 'https://example.com/webhook',
            'events' => ['draw.completed'],
            'is_active' => false,
        ]);

        $webhookService = app(WebhookService::class);
        $webhookService->dispatch('draw.completed', ['test' => 'data']);

        Queue::assertNotPushed(DeliverWebhook::class);
    }

    /** @test */
    public function it_signs_webhook_payloads_correctly()
    {
        $webhookService = app(WebhookService::class);
        $payload = json_encode(['event' => 'test']);
        $secret = 'test-secret';

        $signature = $webhookService->signPayload($payload, $secret);

        $this->assertStringContainsString('t=', $signature);
        $this->assertStringContainsString('v1=', $signature);
    }

    /** @test */
    public function it_verifies_webhook_signatures()
    {
        $webhookService = app(WebhookService::class);
        $payload = json_encode(['event' => 'test']);
        $secret = 'test-secret';

        $signature = $webhookService->signPayload($payload, $secret);

        $isValid = $webhookService->verifySignature($payload, $signature, $secret);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_rejects_invalid_signatures()
    {
        $webhookService = app(WebhookService::class);
        $payload = json_encode(['event' => 'test']);
        $secret = 'test-secret';
        $invalidSignature = 't=123456, v1=invalid';

        $isValid = $webhookService->verifySignature($payload, $invalidSignature, $secret);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_increments_failure_count_on_delivery_failure()
    {
        Http::fake([
            '*' => Http::response('Error', 500),
        ]);

        $subscription = WebhookSubscription::create([
            'url' => 'https://example.com/webhook',
            'events' => ['test.event'],
            'failure_count' => 0,
        ]);

        $job = new DeliverWebhook($subscription, ['event' => 'test.event']);
        $job->handle();

        $subscription->refresh();
        $this->assertEquals(1, $subscription->failure_count);
    }

    /** @test */
    public function it_disables_subscription_after_max_failures()
    {
        Http::fake([
            '*' => Http::response('Error', 500),
        ]);

        $subscription = WebhookSubscription::create([
            'url' => 'https://example.com/webhook',
            'events' => ['test.event'],
            'failure_count' => 9,
            'is_active' => true,
        ]);

        $job = new DeliverWebhook($subscription, ['event' => 'test.event']);
        $job->handle();

        $subscription->refresh();
        $this->assertEquals(10, $subscription->failure_count);
        $this->assertFalse($subscription->is_active);
    }
}
