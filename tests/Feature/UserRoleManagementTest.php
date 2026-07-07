<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_list_with_role_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'name' => 'John Treasurer']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Alice Member']);

        // Check general listing (all users)
        $response = $this->actingAs($admin)
            ->get(route('admin.members.index'));
        $response->assertOk();
        $response->assertSee('John Treasurer');
        $response->assertSee('Alice Member');

        // Check treasurer role filter
        $responseFilter = $this->actingAs($admin)
            ->get(route('admin.members.index', ['role' => 'treasurer']));
        $responseFilter->assertOk();
        $responseFilter->assertSee('John Treasurer');
        $responseFilter->assertDontSee('Alice Member');
    }

    public function test_admin_can_create_member_user_with_slots_and_nok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.members.store'), [
                'name' => 'New Member',
                'email' => 'newmember@example.com',
                'password' => 'password123',
                'role' => 'member',
                'slots' => 3,
                'registration_year' => 2026,
                'nok_name' => 'Jane Kin',
                'nok_phone' => '08012345678',
                'nok_relationship' => 'Spouse',
            ]);

        $response->assertRedirect(route('admin.members.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newmember@example.com',
            'role' => 'member',
            'registration_year' => 2026,
        ]);

        $createdUser = User::where('email', 'newmember@example.com')->first();
        $this->assertNotNull($createdUser->member_code);
        $this->assertEquals(3, $createdUser->savingsSlots()->where('is_active', true)->count());
        $this->assertDatabaseHas('next_of_kins', [
            'user_id' => $createdUser->id,
            'name' => 'Jane Kin',
        ]);
    }

    public function test_admin_can_create_treasurer_without_slots_and_nok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.members.store'), [
                'name' => 'New Treasurer',
                'email' => 'newtreasurer@example.com',
                'password' => 'password123',
                'role' => 'treasurer',
            ]);

        $response->assertRedirect(route('admin.members.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newtreasurer@example.com',
            'role' => 'treasurer',
            'member_code' => null,
            'registration_year' => null,
        ]);

        $createdUser = User::where('email', 'newtreasurer@example.com')->first();
        $this->assertEquals(0, $createdUser->savingsSlots()->count());
    }

    public function test_admin_can_change_user_role_from_member_to_treasurer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'role' => 'member',
            'member_code' => 'YLDA/26/0001',
            'registration_year' => 2026,
        ]);
        
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);
        $member->nextOfKin()->create([
            'name' => 'Jane Kin',
            'phone' => '08012345678',
            'relationship' => 'Spouse',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.members.update', $member), [
                'name' => 'Alice Changed',
                'email' => $member->email,
                'role' => 'treasurer',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.members.index'));
        $member->refresh();

        $this->assertEquals('treasurer', $member->role);
        $this->assertNull($member->member_code);
        $this->assertNull($member->registration_year);
        $this->assertEquals(0, $member->savingsSlots()->where('is_active', true)->count());
    }

    public function test_admin_can_change_user_role_from_treasurer_to_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create([
            'role' => 'treasurer',
            'member_code' => null,
            'registration_year' => null,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.members.update', $treasurer), [
                'name' => 'Treasurer To Member',
                'email' => $treasurer->email,
                'role' => 'member',
                'is_active' => 1,
                'slots' => 2,
                'registration_year' => 2026,
                'nok_name' => 'Jane Kin',
                'nok_phone' => '08012345678',
                'nok_relationship' => 'Spouse',
            ]);

        $response->assertRedirect(route('admin.members.index'));
        $treasurer->refresh();

        $this->assertEquals('member', $treasurer->role);
        $this->assertNotNull($treasurer->member_code);
        $this->assertEquals(2, $treasurer->savingsSlots()->where('is_active', true)->count());
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }
}
