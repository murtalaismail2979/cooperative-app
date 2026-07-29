<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\RegistrationFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFeeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RegistrationFeeService $feeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->feeService = new RegistrationFeeService();
    }

    public function test_update_fee_amount_and_obligation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->feeService->updateFeeAmount(10000.00, 'Increased fee', $admin->id);
        $this->assertEquals(10000.00, $this->feeService->getCurrentFeeAmount());

        $member = User::factory()->create(['role' => 'member']);
        $fee = $this->feeService->createObligationForMember($member);

        $this->assertEquals(10000.00, $fee->fee_amount);
        $this->assertEquals('unpaid', $fee->status);
    }

    public function test_record_and_cancel_payment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $member = User::factory()->create(['role' => 'member']);

        $fee = $this->feeService->createObligationForMember($member, 5000.00);

        $payment = $this->feeService->recordPayment($fee, 5000.00, '2026-01-10', 'Cash', 'REF123', $admin->id);

        $this->assertEquals('completed', $payment->status);
        $this->assertStringStartsWith('REC-REG-', $payment->receipt_number);
        $this->assertEquals('fully_paid', $fee->fresh()->status);

        // Cancel payment
        $this->feeService->cancelPayment($payment, 'Duplicate entry', $admin->id);
        $this->assertEquals('cancelled', $payment->fresh()->status);
        $this->assertEquals('unpaid', $fee->fresh()->status);
    }
}
