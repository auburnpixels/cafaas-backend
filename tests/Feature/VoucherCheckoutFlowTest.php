<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_validation_endpoint_returns_success_for_valid_voucher(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id, 'ticket_price' => 1000]);
        $voucher = Voucher::factory()->fixedAmount(5)->create([
            'user_id' => $host->id,
            'scope' => Voucher::SCOPE_HOST_SPECIFIC,
        ]);

        $response = $this->postJson('/api/vouchers/validate', [
            'code' => $voucher->code,
            'competition_id' => $competition->id,
            'email' => 'test@example.com',
            'ticket_count' => 10,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Voucher applied successfully!',
            ])
            ->assertJsonStructure([
                'voucher' => [
                    'code',
                    'type',
                    'discount_amount',
                    'discount_amount_formatted',
                ],
            ]);
    }

    public function test_voucher_validation_endpoint_returns_error_for_invalid_voucher(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);

        $response = $this->postJson('/api/vouchers/validate', [
            'code' => 'INVALID123',
            'competition_id' => $competition->id,
            'email' => 'test@example.com',
            'ticket_count' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired voucher code.',
            ]);
    }

    public function test_md5_email_voucher_works_correctly(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id, 'ticket_price' => 1000]);
        $email = 'test@example.com';
        $validCode = substr(md5(strtolower($email)), 0, 12);

        $voucher = Voucher::factory()->md5EmailValidation()->create([
            'code' => $validCode,
            'user_id' => $host->id,
            'scope' => Voucher::SCOPE_HOST_SPECIFIC,
            'type' => Voucher::TYPE_PERCENTAGE,
            'value' => 100, // 100% off
        ]);

        $response = $this->postJson('/api/vouchers/validate', [
            'code' => $validCode,
            'competition_id' => $competition->id,
            'email' => $email,
            'ticket_count' => 10,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_md5_email_voucher_fails_with_wrong_email(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);
        $correctEmail = 'correct@example.com';
        $wrongEmail = 'wrong@example.com';
        $validCode = substr(md5(strtolower($correctEmail)), 0, 12);

        $voucher = Voucher::factory()->md5EmailValidation()->create([
            'code' => $validCode,
            'user_id' => $host->id,
            'scope' => Voucher::SCOPE_HOST_SPECIFIC,
        ]);

        $response = $this->postJson('/api/vouchers/validate', [
            'code' => $validCode,
            'competition_id' => $competition->id,
            'email' => $wrongEmail,
            'ticket_count' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This voucher code is not valid for your email address.',
            ]);
    }
}
