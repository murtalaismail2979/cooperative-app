<?php

namespace Tests\Unit\Models;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_creation_attributes_and_relationships()
    {
        $user = User::factory()->create();
        $approver = User::factory()->create(['role' => 'admin']);

        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'principal_amount' => 100000.00,
            'profit_rate' => 10.0,
            'total_amount' => 110000.00,
            'monthly_payment' => 11000.00,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'approved_by' => $approver->id,
        ]);

        $this->assertEquals(100000.00, $loan->principal_amount);
        $this->assertEquals(110000.00, $loan->total_amount);
        $this->assertEquals($user->id, $loan->user->id);
        $this->assertEquals($approver->id, $loan->approver->id);
    }

    public function test_outstanding_balance_attribute_accessor()
    {
        $loan = Loan::factory()->create([
            'total_amount' => 110000.00,
        ]);

        $this->assertEquals(110000.00, $loan->outstanding_balance);

        LoanRepayment::factory()->create([
            'loan_id' => $loan->id,
            'user_id' => $loan->user_id,
            'amount' => 30000.00,
        ]);

        $loan->refresh();
        $this->assertEquals(80000.00, $loan->outstanding_balance);
    }
}
