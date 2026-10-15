<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\MemberSlotHistory;
use App\Models\RegistrationFee;
use App\Models\RegistrationFeePayment;
use App\Models\RegistrationFeeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberReadmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        gc_collect_cycles();
        parent::tearDown();
    }

    public function test_admin_can_readmit_inactive_member_with_fee_waived_and_keep_code(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true, 'slug' => 'COOPTEST']);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'approved',
            'member_code' => 'COOPTEST/24/0001',
        ]);

        // Slot 1 was previously active, slot 2 inactive
        $member->savingsSlots()->createMany([
            ['slot_number' => 1, 'is_active' => false],
            ['slot_number' => 2, 'is_active' => false],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.readmit', $member), [
            'readmission_date' => '2026-04-10',
            'slots' => 3,
            'fee_option' => 'waived',
            'code_action' => 'keep',
            'notes' => 'Exco meeting minutes #45',
        ]);

        $response->assertSessionHas('success');
        $member->refresh();

        $this->assertTrue((bool) $member->is_active);
        $this->assertEquals('approved', $member->membership_status);
        $this->assertEquals('COOPTEST/24/0001', $member->member_code);
        $this->assertEquals($admin->id, $member->approved_by);

        // Check active slots count = 3
        $this->assertEquals(3, $member->savingsSlots()->where('is_active', true)->count());
        $this->assertTrue((bool) $member->savingsSlots()->where('slot_number', 1)->first()->is_active);
        $this->assertTrue((bool) $member->savingsSlots()->where('slot_number', 2)->first()->is_active);
        $this->assertTrue((bool) $member->savingsSlots()->where('slot_number', 3)->first()->is_active);

        // Check slot history logged
        $history = MemberSlotHistory::where('user_id', $member->id)->latest()->first();
        $this->assertNotNull($history);
        $this->assertEquals(0, $history->previous_slots);
        $this->assertEquals(3, $history->current_slots);
        $this->assertStringContainsString('Re-admitted to cooperative on 2026-04-10', $history->reason);
        $this->assertStringContainsString('Exco meeting minutes #45', $history->reason);

        // Fee was waived, so no registration fee obligation created
        $this->assertNull($member->registrationFee);
    }

    public function test_admin_can_readmit_inactive_member_with_standard_fee_and_recorded_payment(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true, 'slug' => 'COOPTEST']);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        RegistrationFeeSetting::create([
            'amount' => 1500.00,
            'updated_by' => $admin->id,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'approved',
            'member_code' => 'COOPTEST/24/0002',
        ]);

        // Prior registration fee and payment when they originally joined
        $existingFee = RegistrationFee::create([
            'user_id' => $member->id,
            'fee_amount' => 1000.00,
            'total_paid' => 1000.00,
            'status' => 'fully_paid',
        ]);
        RegistrationFeePayment::create([
            'registration_fee_id' => $existingFee->id,
            'user_id' => $member->id,
            'receipt_number' => 'REC-INIT-001',
            'amount' => 1000.00,
            'payment_date' => '2024-01-10',
            'payment_method' => 'Cash',
            'status' => 'completed',
            'recorded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.readmit', $member), [
            'readmission_date' => '2026-05-01',
            'slots' => 1,
            'fee_option' => 'standard',
            'payment_method' => 'Cash',
            'reference_number' => 'RECEIPT-9988',
            'code_action' => 'keep',
        ]);

        $response->assertSessionHas('success');
        $member->refresh();
        $existingFee->refresh();

        $this->assertTrue((bool) $member->is_active);
        // Prior 1000 + new 1500 = 2500
        $this->assertEquals(2500.00, (float) $existingFee->fee_amount);
        $this->assertEquals(2500.00, (float) $existingFee->total_paid);
        $this->assertEquals('fully_paid', $existingFee->status);

        $payment = RegistrationFeePayment::where('registration_fee_id', $existingFee->id)
            ->where('reference_number', 'RECEIPT-9988')
            ->first();
        $this->assertNotNull($payment);
        $this->assertEquals(1500.00, (float) $payment->amount);
        $this->assertEquals('Cash', $payment->payment_method);
        $this->assertEquals('completed', $payment->status);
    }

    public function test_admin_can_readmit_inactive_member_with_custom_fee_and_pending_payment(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true, 'slug' => 'COOPTEST']);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'approved',
            'member_code' => 'COOPTEST/24/0003',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.readmit', $member), [
            'readmission_date' => '2026-05-15',
            'slots' => 2,
            'fee_option' => 'custom',
            'custom_fee' => 3000.00,
            'payment_method' => 'Pending',
            'code_action' => 'keep',
        ]);

        $response->assertSessionHas('success');
        $member->refresh();

        $this->assertTrue((bool) $member->is_active);
        $fee = $member->registrationFee;
        $this->assertNotNull($fee);
        $this->assertEquals(3000.00, (float) $fee->fee_amount);
        $this->assertEquals(0.00, (float) $fee->total_paid);
        $this->assertEquals('unpaid', $fee->status);
        $this->assertEquals(3000.00, $fee->outstanding_balance);
    }

    public function test_admin_can_readmit_with_new_member_code_generation(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true, 'slug' => 'MYCOOP']);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'approved',
            'member_code' => 'MYCOOP/23/0001',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.readmit', $member), [
            'readmission_date' => '2026-06-01',
            'slots' => 1,
            'fee_option' => 'waived',
            'code_action' => 'new',
        ]);

        $response->assertSessionHas('success');
        $member->refresh();

        $this->assertTrue((bool) $member->is_active);
        $this->assertNotEquals('MYCOOP/23/0001', $member->member_code);
        $this->assertStringStartsWith('MYCOOP/26/', $member->member_code);
    }

    public function test_admin_cannot_readmit_member_of_another_cooperative(): void
    {
        $coopA = Cooperative::factory()->create(['is_active' => true]);
        $coopB = Cooperative::factory()->create(['is_active' => true]);

        $adminA = User::factory()->create([
            'cooperative_id' => $coopA->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $memberB = User::factory()->create([
            'cooperative_id' => $coopB->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'approved',
        ]);

        $response = $this->actingAs($adminA)->post(route('admin.members.readmit', $memberB), [
            'readmission_date' => '2026-06-01',
            'slots' => 1,
            'fee_option' => 'waived',
            'code_action' => 'keep',
        ]);

        $response->assertStatus(403);
        $memberB->refresh();
        $this->assertFalse((bool) $memberB->is_active);
    }

    public function test_super_admin_can_readmit_member_across_cooperatives(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true, 'slug' => 'SACOOP']);
        $superAdmin = User::factory()->create([
            'cooperative_id' => null,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'approved',
            'member_code' => 'SACOOP/24/0010',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.members.readmit', $member), [
            'readmission_date' => '2026-07-01',
            'slots' => 2,
            'fee_option' => 'waived',
            'code_action' => 'keep',
        ]);

        $response->assertSessionHas('success');
        $member->refresh();
        $this->assertTrue((bool) $member->is_active);
    }

    public function test_cannot_readmit_already_active_member(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $activeMember = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => true,
            'membership_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.readmit', $activeMember), [
            'readmission_date' => '2026-07-01',
            'slots' => 1,
            'fee_option' => 'waived',
            'code_action' => 'keep',
        ]);

        $response->assertSessionHas('info', 'Member is already active.');
    }

    public function test_cannot_readmit_non_member_role(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $secretary = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'secretary',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.readmit', $secretary), [
            'readmission_date' => '2026-07-01',
            'slots' => 1,
            'fee_option' => 'waived',
            'code_action' => 'keep',
        ]);

        $response->assertStatus(400);
    }

    public function test_readmission_validation_rules(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
        ]);

        // Empty submission
        $response = $this->actingAs($admin)->post(route('admin.members.readmit', $member), []);
        $response->assertSessionHasErrors(['readmission_date', 'slots', 'fee_option', 'code_action']);

        // Custom fee requires custom_fee amount
        $response2 = $this->actingAs($admin)->post(route('admin.members.readmit', $member), [
            'readmission_date' => '2026-07-01',
            'slots' => 1,
            'fee_option' => 'custom',
            'code_action' => 'keep',
        ]);
        $response2->assertSessionHasErrors(['custom_fee']);
    }
}
