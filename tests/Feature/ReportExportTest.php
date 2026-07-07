<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use App\Models\Loan;
use App\Models\Expense;
use App\Models\Investment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_monthly_savings_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 3000,
            'month' => '2026-06-01',
            'status' => 'paid'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', ['tab' => 'monthly']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=monthly_savings_report_' . date('Y-m-d') . '.csv');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Year', $content);
        $this->assertStringContainsString('Month', $content);
        $this->assertStringContainsString('Total Savings (₦)', $content);
        $this->assertStringContainsString('Member Count', $content);
        $this->assertStringContainsString('2026,June,3000.00,1', $content);
    }

    public function test_admin_can_export_yearly_savings_csv_with_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member1 = User::factory()->create(['role' => 'member', 'name' => 'Alice', 'member_code' => 'YLDA/26/0001']);
        $member2 = User::factory()->create(['role' => 'member', 'name' => 'Bob', 'member_code' => 'YLDA/26/0002']);

        $slot1 = SavingsSlot::create(['user_id' => $member1->id, 'slot_number' => 1, 'is_active' => true]);
        $slot2 = SavingsSlot::create(['user_id' => $member2->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member1->id,
            'savings_slot_id' => $slot1->id,
            'amount' => 1500,
            'month' => '2025-01-01',
            'status' => 'paid'
        ]);

        MonthlySaving::create([
            'user_id' => $member2->id,
            'savings_slot_id' => $slot2->id,
            'amount' => 2500,
            'month' => '2026-01-01',
            'status' => 'paid'
        ]);

        // Export only 'Alice'
        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', [
                'tab' => 'yearly',
                'yearly_search' => 'Alice'
            ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Alice', $content);
        $this->assertStringNotContainsString('Bob', $content);
        $this->assertStringContainsString('1500.00', $content);

        // Export only year '2026'
        $response2 = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', [
                'tab' => 'yearly',
                'yearly_year' => '2026'
            ]));

        $response2->assertOk();
        $content2 = $response2->streamedContent();
        $this->assertStringContainsString('Bob', $content2);
        $this->assertStringNotContainsString('Alice', $content2);
        $this->assertStringContainsString('2500.00', $content2);
    }

    public function test_admin_can_export_lifetime_savings_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Charlie', 'member_code' => 'YLDA/26/0003']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 5000,
            'month' => '2025-01-01',
            'status' => 'paid'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', ['tab' => 'lifetime']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Member Code', $content);
        $this->assertStringContainsString('Member Name', $content);
        $this->assertStringContainsString('Contribution Period', $content);
        $this->assertStringContainsString('Total Savings (₦)', $content);
        $this->assertStringContainsString('YLDA/26/0003,Charlie,2025,5000.00', $content);
    }

    public function test_admin_can_export_loans_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Danny']);
        
        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 10000,
            'total_amount' => 11000,
            'monthly_payment' => 1833.33,
            'duration_months' => 6,
            'remaining_months' => 6,
            'date_granted' => '2026-06-01',
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.loans.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Financing ID', $content);
        $this->assertStringContainsString('Member Code', $content);
        $this->assertStringContainsString('Member Name', $content);
        $this->assertStringContainsString('Principal Amount (₦)', $content);
        $this->assertStringContainsString('Total Financing Amount (₦)', $content);
        $this->assertStringContainsString('Outstanding Balance (₦)', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Danny,10000.00,11000.00,11000.00,active', $content);
    }

    public function test_admin_can_export_financial_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Investment::create([
            'name' => 'Real Estate Group',
            'type' => 'real_estate',
            'capital_amount' => 50000,
            'total_returns' => 15000,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        Expense::create([
            'title' => 'Office Supplies',
            'amount' => 2000,
            'status' => 'approved',
            'category' => 'operational',
            'description' => 'Buying notebooks',
            'expense_date' => '2026-06-01',
            'requested_by' => $admin->id
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.financial.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Financial Indicator', $content);
        $this->assertStringContainsString('Amount (₦)', $content);
        $this->assertStringContainsString('Investment Returns', $content);
        $this->assertStringContainsString('15000.00', $content);
        $this->assertStringContainsString('Total Expenses', $content);
        $this->assertStringContainsString('2000.00', $content);
    }

    public function test_admin_can_export_all_reports_as_zip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.all.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');
        
        $this->assertMatchesRegularExpression(
            '/attachment; filename=ylda_cooperative_all_reports_\d{4}-\d{2}-\d{2}_\d{6}\.zip/',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_admin_can_export_financing_and_businesses_csv_separately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

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

        Investment::create([
            'name' => 'Poultry Farm A',
            'type' => 'agriculture',
            'capital_amount' => 50000.00,
            'total_returns' => 15000.00,
            'status' => 'active',
            'start_date' => '2026-06-01',
            'created_by' => $admin->id
        ]);

        // 1. Export Financing CSV
        $response1 = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', ['tab' => 'financing']));

        $response1->assertOk();
        $response1->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response1->assertHeader('Content-Disposition', 'attachment; filename=financing_report_' . date('Y-m-d') . '.csv');

        $content1 = $response1->streamedContent();
        $this->assertStringContainsString('Year', $content1);
        $this->assertStringContainsString('Month', $content1);
        $this->assertStringContainsString('Financing Count', $content1);
        $this->assertStringContainsString('Principal Amount (₦)', $content1);
        $this->assertStringContainsString('Projected Profit (₦)', $content1);
        $this->assertStringNotContainsString('Business Count', $content1);
        $this->assertStringContainsString('2026,June,1,10000.00,2000.00', $content1);

        // 2. Export Businesses CSV
        $response2 = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', ['tab' => 'businesses']));

        $response2->assertOk();
        $response2->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response2->assertHeader('Content-Disposition', 'attachment; filename=businesses_report_' . date('Y-m-d') . '.csv');

        $content2 = $response2->streamedContent();
        $this->assertStringContainsString('Year', $content2);
        $this->assertStringContainsString('Month', $content2);
        $this->assertStringContainsString('Business Count', $content2);
        $this->assertStringContainsString('Capital Invested (₦)', $content2);
        $this->assertStringNotContainsString('Financing Count', $content2);
        $this->assertStringContainsString('2026,June,1,50000.00', $content2);
    }

    public function test_admin_can_export_running_charges_csv(): void
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

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', [
                'tab' => 'charges',
                'charges_search' => 'John',
                'charges_year' => '2026',
                'charges_month' => '6'
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=running_charges_report_' . date('Y-m-d') . '.csv');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Member Code', $content);
        $this->assertStringContainsString('Member Name', $content);
        $this->assertStringContainsString('Year', $content);
        $this->assertStringContainsString('Month', $content);
        $this->assertStringContainsString('Amount (₦)', $content);
        $this->assertStringContainsString('YLDA/26/0004,"John Doe",2026,June,500.00,Paid,2026-06-10', $content);
    }

    public function test_admin_can_export_dividends_csv(): void
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

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', [
                'tab' => 'dividends',
                'dividends_search' => 'Alice'
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=dividends_report_' . date('Y-m-d') . '.csv');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Member Code', $content);
        $this->assertStringContainsString('Member Name', $content);
        $this->assertStringContainsString('Total Earned (₦)', $content);
        $this->assertStringContainsString('Total Paid (₦)', $content);
        $this->assertStringContainsString('Total Pending (₦)', $content);
        $this->assertStringContainsString('YLDA/26/0001,"Alice Cooper",3000.00,3000.00,0.00', $content);
    }
}
