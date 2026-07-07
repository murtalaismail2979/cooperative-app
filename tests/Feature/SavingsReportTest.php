<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use App\Models\Loan;
use App\Models\Investment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_savings_report_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings'));

        $response->assertOk();
        $response->assertSee('Monthly Breakdown');
        $response->assertSee('Yearly Savings by Member');
        $response->assertSee('Lifetime Savings by Member');
    }

    public function test_admin_can_view_yearly_savings_by_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0001'
        ]);

        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // Savings in 2025
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 1500.00,
            'month' => '2025-06-01',
            'status' => 'paid',
            'payment_date' => '2025-06-05'
        ]);

        // Savings in 2026
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000.00,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'yearly']));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertSee('YLDA/26/0001');
        $response->assertSee('2025');
        $response->assertSee('2026');
        // 2025 Yearly Total: 1,500.00, Cumulative: 1,500.00
        // 2026 Yearly Total: 2,000.00, Cumulative: 3,500.00
        $response->assertSee('₦1,500.00');
        $response->assertSee('₦2,000.00');
        $response->assertSee('₦3,500.00');
    }

    public function test_admin_can_filter_yearly_savings_by_member_and_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create(['role' => 'member', 'name' => 'Alice Cooper', 'member_code' => 'YLDA/26/0001']);
        $member2 = User::factory()->create(['role' => 'member', 'name' => 'Bob Smith', 'member_code' => 'YLDA/26/0002']);

        $slot1 = SavingsSlot::create(['user_id' => $member1->id, 'slot_number' => 1, 'is_active' => true]);
        $slot2 = SavingsSlot::create(['user_id' => $member2->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member1->id,
            'savings_slot_id' => $slot1->id,
            'amount' => 1000.00,
            'month' => '2025-01-01',
            'status' => 'paid'
        ]);

        MonthlySaving::create([
            'user_id' => $member2->id,
            'savings_slot_id' => $slot2->id,
            'amount' => 2000.00,
            'month' => '2026-01-01',
            'status' => 'paid'
        ]);

        // Filter by search name
        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'yearly', 'yearly_search' => 'Alice']));
        
        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Smith');

        // Filter by year
        $response2 = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'yearly', 'yearly_year' => '2026']));
        
        $response2->assertOk();
        $response2->assertSee('Bob Smith');
        $response2->assertDontSee('Alice Cooper');
    }

    public function test_admin_can_view_lifetime_savings_by_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Charlie Brown',
            'member_code' => 'YLDA/26/0003'
        ]);

        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 3000.00,
            'month' => '2025-01-01',
            'status' => 'paid'
        ]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 4000.00,
            'month' => '2026-01-01',
            'status' => 'paid'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'lifetime']));

        $response->assertOk();
        $response->assertSee('Charlie Brown');
        $response->assertSee('YLDA/26/0003');
        $response->assertSee('2025 - 2026');
        $response->assertSee('₦7,000.00'); // Sum of both years
    }

    public function test_admin_can_view_financing_and_businesses_run_tabs_separately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        // Create a loan in June 2026
        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 10000.00,
            'total_amount' => 12000.00,
            'monthly_payment' => 2000.00,
            'duration_months' => 6,
            'remaining_months' => 6,
            'date_granted' => '2026-06-15',
            'status' => 'active'
        ]);

        // Create an investment in June 2026
        Investment::create([
            'name' => 'Poultry Farm A',
            'type' => 'agriculture',
            'capital_amount' => 50000.00,
            'total_returns' => 15000.00,
            'status' => 'active',
            'start_date' => '2026-06-01',
            'created_by' => $admin->id
        ]);

        // 1. Check Financing Run Tab
        $response1 = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'financing']));

        $response1->assertOk();
        $response1->assertSee('Financing Run');
        $response1->assertSee('June');
        $response1->assertSee('10,000.00');
        $response1->assertSee('2,000.00');
        $response1->assertDontSee('50,000.00');

        // 2. Check Businesses Run Tab
        $response2 = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'businesses']));

        $response2->assertOk();
        $response2->assertSee('Businesses Run');
        $response2->assertSee('June');
        $response2->assertSee('50,000.00');
        $response2->assertDontSee('10,000.00');
    }

    public function test_admin_can_view_running_charges_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'John Doe',
            'member_code' => 'YLDA/26/0004'
        ]);

        \App\Models\RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 500.00,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-10',
            'recorded_by' => $admin->id
        ]);

        // Access charges tab
        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', [
                'tab' => 'charges',
                'charges_search' => 'John',
                'charges_year' => '2026',
                'charges_month' => '6'
            ]));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertSee('YLDA/26/0004');
        $response->assertSee('June');
        $response->assertSee('2026');
        $response->assertSee('500.00');
        $response->assertSee('Paid');
    }

    public function test_business_report_only_shows_months_when_investments_started(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create an investment starting in May 2026
        Investment::create([
            'name' => 'Logistics B',
            'type' => 'transport',
            'capital_amount' => 30000.00,
            'total_returns' => 8000.00,
            'status' => 'active',
            'start_date' => '2026-05-10',
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'businesses']));

        $response->assertOk();
        $response->assertSee('May');
        // It should NOT see June or July as no investments started in those months
        $response->assertDontSee('June');
        $response->assertDontSee('July');
    }

    public function test_admin_can_view_dividends_earned_by_member_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0001'
        ]);

        $investment = Investment::create([
            'name' => 'Poultry Farm A',
            'type' => 'agriculture',
            'capital_amount' => 50000.00,
            'total_returns' => 15000.00,
            'status' => 'completed',
            'start_date' => '2026-06-01',
            'created_by' => $admin->id
        ]);

        $dividend = \App\Models\Dividend::create([
            'investment_id' => $investment->id,
            'year' => 2026,
            'total_dividend_amount' => 10000.00,
            'total_units' => 10,
            'unit_value' => 1000.00,
            'distributed_at' => '2026-06-15 10:00:00',
            'distributed_by' => $admin->id
        ]);

        \App\Models\DividendPayout::create([
            'dividend_id' => $dividend->id,
            'user_id' => $member->id,
            'units' => 3,
            'amount' => 3000.00,
            'paid' => true,
            'payment_date' => '2026-06-15'
        ]);

        \App\Models\DividendPayout::create([
            'dividend_id' => $dividend->id,
            'user_id' => $member->id,
            'units' => 2,
            'amount' => 2000.00,
            'paid' => false
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', [
                'tab' => 'dividends',
                'dividends_search' => 'Alice'
            ]));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertSee('YLDA/26/0001');
        $response->assertSee('5,000.00'); // total earned = 3000 + 2000
        $response->assertSee('3,000.00'); // paid
        $response->assertSee('2,000.00'); // pending
        $response->assertSee('Total Dividends Shared');
    }
}
