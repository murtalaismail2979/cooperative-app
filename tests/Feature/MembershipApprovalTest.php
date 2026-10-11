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

    public function test_self_registered_member_has_pending_membership_status(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'cooperative_id' => $coop->id,
            'name' => 'Applicant Member',
            'email' => 'applicant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'nok_name' => 'Kin Person',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Brother',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

        $member = User::where('email', 'applicant@example.com')->first();
        $this->assertNotNull($member);
        $this->assertEquals('pending', $member->membership_status);
        $this->assertFalse((bool) $member->is_active);
        $this->assertTrue($member->isPendingMembership());
        $this->assertFalse($member->isApprovedMembership());

        // Savings slot should be inactive
        $this->assertDatabaseHas('savings_slots', [
            'user_id' => $member->id,
            'slot_number' => 1,
            'is_active' => false,
        ]);
    }

    public function test_cooperative_admin_can_approve_member(): void
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
        ]);

        $response = $this->actingAs($admin)->post(route('admin.members.reject', $member), [
            'rejection_reason' => 'Incomplete documentation',
        ]);

        $response->assertSessionHas('success');
        $member->refresh();

        $this->assertEquals('rejected', $member->membership_status);
        $this->assertFalse((bool) $member->is_active);
        $this->assertEquals('Incomplete documentation', $member->rejection_reason);
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
        ]);

        $response = $this->actingAs($adminA)->post(route('admin.members.approve', $memberB));
        $response->assertStatus(403);

        $memberB->refresh();
        $this->assertEquals('pending', $memberB->membership_status);
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
        ]);

        $activeMember = User::factory()->create([
            'cooperative_id' => $coop->id,
            'name' => 'Active Regular',
            'role' => 'member',
            'is_active' => true,
            'membership_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.members.index', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('Pending Applicant');
        $response->assertDontSee('Active Regular');
        $response->assertSee('Pending Approval');
    }
}
