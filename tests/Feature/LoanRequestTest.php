<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Loan;
use App\Models\LoanRequest;
use App\Models\MonthlySaving;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function createMemberWithSavings(Cooperative $coop, float $savingsAmount): User
    {
        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => true,
            'membership_status' => 'approved',
        ]);

        if ($savingsAmount > 0) {
            $slot = \App\Models\SavingsSlot::create([
                'user_id' => $member->id,
                'slot_number' => 1,
                'is_active' => true,
            ]);

            MonthlySaving::create([
                'user_id' => $member->id,
                'savings_slot_id' => $slot->id,
                'amount' => $savingsAmount,
                'month' => '2026-01-01',
                'status' => 'paid',
                'payment_date' => '2026-01-15',
            ]);
        }

        return $member;
    }

    public function test_member_can_view_financing_request_screen_with_accurate_three_times_cap(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        // Member with ₦50,000 savings
        $member = $this->createMemberWithSavings($coop, 50000.00);

        $response = $this->actingAs($member)->get(route('member.loans.request'));

        $response->assertOk();
        $response->assertSee('₦50,000.00'); // Total Savings
        $response->assertSee('₦150,000.00'); // 3x Savings Max Cap
        $response->assertSee('Pre-Qualified');
    }

    public function test_member_can_submit_financing_request_within_three_times_savings(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $member = $this->createMemberWithSavings($coop, 100000.00); // 3x cap = ₦300,000

        $response = $this->actingAs($member)->post(route('member.loans.request.store'), [
            'amount_requested' => 250000.00, // Valid (<= 300k)
            'duration_months' => 12,
            'purpose' => 'Business expansion and supplies inventory',
            'agreement' => 1,
        ]);

        $response->assertRedirect(route('member.loans'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loan_requests', [
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 250000.00,
            'duration_months' => 12,
            'member_total_savings_at_request' => 100000.00,
            'max_eligible_amount_at_request' => 300000.00,
            'status' => 'pending',
        ]);
    }

    public function test_member_cannot_request_amount_exceeding_three_times_savings(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $member = $this->createMemberWithSavings($coop, 50000.00); // 3x cap = ₦150,000

        $response = $this->actingAs($member)->post(route('member.loans.request.store'), [
            'amount_requested' => 160000.00, // Exceeds ₦150,000
            'duration_months' => 12,
            'purpose' => 'Personal vehicle purchase',
            'agreement' => 1,
        ]);

        $response->assertSessionHasErrors('amount_requested');
        $this->assertDatabaseMissing('loan_requests', [
            'user_id' => $member->id,
            'amount_requested' => 160000.00,
        ]);
    }

    public function test_member_with_active_financing_cannot_submit_new_request(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $member = $this->createMemberWithSavings($coop, 100000.00);

        // Give member an active loan
        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 50000.00,
            'profit_rate' => 20.00,
            'total_amount' => 60000.00,
            'monthly_payment' => 5000.00,
            'duration_months' => 12,
            'remaining_months' => 12,
            'date_granted' => '2026-06-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($member)->post(route('member.loans.request.store'), [
            'amount_requested' => 50000.00,
            'duration_months' => 12,
            'purpose' => 'Additional funds',
            'agreement' => 1,
        ]);

        $response->assertSessionHasErrors('amount_requested');
        $this->assertDatabaseMissing('loan_requests', [
            'user_id' => $member->id,
        ]);
    }

    public function test_member_cannot_submit_duplicate_pending_requests(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $member = $this->createMemberWithSavings($coop, 100000.00);

        // Submit first request
        LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 50000.00,
            'duration_months' => 12,
            'purpose' => 'First application',
            'member_total_savings_at_request' => 100000.00,
            'max_eligible_amount_at_request' => 300000.00,
            'status' => 'pending',
        ]);

        // Attempt second request
        $response = $this->actingAs($member)->post(route('member.loans.request.store'), [
            'amount_requested' => 50000.00,
            'duration_months' => 12,
            'purpose' => 'Second application',
            'agreement' => 1,
        ]);

        $response->assertSessionHasErrors('amount_requested');
        $this->assertEquals(1, LoanRequest::where('user_id', $member->id)->count());
    }

    public function test_member_can_cancel_their_pending_request(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $member = $this->createMemberWithSavings($coop, 100000.00);

        $request = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 50000.00,
            'duration_months' => 12,
            'purpose' => 'Change of plans',
            'member_total_savings_at_request' => 100000.00,
            'max_eligible_amount_at_request' => 300000.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($member)->post(route('member.loans.request.cancel', $request->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('cancelled', $request->fresh()->status);
    }

    public function test_admin_can_view_financing_requests_index_and_details(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 80000.00);

        $loanRequest = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 150000.00,
            'duration_months' => 12,
            'purpose' => 'Equipment purchase',
            'member_total_savings_at_request' => 80000.00,
            'max_eligible_amount_at_request' => 240000.00,
            'status' => 'pending',
        ]);

        // Test Index
        $responseIndex = $this->actingAs($admin)->get(route('admin.loan-requests.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('₦150,000.00');
        $responseIndex->assertSee($member->name);

        // Test Show
        $responseShow = $this->actingAs($admin)->get(route('admin.loan-requests.show', $loanRequest->id));
        $responseShow->assertOk();
        $responseShow->assertSee('Equipment purchase');
        $responseShow->assertSee('Pre-Qualification');
    }

    public function test_admin_can_approve_request_and_automatically_grant_loan(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 100000.00);

        $loanRequest = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 200000.00,
            'duration_months' => 12,
            'purpose' => 'Store inventory stocking',
            'member_total_savings_at_request' => 100000.00,
            'max_eligible_amount_at_request' => 300000.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.loan-requests.approve', $loanRequest->id), [
            'approved_amount' => 200000.00,
            'approved_duration_months' => 12,
            'approved_profit_rate' => 20.00,
            'date_granted' => '2026-10-16',
            'admin_notes' => 'Approved by cooperative credit committee',
        ]);

        $response->assertRedirect(route('admin.loan-requests.show', $loanRequest->id));
        $response->assertSessionHas('success');

        $loanRequest->refresh();
        $this->assertEquals('approved', $loanRequest->status);
        $this->assertNotNull($loanRequest->loan_id);
        $this->assertEquals($admin->id, $loanRequest->reviewed_by);

        // Verify active loan was created in database
        $this->assertDatabaseHas('loans', [
            'id' => $loanRequest->loan_id,
            'user_id' => $member->id,
            'principal_amount' => 200000.00,
            'profit_rate' => 20.00,
            'total_amount' => 240000.00,
            'monthly_payment' => 20000.00,
            'duration_months' => 12,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_reject_request_with_explanatory_reason(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 50000.00);

        $loanRequest = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 100000.00,
            'duration_months' => 12,
            'purpose' => 'Emergency financing',
            'member_total_savings_at_request' => 50000.00,
            'max_eligible_amount_at_request' => 150000.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.loan-requests.reject', $loanRequest->id), [
            'rejection_reason' => 'Insufficient monthly savings frequency over the last 3 months.',
            'admin_notes' => 'Encouraged member to regularize contributions first.',
        ]);

        $response->assertRedirect(route('admin.loan-requests.show', $loanRequest->id));
        $response->assertSessionHas('success');

        $loanRequest->refresh();
        $this->assertEquals('rejected', $loanRequest->status);
        $this->assertEquals('Insufficient monthly savings frequency over the last 3 months.', $loanRequest->rejection_reason);
        $this->assertNull($loanRequest->loan_id);
    }

    public function test_tenant_isolation_admin_cannot_review_requests_of_another_cooperative(): void
    {
        $coopA = Cooperative::factory()->create(['is_active' => true]);
        $coopB = Cooperative::factory()->create(['is_active' => true]);

        $adminA = User::factory()->create([
            'cooperative_id' => $coopA->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $memberB = $this->createMemberWithSavings($coopB, 50000.00);
        $requestB = LoanRequest::create([
            'user_id' => $memberB->id,
            'cooperative_id' => $coopB->id,
            'amount_requested' => 100000.00,
            'duration_months' => 12,
            'purpose' => 'Coop B purpose',
            'member_total_savings_at_request' => 50000.00,
            'max_eligible_amount_at_request' => 150000.00,
            'status' => 'pending',
        ]);

        // Admin A tries to access Coop B request
        $responseShow = $this->actingAs($adminA)->get(route('admin.loan-requests.show', $requestB->id));
        $responseShow->assertStatus(403);

        $responseApprove = $this->actingAs($adminA)->post(route('admin.loan-requests.approve', $requestB->id), [
            'approved_amount' => 100000.00,
            'approved_duration_months' => 12,
            'approved_profit_rate' => 20.00,
        ]);
        $responseApprove->assertStatus(403);
    }

    public function test_profit_rate_is_five_percent_for_one_to_two_months(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 50000.00);

        // Member submits with 2 months duration
        $response = $this->actingAs($member)->post(route('member.loans.request.store'), [
            'amount_requested' => 100000.00,
            'duration_months' => 2,
            'purpose' => 'Short term 2 months financing',
            'agreement' => 1,
        ]);
        $response->assertRedirect(route('member.loans'));

        $request = LoanRequest::where('user_id', $member->id)->first();
        $this->assertEquals(5.0, $request->estimated_profit_rate);
        $this->assertEquals(5000.00, $request->estimated_profit); // 100,000 * 5% = 5,000

        // Admin approves without specifying custom profit rate -> defaults to 5%
        $this->actingAs($admin)->post(route('admin.loan-requests.approve', $request->id), [
            'approved_amount' => 100000.00,
            'approved_duration_months' => 2,
        ]);

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertEquals(5.0, $request->approved_profit_rate);

        $loan = $request->loan;
        $this->assertEquals(5.0, (float)$loan->profit_rate);
        $this->assertEquals(105000.00, (float)$loan->total_amount); // 100k + 5k
        $this->assertEquals(52500.00, (float)$loan->monthly_payment); // 105k / 2
    }

    public function test_profit_rate_is_ten_percent_for_three_to_six_months(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 50000.00);

        // Member submits with 6 months duration
        $this->actingAs($member)->post(route('member.loans.request.store'), [
            'amount_requested' => 100000.00,
            'duration_months' => 6,
            'purpose' => 'Medium term 6 months financing',
            'agreement' => 1,
        ]);

        $request = LoanRequest::where('user_id', $member->id)->first();
        $this->assertEquals(10.0, $request->estimated_profit_rate);
        $this->assertEquals(10000.00, $request->estimated_profit); // 100,000 * 10% = 10,000

        // Admin approves without specifying custom profit rate -> defaults to 10%
        $this->actingAs($admin)->post(route('admin.loan-requests.approve', $request->id), [
            'approved_amount' => 100000.00,
            'approved_duration_months' => 6,
        ]);

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertEquals(10.0, $request->approved_profit_rate);

        $loan = $request->loan;
        $this->assertEquals(10.0, (float)$loan->profit_rate);
        $this->assertEquals(110000.00, (float)$loan->total_amount); // 100k + 10k
        $this->assertEquals(18333.33, round((float)$loan->monthly_payment, 2)); // 110k / 6
    }

    public function test_profit_rate_is_twenty_percent_for_seven_to_twelve_months(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 50000.00);

        // Member submits with 12 months duration
        $this->actingAs($member)->post(route('member.loans.request.store'), [
            'amount_requested' => 100000.00,
            'duration_months' => 12,
            'purpose' => 'Standard 12 months financing',
            'agreement' => 1,
        ]);

        $request = LoanRequest::where('user_id', $member->id)->first();
        $this->assertEquals(20.0, $request->estimated_profit_rate);
        $this->assertEquals(20000.00, $request->estimated_profit); // 100,000 * 20% = 20,000

        // Admin approves without specifying custom profit rate -> defaults to 20%
        $this->actingAs($admin)->post(route('admin.loan-requests.approve', $request->id), [
            'approved_amount' => 100000.00,
            'approved_duration_months' => 12,
        ]);

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertEquals(20.0, $request->approved_profit_rate);

        $loan = $request->loan;
        $this->assertEquals(20.0, (float)$loan->profit_rate);
        $this->assertEquals(120000.00, (float)$loan->total_amount); // 100k + 20k
        $this->assertEquals(10000.00, (float)$loan->monthly_payment); // 120k / 12
    }

    public function test_admin_can_view_edit_financing_request_screen(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 60000.00);

        $request = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 120000.00,
            'duration_months' => 12,
            'purpose' => 'Business expansion',
            'member_total_savings_at_request' => 60000.00,
            'max_eligible_amount_at_request' => 180000.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.loan-requests.edit', $request->id));

        $response->assertOk();
        $response->assertSee('Edit Financing Request #' . $request->id);
        $response->assertSee('₦60,000.00'); // Verified Savings
        $response->assertSee('₦180,000.00'); // 3x Max Cap
        $response->assertSee('value="120000.00"', false);
    }

    public function test_admin_can_update_requested_amount_and_duration_of_pending_request(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        // Member with ₦100,000 savings -> 3x cap is ₦300,000
        $member = $this->createMemberWithSavings($coop, 100000.00);

        // Initially requested ₦250,000 for 12 months (20% profit tier)
        $request = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 250000.00,
            'duration_months' => 12,
            'purpose' => 'Commercial equipment purchase',
            'member_total_savings_at_request' => 100000.00,
            'max_eligible_amount_at_request' => 300000.00,
            'status' => 'pending',
        ]);

        $this->assertEquals(20.0, $request->estimated_profit_rate);
        $this->assertEquals(50000.00, $request->estimated_profit);

        // Admin edits to ₦150,000 and reduces duration to 2 months (5% profit tier)
        $response = $this->actingAs($admin)->put(route('admin.loan-requests.update', $request->id), [
            'amount_requested' => 150000.00,
            'duration_months' => 2,
            'purpose' => 'Adjusted: equipment down payment only',
            'admin_notes' => 'Reduced requested principal & tenure per committee deliberation.',
        ]);

        $response->assertRedirect(route('admin.loan-requests.show', $request->id));
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertEquals(150000.00, (float)$request->amount_requested);
        $this->assertEquals(2, $request->duration_months);
        $this->assertEquals('Adjusted: equipment down payment only', $request->purpose);
        $this->assertEquals('Reduced requested principal & tenure per committee deliberation.', $request->admin_notes);

        // Tiered profit rate automatically recalculates to 5% for 2 months
        $this->assertEquals(5.0, $request->estimated_profit_rate);
        $this->assertEquals(7500.00, $request->estimated_profit); // 150k * 5% = 7,500
        $this->assertEquals(157500.00, $request->estimated_total_amount); // 150k + 7.5k
        $this->assertEquals(78750.00, $request->estimated_monthly_payment); // 157.5k / 2
    }

    public function test_admin_cannot_update_requested_amount_exceeding_three_times_savings(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        // Member with ₦50,000 savings -> 3x cap is ₦150,000
        $member = $this->createMemberWithSavings($coop, 50000.00);

        $request = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 100000.00,
            'duration_months' => 6,
            'purpose' => 'Initial inventory',
            'member_total_savings_at_request' => 50000.00,
            'max_eligible_amount_at_request' => 150000.00,
            'status' => 'pending',
        ]);

        // Admin attempts to set amount to ₦200,000 (exceeds 3x cap of 150k)
        $response = $this->actingAs($admin)->put(route('admin.loan-requests.update', $request->id), [
            'amount_requested' => 200000.00,
            'duration_months' => 6,
        ]);

        $response->assertSessionHasErrors();

        $request->refresh();
        // Preserves original amount
        $this->assertEquals(100000.00, (float)$request->amount_requested);
    }

    public function test_admin_cannot_edit_non_pending_request(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member = $this->createMemberWithSavings($coop, 50000.00);

        $request = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 100000.00,
            'duration_months' => 6,
            'member_total_savings_at_request' => 50000.00,
            'max_eligible_amount_at_request' => 150000.00,
            'status' => 'approved',
            'approved_amount' => 100000.00,
            'approved_duration_months' => 6,
        ]);

        // Attempt to access edit page
        $response = $this->actingAs($admin)->get(route('admin.loan-requests.edit', $request->id));
        $response->assertRedirect(route('admin.loan-requests.show', $request->id));
        $response->assertSessionHasErrors('general');

        // Attempt to update directly via PUT
        $updateResponse = $this->actingAs($admin)->put(route('admin.loan-requests.update', $request->id), [
            'amount_requested' => 80000.00,
            'duration_months' => 4,
        ]);
        $updateResponse->assertSessionHasErrors('general');

        $request->refresh();
        $this->assertEquals(100000.00, (float)$request->amount_requested);
    }

    public function test_admin_cannot_edit_request_from_another_cooperative(): void
    {
        $coop1 = Cooperative::factory()->create(['is_active' => true]);
        $coop2 = Cooperative::factory()->create(['is_active' => true]);

        $admin1 = User::factory()->create([
            'cooperative_id' => $coop1->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $member2 = $this->createMemberWithSavings($coop2, 50000.00);

        $request2 = LoanRequest::create([
            'user_id' => $member2->id,
            'cooperative_id' => $coop2->id,
            'amount_requested' => 100000.00,
            'duration_months' => 6,
            'member_total_savings_at_request' => 50000.00,
            'max_eligible_amount_at_request' => 150000.00,
            'status' => 'pending',
        ]);

        // Admin from Coop 1 attempts to edit Coop 2 request
        $response = $this->actingAs($admin1)->get(route('admin.loan-requests.edit', $request2->id));
        $response->assertStatus(403);

        $updateResponse = $this->actingAs($admin1)->put(route('admin.loan-requests.update', $request2->id), [
            'amount_requested' => 80000.00,
            'duration_months' => 3,
        ]);
        $updateResponse->assertStatus(403);
    }

    public function test_member_cannot_access_admin_edit_routes(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $member = $this->createMemberWithSavings($coop, 50000.00);

        $request = LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $coop->id,
            'amount_requested' => 100000.00,
            'duration_months' => 6,
            'member_total_savings_at_request' => 50000.00,
            'max_eligible_amount_at_request' => 150000.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($member)->get(route('admin.loan-requests.edit', $request->id));
        $response->assertStatus(403);

        $putResponse = $this->actingAs($member)->put(route('admin.loan-requests.update', $request->id), [
            'amount_requested' => 80000.00,
            'duration_months' => 3,
        ]);
        $putResponse->assertStatus(403);
    }
}

