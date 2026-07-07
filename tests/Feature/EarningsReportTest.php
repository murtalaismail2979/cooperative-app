<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Dividend;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarningsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_earnings_report_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'earnings']));

        $response->assertOk();
        $response->assertSee('Cooperative & Management Earnings Report', false);
        $response->assertSee('Lifetime Cooperative Earnings');
        $response->assertSee('Lifetime Management Earnings');
    }

    public function test_earnings_report_displays_correct_business_and_financing_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        // 1. Create a business dividend record (e.g. for year 2026)
        Dividend::create([
            'year' => 2026,
            'original_sharable_profit' => 100000.00,
            'cooperative_amount' => 5000.00,
            'management_amount' => 5000.00,
            'member_distribution_pool' => 90000.00,
            'total_dividend_amount' => 90000.00,
            'total_units' => 45,
            'unit_value' => 2000.00,
            'distributed_by' => $admin->id,
            'distributed_at' => now(),
        ]);

        // 2. Create a financing loan record (e.g. for year 2026)
        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 10000.00,
            'profit_rate' => 20.00,
            'total_amount' => 12000.00,
            'monthly_payment' => 1000.00,
            'duration_months' => 12,
            'remaining_months' => 12,
            'date_granted' => '2026-04-15',
            'status' => 'active',
            'approved_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings', ['tab' => 'earnings']));

        $response->assertOk();
        
        // Assert we see calculations inside the report page
        // Business Profit: 100,000, Business Coop: 5,000, Business Mgmt: 5,000
        $response->assertSee('₦100,000.00');
        $response->assertSee('₦5,000.00');
        
        // Financing Profit: 2,000, Financing Coop: 100, Financing Mgmt: 100
        $response->assertSee('₦2,000.00');
        $response->assertSee('₦100.00');

        // Combined: Coop = 5,100, Mgmt = 5,100, Total = 10,200
        $response->assertSee('₦5,100.00');
        $response->assertSee('₦10,200.00');
    }

    public function test_admin_can_export_earnings_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        // Create a business dividend record
        Dividend::create([
            'year' => 2026,
            'original_sharable_profit' => 100000.00,
            'cooperative_amount' => 5000.00,
            'management_amount' => 5000.00,
            'member_distribution_pool' => 90000.00,
            'total_dividend_amount' => 90000.00,
            'total_units' => 45,
            'unit_value' => 2000.00,
            'distributed_by' => $admin->id,
            'distributed_at' => now(),
        ]);

        // Create a loan record
        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 10000.00,
            'profit_rate' => 20.00,
            'total_amount' => 12000.00,
            'monthly_payment' => 1000.00,
            'duration_months' => 12,
            'remaining_months' => 12,
            'date_granted' => '2026-04-15',
            'status' => 'active',
            'approved_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.savings.export', ['tab' => 'earnings']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=cooperative_and_management_earnings_report_' . date('Y-m-d') . '.csv');

        $content = $response->streamedContent();
        
        // Assert CSV headers
        $this->assertStringContainsString('Year', $content);
        $this->assertStringContainsString('Business Profit (₦)', $content);
        $this->assertStringContainsString('Business Cooperative Earnings (5%) (₦)', $content);
        $this->assertStringContainsString('Financing Profit (₦)', $content);
        $this->assertStringContainsString('Total Combined Earnings (₦)', $content);

        // Assert CSV row data
        // Format: Year, Business Profit, Business Coop, Business Mgmt, Financing Profit, Financing Coop, Financing Mgmt, Coop Total, Mgmt Total, Combined Total
        $this->assertStringContainsString('2026,100000.00,5000.00,5000.00,2000.00,100.00,100.00,5100.00,5100.00,10200.00', $content);
    }

    public function test_earnings_report_included_in_all_zip_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.all.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');

        $zipPath = $response->getFile()->getPathname();

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertNotFalse($zip->locateName('cooperative_and_management_earnings.csv'));
        $zip->close();
    }
}
