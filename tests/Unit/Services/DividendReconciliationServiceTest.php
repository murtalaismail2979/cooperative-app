<?php

namespace Tests\Unit\Services;

use App\Models\Investment;
use App\Models\User;
use App\Services\DividendReconciliationService;
use App\Services\DividendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DividendReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DividendReconciliationService $reconciliationService;
    protected DividendService $dividendService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reconciliationService = new DividendReconciliationService();
        $this->dividendService = new DividendService();
    }

    public function test_calculate_annual_net_result_and_reconciliation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Investment with loss
        Investment::factory()->create([
            'start_date' => '2026-02-01',
            'capital_amount' => 500000.00,
            'total_returns' => 300000.00, // Loss of 200,000
        ]);

        $summary = $this->reconciliationService->calculateAnnualNetResult(2026);

        $this->assertEquals(2026, $summary['year']);
        $this->assertEquals(200000.00, $summary['total_recognized_loss']);
        $this->assertEquals(0.00, $summary['net_sharable_profit']);
        $this->assertEquals(200000.00, $summary['unabsorbed_loss']);
    }

    public function test_close_and_reopen_financial_year()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->assertFalse($this->reconciliationService->isYearClosed(2026));

        $fy = $this->reconciliationService->closeFinancialYear(2026, $admin->id);
        $this->assertTrue($fy->is_closed);
        $this->assertTrue($this->reconciliationService->isYearClosed(2026));

        $reopened = $this->reconciliationService->reopenFinancialYear(2026);
        $this->assertFalse($reopened->is_closed);
        $this->assertFalse($this->reconciliationService->isYearClosed(2026));
    }
}
