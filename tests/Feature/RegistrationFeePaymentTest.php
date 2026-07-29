<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RegistrationFee;
use App\Models\RegistrationFeePayment;
use App\Services\RegistrationFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFeePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_member_creation_generates_registration_fee_obligation()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.members.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'member',
            'slots' => 1,
            'registration_fee' => 1000.00,
            'payment_method' => 'Cash',
            'nok_name' => 'Jane Doe',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Spouse',
        ]);

        $response->assertRedirect(route('admin.members.index'));

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->registrationFee);
        $this->assertEquals(1000.00, $user->registrationFee->fee_amount);
        $this->assertEquals(1000.00, $user->registrationFee->total_paid);
        $this->assertEquals('fully_paid', $user->registrationFee->status);
        $this->assertCount(1, $user->registrationFee->payments);
    }

    public function test_admin_can_specify_custom_registration_fee_when_creating_member()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.members.store'), [
            'name' => 'Custom Fee Member',
            'email' => 'customfee@example.com',
            'password' => 'password123',
            'role' => 'member',
            'slots' => 1,
            'registration_fee' => 12500.00,
            'payment_method' => 'Bank Transfer',
            'nok_name' => 'Jane Doe',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Spouse',
        ]);

        $response->assertRedirect(route('admin.members.index'));

        $user = User::where('email', 'customfee@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->registrationFee);
        $this->assertEquals(12500.00, $user->registrationFee->fee_amount);
        $this->assertEquals(12500.00, $user->registrationFee->total_paid);
        $this->assertEquals('fully_paid', $user->registrationFee->status);
    }

    public function test_admin_can_record_full_registration_fee_payment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $service = new RegistrationFeeService();
        $fee = $service->createObligationForMember($member, 10000.00);

        $response = $this->actingAs($admin)->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $member->id,
            'amount' => 10000.00,
            'payment_date' => '2026-07-18',
            'payment_method' => 'Cash',
            'reference_number' => 'REF-1001',
        ]);

        $response->assertSessionHasNoErrors();
        $fee->refresh();
        $this->assertEquals(10000.00, $fee->total_paid);
        $this->assertEquals(0.00, $fee->outstanding_balance);
        $this->assertEquals('fully_paid', $fee->status);
        $this->assertCount(1, $fee->payments);
    }

    public function test_partial_payments_automatically_update_status_and_balance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $service = new RegistrationFeeService();
        $fee = $service->createObligationForMember($member, 10000.00);

        // First Payment ₦4,000
        $this->actingAs($admin)->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $member->id,
            'amount' => 4000.00,
            'payment_date' => '2026-07-18',
            'payment_method' => 'Bank Transfer',
        ]);

        $fee->refresh();
        $this->assertEquals(4000.00, $fee->total_paid);
        $this->assertEquals(6000.00, $fee->outstanding_balance);
        $this->assertEquals('partially_paid', $fee->status);

        // Second Payment ₦3,000
        $this->actingAs($admin)->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $member->id,
            'amount' => 3000.00,
            'payment_date' => '2026-07-18',
            'payment_method' => 'Cash',
        ]);

        $fee->refresh();
        $this->assertEquals(7000.00, $fee->total_paid);
        $this->assertEquals(3000.00, $fee->outstanding_balance);
        $this->assertEquals('partially_paid', $fee->status);

        // Final Payment ₦3,000
        $this->actingAs($admin)->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $member->id,
            'amount' => 3000.00,
            'payment_date' => '2026-07-18',
            'payment_method' => 'Cash',
        ]);

        $fee->refresh();
        $this->assertEquals(10000.00, $fee->total_paid);
        $this->assertEquals(0.00, $fee->outstanding_balance);
        $this->assertEquals('fully_paid', $fee->status);
    }

    public function test_payment_cancellation_recalculates_totals_and_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $service = new RegistrationFeeService();
        $fee = $service->createObligationForMember($member, 10000.00);

        $payment = $service->recordPayment($fee, 10000.00, '2026-07-18', 'Bank Transfer', 'REF-001', $admin->id);
        $fee->refresh();
        $this->assertEquals('fully_paid', $fee->status);

        $response = $this->actingAs($admin)->post(route('admin.registration-fees.payment.cancel', $payment->id), [
            'cancellation_reason' => 'Bounced cheque',
        ]);

        $response->assertSessionHasNoErrors();
        $fee->refresh();
        $payment->refresh();

        $this->assertEquals('cancelled', $payment->status);
        $this->assertEquals(0.00, $fee->total_paid);
        $this->assertEquals('unpaid', $fee->status);
    }

    public function test_member_can_view_own_receipt()
    {
        $member = User::factory()->create(['role' => 'member']);
        $service = new RegistrationFeeService();
        $fee = $service->createObligationForMember($member, 10000.00);
        $payment = $service->recordPayment($fee, 10000.00, '2026-07-18', 'Cash');

        $response = $this->actingAs($member)->get(route('member.registration-fee.receipt', $payment->id));

        $response->assertStatus(200);
        $response->assertSee($payment->receipt_number);
        $response->assertSee($member->name);
    }

    public function test_member_cannot_view_another_members_receipt()
    {
        $member1 = User::factory()->create(['role' => 'member']);
        $member2 = User::factory()->create(['role' => 'member']);
        $service = new RegistrationFeeService();
        $fee1 = $service->createObligationForMember($member1, 10000.00);
        $payment1 = $service->recordPayment($fee1, 10000.00, '2026-07-18', 'Cash');

        $response = $this->actingAs($member2)->get(route('member.registration-fee.receipt', $payment1->id));
        $response->assertStatus(403);
    }
}
