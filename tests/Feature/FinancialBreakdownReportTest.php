<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Loan;
use App\Models\Investment;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialBreakdownReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_financial_report_with_breakdown(): void
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

        // Create an investment in May 2026
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
            ->get(route('admin.reports.financial'));

        $response->assertOk();
        $response->assertSee('Financing');
        $response->assertSee('Business Activity Breakdown');
        
        // Check for periods
        $response->assertSee('June');
        $response->assertSee('2026');
        $response->assertSee('May');
        
        // June 2026: 1 loan, 10000 principal, 2000 profit, 1 investment, 50000 capital
        $response->assertSee('10,000.00');
        $response->assertSee('2,000.00');
        $response->assertSee('50,000.00');

        // May 2026: 0 loans, 0 principal, 0 profit, 1 investment, 30000 capital
        $response->assertSee('30,000.00');
    }

    public function test_admin_can_export_financial_csv_with_breakdown(): void
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

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.financial.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        
        // Assert header and summary section
        $this->assertStringContainsString('FINANCIAL SUMMARY', $content);
        $this->assertStringContainsString('"Financial Indicator","Amount (₦)"', $content);
        $this->assertStringContainsString('"Investment Returns",15000.00', $content);
        $this->assertStringContainsString('"Financing Profit",2000.00', $content);

        // Assert breakdown section
        $this->assertStringContainsString('FINANCING AND BUSINESSES BREAKDOWN', $content);
        $this->assertStringContainsString('Year,Month,"Financing Count","Principal Amount (₦)","Projected Profit (₦)","Business Count","Capital Invested (₦)"', $content);
        $this->assertStringContainsString('2026,June,1,10000.00,2000.00,1,50000.00', $content);
    }

    public function test_all_reports_zip_includes_financing_and_businesses_breakdown_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.all.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');

        $zipPath = $response->getFile()->getPathname();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        
        $this->assertNotFalse($zip->locateName('financing_and_businesses_breakdown.csv'));
        $this->assertNotFalse($zip->locateName('financial_summary.csv'));
        $this->assertNotFalse($zip->locateName('monthly_savings_breakdown.csv'));
        $this->assertNotFalse($zip->locateName('yearly_savings_by_member.csv'));
        $this->assertNotFalse($zip->locateName('lifetime_savings_by_member.csv'));
        $this->assertNotFalse($zip->locateName('running_charges_by_member.csv'));

        $zip->close();
    }
}
