<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformChargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_platform_charges_page_and_see_sidebar_link(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $coop = Cooperative::create([
            'name' => 'Apex Horizon Multipurpose Cooperative',
            'slug' => 'AHMC',
            'base_slot_amount' => 2000,
            'monthly_charge_per_member' => 150,
            'is_active' => true,
        ]);

        User::factory()->count(10)->create([
            'role' => 'member',
            'cooperative_id' => $coop->id,
        ]);

        // Sidebar contains link for super admin
        $dashboardResponse = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee(route('admin.platform-charges.index'));
        $dashboardResponse->assertSee('Platform Charges');

        // Can access platform charges index
        $response = $this->actingAs($superAdmin)->get(route('admin.platform-charges.index'));
        $response->assertOk();
        $response->assertSee('Platform Charges');
        $response->assertSee('Cooperative Application Usage Charges');
        $response->assertSee('Apex Horizon Multipurpose Cooperative');
        $response->assertSee('10 Members');
        $response->assertSee('150.00');
    }

    public function test_system_calculates_total_charges_based_on_member_count(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // Coop 1: 10 members @ ₦100/member = ₦1,000 monthly, ₦12,000 annual
        $coop1 = Cooperative::create([
            'name' => 'Cooperative Alpha',
            'slug' => 'ALPHA',
            'base_slot_amount' => 2000,
            'monthly_charge_per_member' => 100.00,
            'is_active' => true,
        ]);
        User::factory()->count(10)->create([
            'role' => 'member',
            'cooperative_id' => $coop1->id,
        ]);

        // Coop 2: 5 members @ ₦200/member = ₦1,000 monthly, ₦12,000 annual
        $coop2 = Cooperative::create([
            'name' => 'Cooperative Beta',
            'slug' => 'BETA',
            'base_slot_amount' => 2000,
            'monthly_charge_per_member' => 200.00,
            'is_active' => true,
        ]);
        User::factory()->count(5)->create([
            'role' => 'member',
            'cooperative_id' => $coop2->id,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.platform-charges.index'));
        $response->assertOk();

        // Check cooperative individual totals:
        $response->assertSee('1,000.00'); // monthly for each
        $response->assertSee('12,000.00'); // annual for each

        // Check grand totals:
        // Total billable members: 15
        $response->assertSee('15 Members');
        // Total monthly: ₦2,000.00
        $response->assertSee('2,000.00');
        // Total annual: ₦24,000.00
        $response->assertSee('24,000.00');
    }

    public function test_super_admin_can_update_cooperative_charge(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $coop = Cooperative::create([
            'name' => 'Yusra Development Association',
            'slug' => 'YLDA',
            'base_slot_amount' => 2000,
            'monthly_charge_per_member' => 50.00,
            'is_active' => true,
        ]);

        User::factory()->count(20)->create([
            'role' => 'member',
            'cooperative_id' => $coop->id,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.platform-charges.update', $coop), [
            'monthly_charge_per_member' => 250.00,
            'billing_notes' => 'Agreed at annual management review',
        ]);

        $response->assertRedirect(route('admin.platform-charges.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cooperatives', [
            'id' => $coop->id,
            'monthly_charge_per_member' => 250.00,
            'billing_notes' => 'Agreed at annual management review',
        ]);

        $this->assertEquals(250.00, $coop->fresh()->monthly_charge_per_member);
        $this->assertEquals(5000.00, $coop->fresh()->total_monthly_charge); // 20 * 250
        $this->assertEquals(60000.00, $coop->fresh()->total_annual_charge); // 5000 * 12
    }

    public function test_super_admin_can_batch_update_cooperative_charges(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $coop1 = Cooperative::create([
            'name' => 'Cooperative One',
            'slug' => 'COOP1',
            'base_slot_amount' => 2000,
            'monthly_charge_per_member' => 50.00,
            'is_active' => true,
        ]);

        $coop2 = Cooperative::create([
            'name' => 'Cooperative Two',
            'slug' => 'COOP2',
            'base_slot_amount' => 2000,
            'monthly_charge_per_member' => 80.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.platform-charges.batch'), [
            'default_rate' => 175.50,
            'cooperative_ids' => [$coop1->id, $coop2->id],
        ]);

        $response->assertRedirect(route('admin.platform-charges.index'));

        $this->assertEquals(175.50, $coop1->fresh()->monthly_charge_per_member);
        $this->assertEquals(175.50, $coop2->fresh()->monthly_charge_per_member);
    }

    public function test_regular_admin_cannot_access_platform_charges(): void
    {
        $coop = Cooperative::create([
            'name' => 'Apex Horizon',
            'slug' => 'AHMC',
            'base_slot_amount' => 2000,
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $coop->id,
        ]);

        // Regular admin cannot see sidebar link
        $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee(route('admin.platform-charges.index'));
        $dashboardResponse->assertDontSee('Platform Charges');

        // Regular admin cannot access index route directly (403 forbidden)
        $response = $this->actingAs($admin)->get(route('admin.platform-charges.index'));
        $response->assertForbidden();

        // Regular admin cannot update charges
        $updateResponse = $this->actingAs($admin)->post(route('admin.platform-charges.update', $coop), [
            'monthly_charge_per_member' => 300,
        ]);
        $updateResponse->assertForbidden();
    }

    public function test_member_cannot_access_platform_charges(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($member)->get(route('admin.platform-charges.index'));
        $response->assertForbidden();
    }
}
