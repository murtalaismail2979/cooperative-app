<?php

namespace Tests\Unit\Models;

use App\Models\Expense;
use App\Models\Investment;
use App\Models\InvestmentReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_investment_creation_and_attributes()
    {
        $creator = User::factory()->create(['role' => 'admin']);

        $investment = Investment::factory()->create([
            'name' => 'Poultry Farm Project',
            'capital_amount' => 500000.00,
            'total_returns' => 600000.00,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);

        $this->assertEquals('Poultry Farm Project', $investment->name);
        $this->assertEquals(500000.00, $investment->capital_amount);
        $this->assertEquals($creator->id, $investment->creator->id);
    }

    public function test_investment_returns_expenses_and_profit_accessors()
    {
        $investment = Investment::factory()->create([
            'capital_amount' => 500000.00,
            'total_returns' => 650000.00,
        ]);

        Expense::factory()->create([
            'investment_id' => $investment->id,
            'amount' => 50000.00,
            'status' => 'approved',
        ]);

        Expense::factory()->create([
            'investment_id' => $investment->id,
            'amount' => 20000.00,
            'status' => 'pending', // Pending expenses should not be deducted
        ]);

        $this->assertEquals(50000.00, $investment->approved_expenses);
        $this->assertEquals(600000.00, $investment->sharable_profit); // 650000 - 50000
        $this->assertEquals(100000.00, $investment->profit); // 600000 - 500000 capital
        $this->assertEquals(120.0, $investment->roi); // (600000 / 500000) * 100 = 120%
    }
}
