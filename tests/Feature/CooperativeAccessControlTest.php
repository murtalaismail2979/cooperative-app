<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CooperativeAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_cooperatives_link_in_sidebar_and_access_cooperative_management(): void
    {
        $coop = Cooperative::create([
            'name' => 'Test Cooperative',
            'slug' => 'TESTCOOP',
            'base_slot_amount' => 2000,
            'is_active' => true,
            'dividend_allocation_basis' => 'savings_only',
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'cooperative_id' => null,
        ]);

        // Super Admin sees sidebar link
        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee(route('admin.cooperatives.index'));
        $response->assertSee('Cooperatives');

        // Super Admin can access cooperative management
        $indexResponse = $this->actingAs($superAdmin)->get(route('admin.cooperatives.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Test Cooperative');
        $indexResponse->assertSee('TESTCOOP');
        $indexResponse->assertSee('Deactivate');

        // Super Admin can toggle activate/deactivate
        $toggleResponse = $this->actingAs($superAdmin)->post(route('admin.cooperatives.toggle', $coop));
        $toggleResponse->assertRedirect();
        $this->assertFalse($coop->fresh()->is_active);

        // Super Admin can toggle back to active
        $this->actingAs($superAdmin)->post(route('admin.cooperatives.toggle', $coop));
        $this->assertTrue($coop->fresh()->is_active);
    }

    public function test_regular_admin_cannot_see_cooperatives_link_in_sidebar(): void
    {
        $coop = Cooperative::create([
            'name' => 'Apex Horizon Multipurpose Cooperative',
            'slug' => 'AHMC',
            'base_slot_amount' => 2000,
            'is_active' => true,
            'dividend_allocation_basis' => 'savings_only',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $coop->id,
            'name' => 'Dr. Ibrahim Yusuf (Admin)',
        ]);

        // Regular admin sees their dashboard but does NOT see Cooperatives link in sidebar
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('admin.cooperatives.index'));
    }

    public function test_regular_admin_is_forbidden_from_cooperative_management_and_toggle(): void
    {
        $coop = Cooperative::create([
            'name' => 'Yusra Long-Term Development Association',
            'slug' => 'YLDA',
            'base_slot_amount' => 2000,
            'is_active' => true,
            'dividend_allocation_basis' => 'savings_only',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $coop->id,
        ]);

        // Accessing cooperatives index directly gives 403
        $indexResponse = $this->actingAs($admin)->get(route('admin.cooperatives.index'));
        $indexResponse->assertForbidden();

        // Accessing create gives 403
        $createResponse = $this->actingAs($admin)->get(route('admin.cooperatives.create'));
        $createResponse->assertForbidden();

        // Toggling cooperative status gives 403
        $toggleResponse = $this->actingAs($admin)->post(route('admin.cooperatives.toggle', $coop));
        $toggleResponse->assertForbidden();
        $this->assertTrue($coop->fresh()->is_active);
    }

    public function test_regular_admin_retains_access_to_regular_admin_functionality(): void
    {
        $coop = Cooperative::create([
            'name' => 'Yusra Long-Term Development Association',
            'slug' => 'YLDA',
            'base_slot_amount' => 2000,
            'is_active' => true,
            'dividend_allocation_basis' => 'savings_only',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $coop->id,
        ]);

        // Can access dashboard
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        // Can access members list
        $this->actingAs($admin)->get(route('admin.members.index'))->assertOk();

        // Can access savings
        $this->actingAs($admin)->get(route('admin.savings.index'))->assertOk();
    }

    public function test_dashboard_redirects_correctly_for_all_roles(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        // Super Admin gets redirected to admin dashboard
        $this->actingAs($superAdmin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        // Super Admin visiting member dashboard gets redirected to admin dashboard instead of 403
        $this->actingAs($superAdmin)->get(route('member.dashboard'))->assertRedirect(route('admin.dashboard'));

        // Regular admin gets redirected to admin dashboard
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        // Regular admin visiting member dashboard gets redirected to admin dashboard instead of 403
        $this->actingAs($admin)->get(route('member.dashboard'))->assertRedirect(route('admin.dashboard'));

        // Member gets redirected to member dashboard
        $this->actingAs($member)->get('/dashboard')->assertRedirect(route('member.dashboard'));
    }
}
