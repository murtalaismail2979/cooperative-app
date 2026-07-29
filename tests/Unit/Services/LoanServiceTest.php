<?php

namespace Tests\Unit\Services;

use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = new LoanService();
    }

    public function test_create_loan_calculates_total_amount_and_monthly_payment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $member = User::factory()->create(['role' => 'member']);

        // Principal 100,000, 20% interest rate, 10 months duration
        $loan = $this->loanService->createLoan(
            $member,
            100000.00,
            '2026-01-01',
            20.0,
            10
        );

        $this->assertEquals(120000.00, $loan->total_amount);
        $this->assertEquals(12000.00, $loan->monthly_payment);
        $this->assertEquals(10, $loan->remaining_months);
        $this->assertEquals('active', $loan->status);
    }

    public function test_record_repayment_updates_loan_balance_and_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $member = User::factory()->create(['role' => 'member']);

        $loan = $this->loanService->createLoan($member, 100000.00, '2026-01-01', 20.0, 2); // total 120,000

        $this->loanService->recordRepayment($loan, 60000.00, '2026-01-15');
        $this->assertEquals(60000.00, $loan->fresh()->outstanding_balance);
        $this->assertEquals('active', $loan->fresh()->status);

        // Second repayment fully settles loan
        $this->loanService->recordRepayment($loan, 60000.00, '2026-02-15');
        $this->assertEquals(0.00, $loan->fresh()->outstanding_balance);
        $this->assertEquals('fully_paid', $loan->fresh()->status);
        $this->assertEquals(0, $loan->fresh()->remaining_months);
    }
}
