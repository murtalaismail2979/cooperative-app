<?php

namespace Tests\Unit\Models;

use App\Models\Expense;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_creation_casting_and_relationships()
    {
        $requester = User::factory()->create(['role' => 'treasurer']);
        $approver = User::factory()->create(['role' => 'admin']);
        $investment = Investment::factory()->create();

        $expense = Expense::factory()->create([
            'category' => 'operational',
            'description' => 'Vehicle Repair',
            'amount' => 25000.00,
            'status' => 'approved',
            'requested_by' => $requester->id,
            'approved_by' => $approver->id,
            'investment_id' => $investment->id,
        ]);

        $this->assertEquals(25000.00, $expense->amount);
        $this->assertEquals($requester->id, $expense->requester->id);
        $this->assertEquals($approver->id, $expense->approver->id);
        $this->assertEquals($investment->id, $expense->investment->id);
    }
}
