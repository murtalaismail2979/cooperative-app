<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\MonthlySaving;
use App\Models\RegistrationFee;
use App\Models\RegistrationFeePayment;
use App\Models\User;
use App\Services\LoanService;
use App\Services\RegistrationFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialLedgerEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;
    protected User $admin;
    protected User $treasurer;
    protected User $member;
    protected LoanService $loanService;
    protected RegistrationFeeService $regFeeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cooperative = Cooperative::create([
            'name' => 'Edge Cases Cooperative',
            'slug' => 'ECCOOP',
            'base_slot_amount' => 2000,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $this->cooperative->id,
        ]);

        $this->treasurer = User::factory()->create([
            'role' => 'treasurer',
            'cooperative_id' => $this->cooperative->id,
        ]);

        $this->member = User::factory()->create([
            'role' => 'member',
            'cooperative_id' => $this->cooperative->id,
            'is_active' => true,
            'membership_status' => 'approved',
            'member_code' => 'ECCOOP/26/0001',
        ]);
        $this->member->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        $this->loanService = app(LoanService::class);
        $this->regFeeService = app(RegistrationFeeService::class);
    }

    public function test_loan_settlement_transitions_status_to_fully_paid_and_clears_remaining_months(): void
    {
        // 100,000 principal at 20% interest = 120,000 total across 2 months (60,000/mo)
        $loan = $this->loanService->createLoan($this->member, 100000.00, '2026-01-01', 20.0, 2);

        $this->assertEquals('active', $loan->status);
        $this->assertEquals(2, $loan->remaining_months);
        $this->assertEquals(120000.00, $loan->outstanding_balance);

        // Repayment 1: 60,000
        $this->actingAs($this->admin)->post(route('admin.loans.repayment', $loan), [
            'amount' => 60000.00,
            'payment_date' => '2026-01-31',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertEquals('active', $loan->status);
        $this->assertEquals(1, $loan->remaining_months);
        $this->assertEquals(60000.00, $loan->outstanding_balance);

        // Repayment 2: 60,000 (settles entire balance)
        $this->actingAs($this->admin)->post(route('admin.loans.repayment', $loan), [
            'amount' => 60000.00,
            'payment_date' => '2026-02-28',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertEquals('fully_paid', $loan->status);
        $this->assertEquals(0, $loan->remaining_months);
        $this->assertEquals(0.00, $loan->outstanding_balance);
    }

    public function test_loan_overpayment_retains_fully_paid_status_without_negative_remaining_months(): void
    {
        // Loan total 120,000
        $loan = $this->loanService->createLoan($this->member, 100000.00, '2026-01-01', 20.0, 2);

        // First repayment: 50,000 (70,000 outstanding)
        $this->loanService->recordRepayment($loan, 50000.00, '2026-01-31');

        // Second repayment with overpayment: 80,000 (exceeds 70,000 outstanding)
        $this->actingAs($this->admin)->post(route('admin.loans.repayment', $loan), [
            'amount' => 80000.00,
            'payment_date' => '2026-02-28',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertEquals('fully_paid', $loan->status);
        $this->assertEquals(0, $loan->remaining_months);
        $this->assertLessThanOrEqual(0.00, $loan->outstanding_balance);
    }

    public function test_deleting_repayment_on_fully_paid_loan_reopens_loan_to_active(): void
    {
        $loan = $this->loanService->createLoan($this->member, 100000.00, '2026-01-01', 20.0, 2);
        $this->loanService->recordRepayment($loan, 60000.00, '2026-01-31');
        $this->loanService->recordRepayment($loan, 60000.00, '2026-02-28');

        $this->assertEquals('fully_paid', $loan->fresh()->status);

        $lastRepayment = $loan->repayments()->latest('id')->first();
        $this->assertNotNull($lastRepayment);

        // Delete the final repayment
        $this->actingAs($this->admin)->delete(route('admin.loans.repayments.destroy', [$loan, $lastRepayment]))
            ->assertRedirect();

        $loan->refresh();
        // Loan must automatically reopen to active!
        $this->assertEquals('active', $loan->status);
        $this->assertGreaterThan(0, $loan->remaining_months);
        $this->assertEquals(60000.00, $loan->outstanding_balance);
    }

    public function test_updating_repayment_amount_downwards_reopens_loan_to_active(): void
    {
        $loan = $this->loanService->createLoan($this->member, 100000.00, '2026-01-01', 20.0, 2);
        $this->loanService->recordRepayment($loan, 60000.00, '2026-01-31');
        $this->loanService->recordRepayment($loan, 60000.00, '2026-02-28');

        $this->assertEquals('fully_paid', $loan->fresh()->status);

        $lastRepayment = $loan->repayments()->latest('id')->first();

        // Admin updates repayment from 60,000 to 20,000 (leaves 40,000 unpaid)
        $this->actingAs($this->admin)->put(route('admin.loans.repayments.update', [$loan, $lastRepayment]), [
            'amount' => 20000.00,
            'payment_date' => '2026-02-28',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertEquals('active', $loan->status);
        $this->assertEquals(40000.00, $loan->outstanding_balance);
        $this->assertGreaterThanOrEqual(1, $loan->remaining_months);
    }

    public function test_non_positive_amounts_are_rejected_across_all_financial_forms(): void
    {
        $this->actingAs($this->admin);

        // 1. Loan creation with 0 amount
        $this->post(route('admin.loans.store'), [
            'user_id' => $this->member->id,
            'principal_amount' => 0.00,
            'date_granted' => '2026-01-01',
        ])->assertSessionHasErrors('principal_amount');

        // 2. Loan creation with negative amount
        $this->post(route('admin.loans.store'), [
            'user_id' => $this->member->id,
            'principal_amount' => -50000.00,
            'date_granted' => '2026-01-01',
        ])->assertSessionHasErrors('principal_amount');

        // 3. Loan repayment with 0 amount
        $loan = $this->loanService->createLoan($this->member, 50000.00, '2026-01-01');
        $this->post(route('admin.loans.repayment', $loan), [
            'amount' => 0.00,
            'payment_date' => '2026-01-15',
        ])->assertSessionHasErrors('amount');

        // 4. Loan repayment with negative amount
        $this->post(route('admin.loans.repayment', $loan), [
            'amount' => -1000.00,
            'payment_date' => '2026-01-15',
        ])->assertSessionHasErrors('amount');

        // 5. Expense creation with 0 or negative amount
        $this->post(route('admin.expenses.store'), [
            'category' => 'operational',
            'description' => 'Office repairs',
            'amount' => 0.00,
            'expense_date' => '2026-01-15',
        ])->assertSessionHasErrors('amount');

        $this->post(route('admin.expenses.store'), [
            'category' => 'operational',
            'description' => 'Office repairs',
            'amount' => -2500.00,
            'expense_date' => '2026-01-15',
        ])->assertSessionHasErrors('amount');

        // 6. Registration fee payment with negative or zero amount
        $this->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $this->member->id,
            'amount' => 0.00,
            'payment_date' => '2026-01-01',
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('amount');

        $this->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $this->member->id,
            'amount' => -500.00,
            'payment_date' => '2026-01-01',
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('amount');
    }

    public function test_registration_fee_partial_payments_and_cancellation_workflow(): void
    {
        $fee = $this->regFeeService->createObligationForMember($this->member, 10000.00);

        $this->assertEquals('unpaid', $fee->status);
        $this->assertEquals(10000.00, $fee->outstanding_balance);

        // Payment 1: 4,000 partial payment
        $response1 = $this->actingAs($this->admin)->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $this->member->id,
            'amount' => 4000.00,
            'payment_date' => '2026-01-15',
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-001',
        ]);
        $response1->assertRedirect();

        $fee->refresh();
        $this->assertEquals('partially_paid', $fee->status);
        $this->assertEquals(4000.00, $fee->total_paid);
        $this->assertEquals(6000.00, $fee->outstanding_balance);

        // Payment 2: 6,000 remaining settlement
        $response2 = $this->actingAs($this->admin)->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $this->member->id,
            'amount' => 6000.00,
            'payment_date' => '2026-02-15',
            'payment_method' => 'Cash',
        ]);
        $response2->assertRedirect();

        $fee->refresh();
        $this->assertEquals('fully_paid', $fee->status);
        $this->assertEquals(10000.00, $fee->total_paid);
        $this->assertEquals(0.00, $fee->outstanding_balance);

        // Now cancel Payment 2
        $payment2 = $fee->payments()->where('amount', 6000.00)->first();
        $this->assertNotNull($payment2);

        $cancelResponse = $this->actingAs($this->admin)->post(route('admin.registration-fees.payment.cancel', $payment2), [
            'cancellation_reason' => 'Duplicate payment error',
        ]);
        $cancelResponse->assertRedirect();

        $payment2->refresh();
        $this->assertEquals('cancelled', $payment2->status);

        $fee->refresh();
        // Fee status must automatically drop back to partially_paid!
        $this->assertEquals('partially_paid', $fee->status);
        $this->assertEquals(4000.00, $fee->total_paid);
        $this->assertEquals(6000.00, $fee->outstanding_balance);
    }

    public function test_member_reconciliation_updates_fee_status_and_records_audit_trail(): void
    {
        $this->regFeeService->createObligationForMember($this->member, 10000.00);

        $response = $this->actingAs($this->admin)->post(route('admin.registration-fees.reconcile', $this->member), [
            'status' => 'fully_paid',
            'fee_amount' => 10000.00,
            'total_paid' => 10000.00,
            'notes' => 'Audited and verified legacy paper receipt',
        ]);
        $response->assertRedirect();

        $fee = $this->member->fresh()->registrationFee;
        $this->assertEquals('fully_paid', $fee->status);
        $this->assertEquals(10000.00, $fee->total_paid);
        $this->assertEquals(0.00, $fee->outstanding_balance);
        $this->assertStringContainsString('Audited and verified', $fee->notes);
    }
}
