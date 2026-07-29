<?php

namespace Tests\Unit\Services;

use App\Models\Investment;
use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\User;
use App\Services\DividendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DividendServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DividendService $dividendService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dividendService = new DividendService();
    }

    public function test_distribute_dividends_calculates_correct_10_percent_deduction_and_unit_values()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $member1 = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $slot1 = SavingsSlot::factory()->create(['user_id' => $member1->id]);
        MonthlySaving::factory()->create([
            'user_id' => $member1->id,
            'savings_slot_id' => $slot1->id,
            'amount' => 10000.00, // 5 units (10000 / 2000)
            'month' => '2026-01-01',
            'status' => 'paid',
        ]);

        $member2 = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $slot2 = SavingsSlot::factory()->create(['user_id' => $member2->id]);
        MonthlySaving::factory()->create([
            'user_id' => $member2->id,
            'savings_slot_id' => $slot2->id,
            'amount' => 10000.00, // 5 units (10000 / 2000)
            'month' => '2026-01-01',
            'status' => 'paid',
        ]);

        $investment = Investment::factory()->create([
            'start_date' => '2026-01-15',
            'total_returns' => 200000.00,
        ]);

        // Total sharable profit = 100,000
        $dividend = $this->dividendService->distributeDividends($investment->id, 100000.00);

        $this->assertEquals(100000.00, $dividend->original_sharable_profit);
        $this->assertEquals(5000.00, $dividend->cooperative_amount); // 5%
        $this->assertEquals(5000.00, $dividend->management_amount); // 5%
        $this->assertEquals(90000.00, $dividend->member_distribution_pool); // 90%
        $this->assertEquals(10, $dividend->total_units); // 5 + 5
        $this->assertEquals(9000.00, $dividend->unit_value); // 90000 / 10

        $this->assertCount(2, $dividend->payouts);
        $this->assertEquals(45000.00, $dividend->payouts()->where('user_id', $member1->id)->first()->amount);
        $this->assertEquals(45000.00, $dividend->payouts()->where('user_id', $member2->id)->first()->amount);
    }

    public function test_distribute_dividends_throws_exception_if_no_savings_exist()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        User::factory()->create(['role' => 'member', 'is_active' => true]);

        $investment = Investment::factory()->create([
            'start_date' => '2026-01-15',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No savings data found for members at the time this business started.');

        $this->dividendService->distributeDividends($investment->id, 100000.00);
    }
}
