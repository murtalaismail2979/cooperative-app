<?php

namespace Tests\Unit\Models;

use App\Models\Dividend;
use App\Models\DividendPayout;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DividendModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dividend_creation_and_profit_sharing_accessors()
    {
        $distributor = User::factory()->create(['role' => 'admin']);
        $investment = Investment::factory()->create();

        $dividend = Dividend::factory()->create([
            'year' => 2026,
            'total_dividend_amount' => 100000.00,
            'total_units' => 10,
            'unit_value' => 9000.00,
            'distributed_by' => $distributor->id,
            'investment_id' => $investment->id,
            'original_sharable_profit' => 100000.00,
            'cooperative_amount' => 5000.00,
            'management_amount' => 5000.00,
            'member_distribution_pool' => 90000.00,
        ]);

        $this->assertEquals(100000.00, $dividend->original_sharable_profit);
        $this->assertEquals(5000.00, $dividend->cooperative_amount);
        $this->assertEquals(5000.00, $dividend->management_amount);
        $this->assertEquals(90000.00, $dividend->member_distribution_pool);
        $this->assertEquals($investment->id, $dividend->investment->id);
        $this->assertEquals($distributor->id, $dividend->distributor->id);
    }

    public function test_dividend_payouts_relationship()
    {
        $dividend = Dividend::factory()->create();
        $payout = DividendPayout::factory()->create([
            'dividend_id' => $dividend->id,
        ]);

        $this->assertTrue($dividend->payouts->contains($payout));
    }
}
