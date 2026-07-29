<?php

namespace Tests\Feature;

use App\Models\Dividend;
use App\Models\Investment;
use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitDividendWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_profit_and_dividend_workflow()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Members with savings
        $m1 = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $s1 = SavingsSlot::factory()->create(['user_id' => $m1->id]);
        MonthlySaving::factory()->create([
            'user_id' => $m1->id,
            'savings_slot_id' => $s1->id,
            'amount' => 10000.00,
            'month' => '2026-01-01',
            'status' => 'paid',
        ]);

        $m2 = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $s2 = SavingsSlot::factory()->create(['user_id' => $m2->id]);
        MonthlySaving::factory()->create([
            'user_id' => $m2->id,
            'savings_slot_id' => $s2->id,
            'amount' => 10000.00,
            'month' => '2026-01-01',
            'status' => 'paid',
        ]);

        // 1. Record Investment
        $investment = Investment::factory()->create([
            'name' => 'Transport Business',
            'capital_amount' => 500000.00,
            'total_returns' => 700000.00,
            'start_date' => '2026-01-10',
            'status' => 'completed',
        ]);

        // 2. Distribute dividend (Sharable profit = 200,000)
        $response = $this->post(route('admin.dividends.store'), [
            'investment_id' => $investment->id,
            'total_dividend_amount' => 200000.00,
        ]);

        $dividend = Dividend::where('investment_id', $investment->id)->first();
        $this->assertNotNull($dividend);
        $response->assertRedirect(route('admin.dividends.show', $dividend));

        // 3. Verify dividend details in DB
        $this->assertDatabaseHas('dividends', [
            'investment_id' => $investment->id,
            'original_sharable_profit' => 200000.00,
            'cooperative_amount' => 10000.00, // 5%
            'management_amount' => 10000.00,  // 5%
            'member_distribution_pool' => 180000.00, // 90%
        ]);

        // 4. Financial year reconciliation preview index
        $this->get(route('admin.dividends.reconciliation.index', ['year' => 2026]))->assertStatus(200);

        // Process reconciliation
        $processRes = $this->post(route('admin.dividends.reconciliation.process', 2026), [
            'notes' => 'Year end reconciliation approved',
        ]);
        $processRes->assertRedirect();

        $this->assertDatabaseHas('financial_year_reconciliations', [
            'year' => 2026,
            'status' => 'approved',
        ]);

        // Now show page renders finalized report (200 OK)
        $this->get(route('admin.dividends.reconciliation.show', 2026))->assertStatus(200);

        // 5. Lock financial year
        $lockRes = $this->post(route('admin.dividends.reconciliation.lock', 2026));
        $lockRes->assertRedirect();
        $this->assertDatabaseHas('financial_years', [
            'year' => 2026,
            'is_closed' => 1,
        ]);
    }
}
