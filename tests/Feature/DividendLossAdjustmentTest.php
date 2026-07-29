<?php

namespace Tests\Feature;

use App\Models\Dividend;
use App\Models\DividendAdjustment;
use App\Models\FinancialYearReconciliation;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\LossCarryForward;
use App\Models\MonthlySaving;
use App\Models\User;
use App\Services\DividendReconciliationService;
use App\Services\DividendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DividendLossAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function createMemberWithSavings(string $name, string $email, float $savingsAmount = 20000): User
    {
        $member = User::factory()->create([
            'role' => 'member',
            'name' => $name,
            'email' => $email,
            'is_active' => true,
        ]);

        // Create savings slots
        $slot = $member->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        // Create monthly savings
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'month' => '2026-01-01',
            'amount' => $savingsAmount,
            'status' => 'paid',
            'payment_date' => '2026-01-05',
        ]);

        return $member;
    }

    // Scenario 1: Profit Without Loss
    public function test_profit_without_loss_calculates_normally()
    {
        $member = $this->createMemberWithSavings('Alice', 'alice@test.com', 20000);
        $admin = User::factory()->create(['role' => 'admin']);

        $inv = Investment::create([
            'name' => 'Poultry Farm A',
            'type' => 'business',
            'capital_amount' => 500000,
            'total_returns' => 1000000,
            'start_date' => '2026-01-01',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $dividendService = new DividendService();
        $dividend = $dividendService->distributeDividends($inv->id, 1000000);

        $this->assertEquals(1000000, $dividend->original_sharable_profit);
        $this->assertEquals(50000, $dividend->cooperative_amount);
        $this->assertEquals(50000, $dividend->management_amount);
        $this->assertEquals(900000, $dividend->member_distribution_pool);
    }

    // Scenario 2: Profit Followed by Loss
    public function test_profit_followed_by_loss_reconciles_against_distributed_dividends()
    {
        $member = $this->createMemberWithSavings('Bob', 'bob@test.com', 20000);
        $admin = User::factory()->create(['role' => 'admin']);

        $inv1 = Investment::create([
            'name' => 'Fish Farm 2026',
            'type' => 'business',
            'capital_amount' => 500000,
            'total_returns' => 1000000,
            'start_date' => '2026-02-01',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $dividendService = new DividendService();
        $dividend = $dividendService->distributeDividends($inv1->id, 1000000);
        $dividend->payouts()->update(['paid' => true, 'paid_date' => '2026-03-01']);

        // Later a business records a loss of 200,000
        $inv2 = Investment::create([
            'name' => 'Loss Business 2026',
            'type' => 'business',
            'capital_amount' => 300000,
            'total_returns' => 100000, // Losses 200k
            'start_date' => '2026-05-01',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $reconciliationService = new DividendReconciliationService();
        $summary = $reconciliationService->calculateAnnualNetResult(2026);

        $this->assertEquals(1000000, $summary['total_business_profit']);
        $this->assertEquals(200000, $summary['total_recognized_loss']);
        $this->assertEquals(800000, $summary['net_sharable_profit']);
        // 90% of 800,000 = 720,000 adjusted member pool
        $this->assertEquals(720000, $summary['adjusted_member_pool']);
        // Originally distributed 900,000 - 720,000 = 180,000 loss adjustment required
        $this->assertEquals(180000, $summary['total_loss_adjustment']);
    }

    // Scenario 3: Multiple Businesses Aggregation
    public function test_multiple_businesses_profits_and_losses_aggregation()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Investment::create(['name' => 'Biz 1', 'type' => 'business', 'capital_amount' => 100000, 'total_returns' => 300000, 'start_date' => '2026-01-01', 'status' => 'active', 'created_by' => $admin->id]);
        Investment::create(['name' => 'Biz 2', 'type' => 'business', 'capital_amount' => 100000, 'total_returns' => 50000, 'start_date' => '2026-02-01', 'status' => 'active', 'created_by' => $admin->id]);

        $service = new DividendReconciliationService();
        $summary = $service->calculateAnnualNetResult(2026);

        $this->assertEquals(300000, $summary['total_business_profit']);
        $this->assertEquals(50000, $summary['total_recognized_loss']);
        $this->assertEquals(250000, $summary['net_sharable_profit']);
    }

    // Scenario 4: Multiple Financing Activities
    public function test_multiple_financing_activities_aggregation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = $this->createMemberWithSavings('Charlie', 'charlie@test.com', 20000);

        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 100000,
            'profit_rate' => 10,
            'total_amount' => 110000, // 10,000 profit
            'monthly_payment' => 11000,
            'duration_months' => 10,
            'remaining_months' => 10,
            'date_granted' => '2026-03-01',
            'status' => 'active',
            'approved_by' => $admin->id,
        ]);

        $service = new DividendReconciliationService();
        $summary = $service->calculateAnnualNetResult(2026);

        $this->assertEquals(10000, $summary['total_financing_profit']);
    }

    // Scenario 5: Multiple Members Proportional Loss Adjustment
    public function test_multiple_members_proportional_loss_adjustment()
    {
        $m1 = $this->createMemberWithSavings('Member A', 'm1@test.com', 10000); // 5 units
        $m2 = $this->createMemberWithSavings('Member B', 'm2@test.com', 30000); // 15 units
        $admin = User::factory()->create(['role' => 'admin']);

        $inv = Investment::create(['name' => 'Coop Venture', 'type' => 'business', 'capital_amount' => 500000, 'total_returns' => 1000000, 'start_date' => '2026-01-01', 'status' => 'active', 'created_by' => $admin->id]);

        $divService = new DividendService();
        $dividend = $divService->distributeDividends($inv->id, 1000000);
        $dividend->payouts()->update(['paid' => true, 'paid_date' => '2026-02-01']);

        // Add a 200,000 loss business
        Investment::create(['name' => 'Loss Venture', 'type' => 'business', 'capital_amount' => 200000, 'total_returns' => 0, 'start_date' => '2026-06-01', 'status' => 'active', 'created_by' => $admin->id]);

        $recService = new DividendReconciliationService();
        $preview = $recService->previewReconciliation(2026);

        // Total distributed member pool = 900,000.
        // m1 got 25% (225,000), m2 got 75% (675,000).
        // Total loss adjustment = 180,000.
        // m1 loss adjustment = 25% of 180,000 = 45,000.
        // m2 loss adjustment = 75% of 180,000 = 135,000.
        $adj1 = collect($preview['member_adjustments'])->firstWhere('user_id', $m1->id);
        $adj2 = collect($preview['member_adjustments'])->firstWhere('user_id', $m2->id);

        $this->assertEquals(225000, $adj1['original_dividend_amount']);
        $this->assertEquals(45000, $adj1['loss_adjustment_amount']);
        $this->assertEquals(180000, $adj1['final_entitlement_amount']);

        $this->assertEquals(675000, $adj2['original_dividend_amount']);
        $this->assertEquals(135000, $adj2['loss_adjustment_amount']);
        $this->assertEquals(540000, $adj2['final_entitlement_amount']);
    }

    // Scenario 6: Loss Greater Than Dividend
    public function test_loss_greater_than_dividend_does_not_create_negative_entitlements()
    {
        $member = $this->createMemberWithSavings('David', 'david@test.com', 20000);
        $admin = User::factory()->create(['role' => 'admin']);

        $inv1 = Investment::create(['name' => 'Small Profit', 'type' => 'business', 'capital_amount' => 100000, 'total_returns' => 100000, 'start_date' => '2026-01-01', 'status' => 'active', 'created_by' => $admin->id]);
        $divService = new DividendService();
        $divService->distributeDividends($inv1->id, 100000);

        // Massive loss of 500,000
        Investment::create(['name' => 'Massive Loss', 'type' => 'business', 'capital_amount' => 500000, 'total_returns' => 0, 'start_date' => '2026-07-01', 'status' => 'active', 'created_by' => $admin->id]);

        $recService = new DividendReconciliationService();
        $preview = $recService->previewReconciliation(2026);

        $adj = $preview['member_adjustments'][0];
        $this->assertGreaterThanOrEqual(0.00, $adj['final_entitlement_amount']);
        $this->assertEquals(0.00, $adj['final_entitlement_amount']);
        $this->assertEquals(400000, $preview['unabsorbed_loss']);
    }

    // Scenario 7: Partial Dividend Distribution & Outstanding Payable
    public function test_partial_dividend_distribution_and_payable_balance()
    {
        $member = $this->createMemberWithSavings('Eva', 'eva@test.com', 20000);
        $admin = User::factory()->create(['role' => 'admin']);

        $inv = Investment::create(['name' => 'Farm 2026', 'type' => 'business', 'capital_amount' => 200000, 'total_returns' => 400000, 'start_date' => '2026-01-01', 'status' => 'active', 'created_by' => $admin->id]);
        $divService = new DividendService();
        $dividend = $divService->distributeDividends($inv->id, 400000);

        // Payout created but unpaid (amount_already_paid = 0)
        $recService = new DividendReconciliationService();
        $preview = $recService->previewReconciliation(2026);

        $adj = $preview['member_adjustments'][0];
        $this->assertEquals(360000, $adj['original_dividend_amount']);
        $this0 = $this->assertEquals(0.00, $adj['amount_already_paid']);
        $this->assertEquals(360000, $adj['underpayment_amount']);
        $this->assertEquals(0.00, $adj['overpayment_amount']);
    }

    // Scenario 8: Historical Dividend Preservation & Audit Record Separation
    public function test_historical_dividends_preserved_and_adjustments_recorded_separately()
    {
        $member = $this->createMemberWithSavings('Frank', 'frank@test.com', 20000);
        $admin = User::factory()->create(['role' => 'admin']);

        $inv = Investment::create(['name' => 'Biz 2026', 'type' => 'business', 'capital_amount' => 100000, 'total_returns' => 200000, 'start_date' => '2026-01-01', 'status' => 'active', 'created_by' => $admin->id]);
        $divService = new DividendService();
        $dividend = $divService->distributeDividends($inv->id, 200000);

        $recService = new DividendReconciliationService();
        $reconciliation = $recService->processYearEndReconciliation(2026, 'Year end audit', $admin->id);

        // Assert original dividend record remains untouched
        $this->assertDatabaseHas('dividends', ['id' => $dividend->id, 'member_distribution_pool' => 180000]);

        // Assert separate adjustment record exists
        $this->assertDatabaseHas('dividend_adjustments', ['reconciliation_id' => $reconciliation->id, 'user_id' => $member->id]);
    }

    // Scenario 9: Loss Carry-Forward Mechanism
    public function test_loss_carry_forward_mechanism()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 2026 has net loss of 300,000
        Investment::create(['name' => '2026 Loss Biz', 'type' => 'business', 'capital_amount' => 300000, 'total_returns' => 0, 'start_date' => '2026-05-01', 'status' => 'active', 'created_by' => $admin->id]);

        $recService = new DividendReconciliationService();
        $recService->processYearEndReconciliation(2026, 'Closing 2026 with loss', $admin->id);

        $this->assertDatabaseHas('loss_carry_forwards', [
            'from_year' => 2026,
            'to_year' => 2027,
            'unabsorbed_loss_amount' => 300000,
            'status' => 'active',
        ]);

        // Check 2027 calculation carries forward the loss
        $summary2027 = $recService->calculateAnnualNetResult(2027);
        $this->assertEquals(300000, $summary2027['previous_carried_loss']);
        $this->assertEquals(300000, $summary2027['total_recognized_loss']);
    }

    // Scenario 10: Closed Financial Year Locking
    public function test_closed_financial_year_prevents_modification()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recService = new DividendReconciliationService();

        $recService->closeFinancialYear(2026, $admin->id);
        $this->assertTrue($recService->isYearClosed(2026));

        // Attempting to distribute dividends for 2026 investment must throw RuntimeException
        $inv = Investment::create(['name' => 'Late Biz', 'type' => 'business', 'capital_amount' => 100000, 'total_returns' => 200000, 'start_date' => '2026-01-01', 'status' => 'active', 'created_by' => $admin->id]);

        $this->expectException(\RuntimeException::class);
        $divService = new DividendService();
        $divService->distributeDividends($inv->id, 200000);
    }
}
