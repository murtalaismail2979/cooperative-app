<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\MonthlySaving;
use App\Models\RegistrationFee;
use App\Models\RegistrationFeePayment;
use App\Models\RunningCharge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $coopA;
    protected Cooperative $coopB;
    protected User $adminA;
    protected User $treasurerA;
    protected User $memberA;
    protected User $adminB;
    protected User $treasurerB;
    protected User $memberB;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coopA = Cooperative::create([
            'name' => 'Cooperative Alpha',
            'slug' => 'CALPHA',
            'base_slot_amount' => 2000,
            'is_active' => true,
        ]);

        $this->coopB = Cooperative::create([
            'name' => 'Cooperative Beta',
            'slug' => 'CBETA',
            'base_slot_amount' => 2000,
            'is_active' => true,
        ]);

        $this->adminA = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $this->coopA->id,
            'email' => 'adminA@alpha.com',
        ]);

        $this->treasurerA = User::factory()->create([
            'role' => 'treasurer',
            'cooperative_id' => $this->coopA->id,
            'email' => 'treasurerA@alpha.com',
        ]);

        $this->memberA = User::factory()->create([
            'role' => 'member',
            'cooperative_id' => $this->coopA->id,
            'email' => 'memberA@alpha.com',
            'member_code' => 'CALPHA/26/0001',
            'is_active' => true,
            'membership_status' => 'approved',
        ]);
        $this->memberA->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        $this->adminB = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $this->coopB->id,
            'email' => 'adminB@beta.com',
        ]);

        $this->treasurerB = User::factory()->create([
            'role' => 'treasurer',
            'cooperative_id' => $this->coopB->id,
            'email' => 'treasurerB@beta.com',
        ]);

        $this->memberB = User::factory()->create([
            'role' => 'member',
            'cooperative_id' => $this->coopB->id,
            'email' => 'memberB@beta.com',
            'member_code' => 'CBETA/26/0001',
            'is_active' => true,
            'membership_status' => 'approved',
        ]);
        $this->memberB->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'cooperative_id' => null,
            'email' => 'super@platform.com',
        ]);
    }

    public function test_admin_cannot_view_or_edit_member_from_another_cooperative(): void
    {
        // Admin A trying to edit Member B
        $response = $this->actingAs($this->adminA)->get(route('admin.members.edit', $this->memberB));
        $response->assertStatus(403);

        // Admin A trying to view slots of Member B
        $responseSlots = $this->actingAs($this->adminA)->get(route('admin.members.slots', $this->memberB));
        $responseSlots->assertStatus(403);

        // Admin A trying to update Member B details
        $responseUpdate = $this->actingAs($this->adminA)->put(route('admin.members.update', $this->memberB), [
            'name' => 'Tampered Name',
            'email' => 'memberB@beta.com',
            'role' => 'member',
            'is_active' => true,
            'slots' => 1,
            'nok_name' => 'Kin',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Brother',
        ]);
        $responseUpdate->assertStatus(403);
        $this->assertNotEquals('Tampered Name', $this->memberB->fresh()->name);

        // Admin A trying to deactivate Member B
        $responseDestroy = $this->actingAs($this->adminA)->delete(route('admin.members.destroy', $this->memberB));
        $responseDestroy->assertStatus(403);
        $this->assertTrue($this->memberB->fresh()->is_active);
    }

    public function test_admin_creating_member_forces_admin_own_cooperative_id(): void
    {
        // Admin A tries to inject cooperative_id = Coop B in POST payload
        $response = $this->actingAs($this->adminA)->post(route('admin.members.store'), [
            'name' => 'Infiltrated User',
            'email' => 'infiltrator@test.com',
            'password' => 'password123',
            'role' => 'member',
            'slots' => 1,
            'cooperative_id' => $this->coopB->id, // Malicious injection
            'nok_name' => 'Kin Person',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Sister',
        ]);

        $response->assertRedirect(route('admin.members.index'));

        $created = User::where('email', 'infiltrator@test.com')->first();
        $this->assertNotNull($created);
        // Must be forced to Coop A, NOT Coop B!
        $this->assertEquals($this->coopA->id, $created->cooperative_id);
    }

    public function test_admin_and_treasurer_cannot_access_or_repay_loans_from_another_cooperative(): void
    {
        $loanService = app(\App\Services\LoanService::class);
        $loanB = $loanService->createLoan($this->memberB, 100000.00, '2026-01-01', 20.0, 10);

        // Admin A trying to view Loan B
        $responseShowAdmin = $this->actingAs($this->adminA)->get(route('admin.loans.show', $loanB));
        $responseShowAdmin->assertStatus(403);

        // Treasurer A trying to view Loan B
        $responseShowTreasurer = $this->actingAs($this->treasurerA)->get(route('treasurer.loans.show', $loanB));
        $responseShowTreasurer->assertStatus(403);

        // Admin A trying to record repayment for Loan B
        $responseRepayAdmin = $this->actingAs($this->adminA)->post(route('admin.loans.repayment', $loanB), [
            'amount' => 12000.00,
            'payment_date' => '2026-01-15',
        ]);
        $responseRepayAdmin->assertStatus(403);

        // Treasurer A trying to record repayment for Loan B
        $responseRepayTreasurer = $this->actingAs($this->treasurerA)->post(route('treasurer.loans.repayment', $loanB), [
            'amount' => 12000.00,
            'payment_date' => '2026-01-15',
        ]);
        $responseRepayTreasurer->assertStatus(403);

        // Admin A trying to grant loan to Member B
        $responseGrant = $this->actingAs($this->adminA)->post(route('admin.loans.store'), [
            'user_id' => $this->memberB->id,
            'principal_amount' => 50000.00,
            'date_granted' => '2026-02-01',
        ]);
        $responseGrant->assertStatus(403);
    }

    public function test_admin_and_treasurer_cannot_record_savings_for_another_cooperative_member(): void
    {
        $slotB = $this->memberB->savingsSlots()->first();

        // Admin A recording savings for Member B
        $responseAdmin = $this->actingAs($this->adminA)->post(route('admin.savings.store'), [
            'member_id' => $this->memberB->id,
            'month' => '2026-01-01',
            'slots' => [$slotB->id],
            'payment_date' => '2026-01-10',
        ]);
        $responseAdmin->assertStatus(403);

        // Treasurer A recording savings for Member B
        $responseTreasurer = $this->actingAs($this->treasurerA)->post(route('treasurer.savings.store'), [
            'user_id' => $this->memberB->id,
            'month' => '2026-01-01',
            'slot_ids' => [$slotB->id],
            'payment_date' => '2026-01-10',
        ]);
        $responseTreasurer->assertStatus(403);
    }

    public function test_admin_and_treasurer_cannot_record_or_modify_running_charges_for_another_cooperative_member(): void
    {
        // Admin A recording running charges for Member B
        $responseAdmin = $this->actingAs($this->adminA)->post(route('admin.running-charges.store'), [
            'user_id' => $this->memberB->id,
            'month' => '2026-01-01',
            'amount' => 500.00,
            'payment_date' => '2026-01-10',
        ]);
        $responseAdmin->assertStatus(403);

        // Treasurer A recording running charges for Member B
        $responseTreasurer = $this->actingAs($this->treasurerA)->post(route('treasurer.running-charges.store'), [
            'user_id' => $this->memberB->id,
            'month' => '2026-01-01',
            'amount' => 500.00,
            'payment_date' => '2026-01-10',
        ]);
        $responseTreasurer->assertStatus(403);

        // Existing running charge of Member B
        $chargeB = RunningCharge::create([
            'user_id' => $this->memberB->id,
            'month' => '2026-01-01',
            'amount' => 500.00,
            'status' => 'paid',
            'payment_date' => '2026-01-10',
        ]);

        $responseEdit = $this->actingAs($this->adminA)->get(route('admin.running-charges.edit', $chargeB));
        $responseEdit->assertStatus(403);

        $responseDelete = $this->actingAs($this->adminA)->delete(route('admin.running-charges.destroy', $chargeB));
        $responseDelete->assertStatus(403);
    }

    public function test_admin_and_treasurer_cannot_record_or_access_registration_fees_of_another_cooperative(): void
    {
        $regFeeService = app(\App\Services\RegistrationFeeService::class);
        $feeB = $regFeeService->createObligationForMember($this->memberB, 5000.00);

        // Admin A recording payment for Member B
        $responseAdmin = $this->actingAs($this->adminA)->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $this->memberB->id,
            'amount' => 5000.00,
            'payment_date' => '2026-01-10',
            'payment_method' => 'Cash',
        ]);
        $responseAdmin->assertStatus(403);

        // Treasurer A recording payment for Member B
        $responseTreasurer = $this->actingAs($this->treasurerA)->post(route('treasurer.registration-fees.payment.store'), [
            'user_id' => $this->memberB->id,
            'amount' => 5000.00,
            'payment_date' => '2026-01-10',
            'payment_method' => 'Cash',
        ]);
        $responseTreasurer->assertStatus(403);

        // Payment recorded by Treasurer B
        $paymentB = $regFeeService->recordPayment($feeB, 5000.00, '2026-01-10', 'Cash', null, $this->treasurerB->id);

        // Admin A trying to access receipt of payment B
        $responseReceiptAdmin = $this->actingAs($this->adminA)->get(route('admin.registration-fees.receipt', $paymentB));
        $responseReceiptAdmin->assertStatus(403);

        // Treasurer A trying to access receipt of payment B
        $responseReceiptTreasurer = $this->actingAs($this->treasurerA)->get(route('treasurer.registration-fees.receipt', $paymentB));
        $responseReceiptTreasurer->assertStatus(403);

        // Admin A trying to cancel payment B
        $responseCancel = $this->actingAs($this->adminA)->post(route('admin.registration-fees.payment.cancel', $paymentB), [
            'cancellation_reason' => 'Unauthorized cancellation attempt',
        ]);
        $responseCancel->assertStatus(403);
    }

    public function test_member_ajax_helper_endpoints_prevent_cross_tenant_information_disclosure(): void
    {
        // Admin A cannot query Member B active slots endpoint
        $responseSlots = $this->actingAs($this->adminA)->get(route('members.active-slots', $this->memberB));
        $responseSlots->assertStatus(403);

        // Treasurer A cannot query Member B savings info endpoint
        $responseSavingsInfo = $this->actingAs($this->treasurerA)->get(route('members.savings-info', $this->memberB));
        $responseSavingsInfo->assertStatus(403);

        // Admin A can query Member A active slots
        $responseOwnSlots = $this->actingAs($this->adminA)->get(route('members.active-slots', $this->memberA));
        $responseOwnSlots->assertStatus(200);

        // Treasurer A can query Member A savings info
        $responseOwnSavingsInfo = $this->actingAs($this->treasurerA)->get(route('members.savings-info', $this->memberA));
        $responseOwnSavingsInfo->assertStatus(200);
    }

    public function test_super_admin_has_unrestricted_cross_cooperative_access(): void
    {
        // Super Admin can edit Member A and Member B
        $this->actingAs($this->superAdmin)->get(route('admin.members.edit', $this->memberA))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('admin.members.edit', $this->memberB))->assertStatus(200);

        // Super Admin can view loans of any cooperative
        $loanService = app(\App\Services\LoanService::class);
        $loanA = $loanService->createLoan($this->memberA, 100000.00, '2026-01-01', 20.0, 10);
        $loanB = $loanService->createLoan($this->memberB, 100000.00, '2026-01-01', 20.0, 10);

        $this->actingAs($this->superAdmin)->get(route('admin.loans.show', $loanA))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('admin.loans.show', $loanB))->assertStatus(200);
    }
}
