<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_registered_member_has_pending_membership_status_and_no_member_id(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'cooperative_id' => $coop->id,
            'name' => 'Applicant Member',
            'email' => 'applicant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '08012345678',
            'nok_name' => 'Kin Person',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Brother',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

        $member = User::where('email', 'applicant@example.com')->first();
        $this->assertNotNull($member);
        $this->assertEquals('pending', $member->membership_status);
        $this->assertFalse((bool) $member->is_active);
        $this->assertTrue($member->isPendingMembership());
        $this->assertFalse($member->isApprovedMembership());
        
        // Member ID must only be assigned upon management approval
        $this->assertNull($member->member_code);

        // Savings slot should be inactive
        $this->assertDatabaseHas('savings_slots', [
            'user_id' => $member->id,
            'slot_number' => 1,
            'is_active' => false,
        ]);
    }

    public function test_cooperative_admin_can_approve_member_and_assign_member_id(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true, 'slug' => 'TESTCOOP']);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'member_code' => null,
            'registration_year' => 2026,
        ]);
        $member->savingsSlots()->create([
            'slot_number' => 1,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.approve', $member));

        $response->assertSessionHas('success');
        $member->refresh();

        $this->assertEquals('approved', $member->membership_status);
        $this->assertTrue((bool) $member->is_active);
        $this->assertEquals($admin->id, $member->approved_by);
        $this->assertNotNull($member->approved_at);
        $this->assertTrue((bool) $member->savingsSlots()->first()->is_active);

        // Member ID is now generated and assigned
        $this->assertNotNull($member->member_code);
        $this->assertStringStartsWith('TESTCOOP/26/', $member->member_code);
    }

    public function test_cooperative_admin_can_reject_member(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'member_code' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.reject', $member), [
            'rejection_reason' => 'Incomplete documentation',
        ]);

        $response->assertSessionHas('success');
        $member->refresh();

        $this->assertEquals('rejected', $member->membership_status);
        $this->assertFalse((bool) $member->is_active);
        $this->assertEquals('Incomplete documentation', $member->rejection_reason);
        $this->assertNull($member->member_code);
    }

    public function test_admin_cannot_approve_member_of_different_cooperative(): void
    {
        $coopA = Cooperative::factory()->create(['is_active' => true]);
        $coopB = Cooperative::factory()->create(['is_active' => true]);

        $adminA = User::factory()->create([
            'cooperative_id' => $coopA->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $memberB = User::factory()->create([
            'cooperative_id' => $coopB->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'member_code' => null,
        ]);

        $response = $this->actingAs($adminA)->post(route('admin.members.approve', $memberB));
        $response->assertStatus(403);

        $memberB->refresh();
        $this->assertEquals('pending', $memberB->membership_status);
        $this->assertNull($memberB->member_code);
    }

    public function test_pending_members_displayed_and_filtered_in_admin_index(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $pendingMember = User::factory()->create([
            'cooperative_id' => $coop->id,
            'name' => 'Pending Applicant',
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'member_code' => null,
        ]);

        $activeMember = User::factory()->create([
            'cooperative_id' => $coop->id,
            'name' => 'Active Regular',
            'role' => 'member',
            'is_active' => true,
            'membership_status' => 'approved',
            'member_code' => 'COOP/26/0001',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.members.index', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('Pending Applicant');
        $response->assertDontSee('Active Regular');
        $response->assertSee('Pending Approval');
    }

    public function test_pending_member_dashboard_shows_application_progress_and_profile_only(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true, 'name' => 'Harmony Cooperative']);
        $applicant = User::factory()->create([
            'cooperative_id' => $coop->id,
            'name' => 'John Applicant',
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'member_code' => null,
        ]);

        $response = $this->actingAs($applicant)->get(route('member.dashboard'));
        $response->assertStatus(200);

        // Should see progress and profile
        $response->assertSee('Application Status & Progress', false);
        $response->assertSee('Application In Review');
        $response->assertSee('My Profile');
        $response->assertSee('Assigned upon management approval');
        $response->assertSee('John Applicant');
        $response->assertSee('Harmony Cooperative');

        // Should NOT see financial modules or active cards
        $response->assertDontSee('My Savings Slots');
        $response->assertDontSee('Active Financing');
        $response->assertDontSee('Recent Savings');
    }

    public function test_approved_member_dashboard_shows_full_financial_metrics(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $member = User::factory()->create([
            'cooperative_id' => $coop->id,
            'name' => 'Active Full Member',
            'role' => 'member',
            'is_active' => true,
            'membership_status' => 'approved',
            'member_code' => 'YLDA/26/0055',
        ]);

        $response = $this->actingAs($member)->get(route('member.dashboard'));
        $response->assertStatus(200);

        // Should see financial metrics
        $response->assertSee('My Savings Slots');
        $response->assertSee('Active Financing');
        $response->assertSee('Recent Savings');
        $response->assertSee('YLDA/26/0055');

        // Should NOT see applicant in review banner or admin-only running charges
        $response->assertDontSee('Application In Review');
        $response->assertDontSee('Assigned upon management approval');
        $response->assertDontSee('Running Charges Paid');
    }
}
