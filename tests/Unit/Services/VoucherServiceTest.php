<?php

namespace Tests\Unit\Services;

use App\Http\Services\VoucherService;
use App\Models\Competition;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VoucherService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VoucherService;
    }

    public function test_validates_active_voucher_successfully(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);
        $voucher = Voucher::factory()->fixedAmount(5)->hostSpecific($host)->create();

        $result = $this->service->validateVoucher(
            $voucher->code,
            $competition->id,
            $host->id,
            'test@example.com'
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals('Voucher applied successfully!', $result['message']);
        $this->assertInstanceOf(Voucher::class, $result['voucher']);
    }

    public function test_rejects_invalid_voucher_code(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);

        $result = $this->service->validateVoucher(
            'INVALID123',
            $competition->id,
            $host->id,
            'test@example.com'
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid or expired voucher code.', $result['message']);
    }

    public function test_rejects_inactive_voucher(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);
        $voucher = Voucher::factory()->inactive()->hostSpecific($host)->create();

        $result = $this->service->validateVoucher(
            $voucher->code,
            $competition->id,
            $host->id,
            'test@example.com'
        );

        $this->assertFalse($result['valid']);
    }

    public function test_rejects_expired_voucher(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);
        $voucher = Voucher::factory()->expired()->hostSpecific($host)->create();

        $result = $this->service->validateVoucher(
            $voucher->code,
            $competition->id,
            $host->id,
            'test@example.com'
        );

        $this->assertFalse($result['valid']);
    }

    public function test_validates_md5_email_voucher_correctly(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);
        $email = 'test@example.com';
        $validCode = substr(md5(strtolower($email)), 0, 12);

        $voucher = Voucher::factory()
            ->md5EmailValidation()
            ->hostSpecific($host)
            ->create(['code' => $validCode]);

        $result = $this->service->validateVoucher(
            $validCode,
            $competition->id,
            $host->id,
            $email
        );

        $this->assertTrue($result['valid']);
    }

    public function test_rejects_md5_email_voucher_with_wrong_email(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);
        $validEmail = 'correct@example.com';
        $wrongEmail = 'wrong@example.com';
        $validCode = substr(md5(strtolower($validEmail)), 0, 12);

        $voucher = Voucher::factory()
            ->md5EmailValidation()
            ->hostSpecific($host)
            ->create(['code' => $validCode]);

        $result = $this->service->validateVoucher(
            $validCode,
            $competition->id,
            $host->id,
            $wrongEmail
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('This voucher code is not valid for your email address.', $result['message']);
    }

    public function test_rejects_voucher_for_wrong_competition(): void
    {
        $host = User::factory()->create();
        $competition1 = Competition::factory()->create(['user_id' => $host->id]);
        $competition2 = Competition::factory()->create(['user_id' => $host->id]);
        $voucher = Voucher::factory()->competitionSpecific($competition1)->create();

        $result = $this->service->validateVoucher(
            $voucher->code,
            $competition2->id,
            $host->id,
            'test@example.com'
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('This voucher is not valid for this competition.', $result['message']);
    }

    public function test_rejects_already_used_voucher(): void
    {
        $host = User::factory()->create();
        $competition = Competition::factory()->create(['user_id' => $host->id]);
        $voucher = Voucher::factory()->hostSpecific($host)->create();
        $email = 'test@example.com';

        VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'email' => $email,
        ]);

        $result = $this->service->validateVoucher(
            $voucher->code,
            $competition->id,
            $host->id,
            $email
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals('You have already used this voucher code.', $result['message']);
    }

    public function test_calculates_fixed_amount_discount_correctly(): void
    {
        $voucher = Voucher::factory()->fixedAmount(5)->create();
        $subtotal = 10000; // £100 in pence
        $ticketCount = 10;

        $discount = $this->service->calculateVoucherDiscount($voucher, $subtotal, $ticketCount);

        $this->assertEquals(500, $discount); // £5 in pence
    }

    public function test_calculates_percentage_discount_correctly(): void
    {
        $voucher = Voucher::factory()->percentage(20)->create();
        $subtotal = 10000; // £100 in pence
        $ticketCount = 10;

        $discount = $this->service->calculateVoucherDiscount($voucher, $subtotal, $ticketCount);

        $this->assertEquals(2000, $discount); // 20% of £100 = £20 in pence
    }

    public function test_fixed_amount_discount_does_not_exceed_subtotal(): void
    {
        $voucher = Voucher::factory()->fixedAmount(150)->create();
        $subtotal = 10000; // £100 in pence
        $ticketCount = 10;

        $discount = $this->service->calculateVoucherDiscount($voucher, $subtotal, $ticketCount);

        $this->assertEquals(10000, $discount); // Capped at subtotal
    }
}
