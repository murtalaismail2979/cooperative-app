<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_loan_and_repayment_workflow()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $member = User::factory()->create(['role' => 'member']);

        // 1. Create loan for member
        $loanData = [
            'user_id' => $member->id,
            'principal_amount' => 100000.00,
            'profit_rate' => 20.0,
            'duration_months' => 2,
            'date_granted' => '2026-01-01',
        ];

        $response = $this->post(route('admin.loans.store'), $loanData);
        $response->assertRedirect(route('admin.loans.index'));

        $loan = Loan::where('user_id', $member->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals(120000.00, $loan->total_amount);
        $this->assertEquals(60000.00, $loan->monthly_payment);
        $this->assertEquals('active', $loan->status);

        // 2. Record 1st Repayment
        $repayment1 = $this->post(route('admin.loans.repayment', $loan), [
            'amount' => 60000.00,
            'payment_date' => '2026-01-31',
        ]);
        $repayment1->assertRedirect();
        $this->assertEquals(60000.00, $loan->fresh()->outstanding_balance);
        $this->assertEquals('active', $loan->fresh()->status);

        // 3. Record 2nd Repayment (settles loan)
        $repayment2 = $this->post(route('admin.loans.repayment', $loan), [
            'amount' => 60000.00,
            'payment_date' => '2026-02-28',
        ]);
        $repayment2->assertRedirect();
        $this->assertEquals(0.00, $loan->fresh()->outstanding_balance);
        $this->assertEquals('fully_paid', $loan->fresh()->status);

        // 4. Non-existent loan repayment returns 404
        $this->post('/admin/loans/99999/repayment', [
            'amount' => 1000.00,
            'payment_date' => '2026-03-01',
        ])->assertStatus(404);
    }
}
