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

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

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

    public function test_pending_member_cannot_login_to_account(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $applicant = User::factory()->create([
            'cooperative_id' => $coop->id,
            'email' => 'pending@example.com',
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'pending@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertTrue(str_contains(
            session('errors')->first('email'),
            'pending approval'
        ));
    }

    public function test_rejected_member_cannot_login_to_account(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $rejected = User::factory()->create([
            'cooperative_id' => $coop->id,
            'email' => 'rejected@example.com',
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'rejected',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'rejected@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertTrue(str_contains(
            session('errors')->first('email'),
            'not approved'
        ));
    }

    public function test_approved_member_can_login_successfully(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);
        $approved = User::factory()->create([
            'cooperative_id' => $coop->id,
            'email' => 'approved@example.com',
            'role' => 'member',
            'is_active' => true,
            'membership_status' => 'approved',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'approved@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($approved);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_pending_member_cannot_access_member_dashboard_if_session_existed(): void
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
        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
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

    public function test_approving_member_activates_all_requested_savings_slots(): void
    {
        $coop = Cooperative::factory()->create([
            'is_active' => true,
            'slug' => 'SAVINGSCOOP',
            'base_slot_amount' => 5000,
        ]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $applicant = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'member_code' => null,
            'registration_year' => 2026,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $applicant->savingsSlots()->create([
                'slot_number' => $i,
                'is_active' => false,
            ]);
        }

        $this->assertEquals(0, $applicant->savingsSlots()->where('is_active', true)->count());
        $this->assertEquals(4, $applicant->savingsSlots()->where('is_active', false)->count());

        $response = $this->actingAs($admin)->post(route('admin.members.approve', $applicant));
        $response->assertSessionHas('success');

        $applicant->refresh();
        $this->assertEquals('approved', $applicant->membership_status);
        $this->assertTrue((bool) $applicant->is_active);
        $this->assertEquals(4, $applicant->savingsSlots()->where('is_active', true)->count());
    }

    public function test_pending_member_displays_requested_slots_and_monthly_amount_in_admin_list(): void
    {
        $coop = Cooperative::factory()->create([
            'is_active' => true,
            'base_slot_amount' => 5000,
        ]);
        $admin = User::factory()->create([
            'cooperative_id' => $coop->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $applicant = User::factory()->create([
            'cooperative_id' => $coop->id,
            'name' => 'Fatima Ibrahim',
            'role' => 'member',
            'is_active' => false,
            'membership_status' => 'pending',
            'member_code' => null,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $applicant->savingsSlots()->create([
                'slot_number' => $i,
                'is_active' => false,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.members.index'));
        $response->assertStatus(200);
        $response->assertSee('Fatima Ibrahim');
        $response->assertSee('3/10 (₦15,000.00/mo)');
    }
}
