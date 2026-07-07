<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Investment;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use App\Models\Dividend;
use App\Models\DividendPayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitSharingDeductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_sharing_deducts_10_percent_split_between_cooperative_and_management_example_1(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        // Create member A with savings units
        $userA = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $slotA = SavingsSlot::create(['user_id' => $userA->id, 'slot_number' => 1, 'is_active' => true]);
        MonthlySaving::create([
            'user_id' => $userA->id,
            'savings_slot_id' => $slotA->id,
            'amount' => 4000.00, // 2 units
            'month' => '2026-05-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);

        // Create business investment (type = buying_selling_goods)
        $investment = Investment::create([
            'name' => 'Rice Business',
            'type' => 'buying_selling_goods',
            'capital_amount' => 10000.00,
            'total_returns' => 1000000.00,
            'status' => 'active',
            'start_date' => '2026-05-15',
            'created_by' => $admin->id
        ]);

        // Assert sharable profit is 1,000,000
        $this->assertEquals(1000000.00, $investment->sharable_profit);

        // Distribute dividends for 1,000,000 profit
        $response = $this->actingAs($admin)
            ->post(route('admin.dividends.store'), [
                'investment_id' => $investment->id,
                'total_dividend_amount' => 1000000.00
            ]);

        $dividend = Dividend::where('investment_id', $investment->id)->first();
        $this->assertNotNull($dividend);

        $response->assertRedirect(route('admin.dividends.show', $dividend));

        // Verify calculations: Example 1
        // Sharable Profit = 1,000,000
        // Cooperative (5%) = 50,000
        // Management (5%) = 50,000
        // Member Pool (90%) = 900,000
        $this->assertEquals(1000000.00, $dividend->original_sharable_profit);
        $this->assertEquals(50000.00, $dividend->cooperative_amount);
        $this->assertEquals(50000.00, $dividend->management_amount);
        $this->assertEquals(900000.00, $dividend->member_distribution_pool);
        $this->assertEquals(900000.00, $dividend->total_dividend_amount);
        
        // Members receive their shares from the Member Distribution Pool (900,000)
        // Since there is only User A with 2 units, total units = 2. Unit value = 450,000. Payout = 900,000.
        $this->assertEquals(2, $dividend->total_units);
        $this->assertEquals(450000.00, $dividend->unit_value);

        $payoutA = DividendPayout::where('dividend_id', $dividend->id)->where('user_id', $userA->id)->first();
        $this->assertNotNull($payoutA);
        $this->assertEquals(900000.00, $payoutA->amount);
    }

    public function test_profit_sharing_deducts_10_percent_split_between_cooperative_and_management_example_2(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        // Create member A with savings units
        $userA = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $slotA = SavingsSlot::create(['user_id' => $userA->id, 'slot_number' => 1, 'is_active' => true]);
        MonthlySaving::create([
            'user_id' => $userA->id,
            'savings_slot_id' => $slotA->id,
            'amount' => 2000.00, // 1 unit
            'month' => '2026-05-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);

        // Create financing investment (type = financing)
        $investment = Investment::create([
            'name' => 'Loan Financing',
            'type' => 'financing',
            'capital_amount' => 10000.00,
            'total_returns' => 250000.00,
            'status' => 'active',
            'start_date' => '2026-05-15',
            'created_by' => $admin->id
        ]);

        // Assert sharable profit is 250,000
        $this->assertEquals(250000.00, $investment->sharable_profit);

        // Distribute dividends for 250,000 profit
        $response = $this->actingAs($admin)
            ->post(route('admin.dividends.store'), [
                'investment_id' => $investment->id,
                'total_dividend_amount' => 250000.00
            ]);

        $dividend = Dividend::where('investment_id', $investment->id)->first();
        $this->assertNotNull($dividend);

        $response->assertRedirect(route('admin.dividends.show', $dividend));

        // Verify calculations: Example 2
        // Sharable Profit = 250,000
        // Cooperative (5%) = 12,500
        // Management (5%) = 12,500
        // Member Pool (90%) = 225,000
        $this->assertEquals(250000.00, $dividend->original_sharable_profit);
        $this->assertEquals(12500.00, $dividend->cooperative_amount);
        $this->assertEquals(12500.00, $dividend->management_amount);
        $this->assertEquals(225000.00, $dividend->member_distribution_pool);
        $this->assertEquals(225000.00, $dividend->total_dividend_amount);
        
        $this->assertEquals(1, $dividend->total_units);
        $this->assertEquals(225000.00, $dividend->unit_value);

        $payoutA = DividendPayout::where('dividend_id', $dividend->id)->where('user_id', $userA->id)->first();
        $this->assertNotNull($payoutA);
        $this->assertEquals(225000.00, $payoutA->amount);
    }

    public function test_ui_and_reports_correctly_display_the_profit_sharing_deductions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        // Create member A with savings units
        $userA = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $slotA = SavingsSlot::create(['user_id' => $userA->id, 'slot_number' => 1, 'is_active' => true]);
        MonthlySaving::create([
            'user_id' => $userA->id,
            'savings_slot_id' => $slotA->id,
            'amount' => 2000.00, // 1 unit
            'month' => '2026-05-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);

        $investment = Investment::create([
            'name' => 'Farming Deal',
            'type' => 'agriculture',
            'capital_amount' => 10000.00,
            'total_returns' => 250000.00, // sharable profit = 250,000
            'status' => 'active',
            'start_date' => '2026-05-15',
            'created_by' => $admin->id
        ]);

        $this->actingAs($admin)
            ->post(route('admin.dividends.store'), [
                'investment_id' => $investment->id,
                'total_dividend_amount' => 250000.00
            ]);

        $dividend = Dividend::where('investment_id', $investment->id)->first();

        // 1. Details view page displays breakdown
        $responseShow = $this->actingAs($admin)
            ->get(route('admin.dividends.show', $dividend));
        $responseShow->assertOk();
        $responseShow->assertSee('Original Sharable Profit:</strong> ₦250,000.00', false);
        $responseShow->assertSee('Cooperative Amount (5%):</strong> ₦12,500.00', false);
        $responseShow->assertSee('Management Amount (5%):</strong> ₦12,500.00', false);
        $responseShow->assertSee('Total Deduction (10%):</strong> ₦25,000.00', false);
        $responseShow->assertSee('Net Member Pool (90%):</strong> ₦225,000.00', false);

        // 2. Savings report tab: dividends page displays summary cards
        $responseSavingsReport = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'dividends']));
        $responseSavingsReport->assertOk();
        $responseSavingsReport->assertSee('Cooperative Earnings (Deductions)');
        $responseSavingsReport->assertSee('Management Earnings (Deductions)');
        $responseSavingsReport->assertSee('Total Profit Deducted (10%)');
        $responseSavingsReport->assertSee('₦12,500.00');
        $responseSavingsReport->assertSee('₦25,000.00');

        // 3. Financial report page displays the indicators
        $responseFinancialReport = $this->actingAs($admin)
            ->get(route('admin.reports.financial'));
        $responseFinancialReport->assertOk();
        $responseFinancialReport->assertSee('Cooperative Deductions:');
        $responseFinancialReport->assertSee('Management Deductions:');
        $responseFinancialReport->assertSee('Total Deducted:');
        $responseFinancialReport->assertSee('₦12,500.00');
        $responseFinancialReport->assertSee('₦25,000.00');

        // 4. Financial CSV export reflects indicators
        $responseCsv = $this->actingAs($admin)
            ->get(route('admin.reports.financial.export'));
        $responseCsv->assertOk();
        $content = $responseCsv->streamedContent();
        $this->assertStringContainsString('"Cooperative Earnings from Profit Deductions",12500.00', $content);
        $this->assertStringContainsString('"Management Earnings from Profit Deductions",12500.00', $content);
        $this->assertStringContainsString('"Total Profit Deductions",25000.00', $content);
        $this->assertStringContainsString('"Total Distributed to Members",225000.00', $content);
    }

    public function test_backward_compatibility_with_historical_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create historical dividend where new columns are null
        $dividend = Dividend::create([
            'year' => 2026,
            'investment_id' => null,
            'total_dividend_amount' => 5000.00,
            'total_units' => 1,
            'unit_value' => 5000.00,
            'distributed_at' => now(),
            'distributed_by' => $admin->id,
            'original_sharable_profit' => null,
            'cooperative_amount' => null,
            'management_amount' => null,
            'member_distribution_pool' => null,
        ]);

        // Accessors should fall back correctly
        $this->assertEquals(5000.00, $dividend->original_sharable_profit);
        $this->assertEquals(0.00, $dividend->cooperative_amount);
        $this->assertEquals(0.00, $dividend->management_amount);
        $this->assertEquals(5000.00, $dividend->member_distribution_pool);
    }
}
