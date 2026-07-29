<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RegistrationFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFeeDashboardAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_dashboard_displays_registration_fee_status()
    {
        $member = User::factory()->create(['role' => 'member']);
        $service = new RegistrationFeeService();
        $fee = $service->createObligationForMember($member, 10000.00);
        $service->recordPayment($fee, 4000.00, '2026-07-18', 'Cash');

        $response = $this->actingAs($member)->get(route('member.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Member Registration Fee');
        $response->assertSee('Partially Paid');
        $response->assertSee('4,000.00');
    }

    public function test_admin_dashboard_displays_registration_fee_stats()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $service = new RegistrationFeeService();
        $fee = $service->createObligationForMember($member, 10000.00);
        $service->recordPayment($fee, 10000.00, '2026-07-18', 'Cash');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Registration Fee Summary');
        $response->assertSee('10,000.00');
    }

    public function test_admin_can_export_registration_fees_csv()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Alice Smith']);
        $service = new RegistrationFeeService();
        $service->createObligationForMember($member, 10000.00);

        $response = $this->actingAs($admin)->get(route('admin.reports.savings.export', ['tab' => 'registration_fees']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Alice Smith', $content);
        $this->assertStringContainsString('Registration Fee (₦)', $content);
    }
}
