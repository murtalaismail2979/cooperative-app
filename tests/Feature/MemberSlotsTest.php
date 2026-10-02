<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\MemberSlotHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberSlotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_member_records_initial_slot_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.members.store'), [
                'name' => 'Alice Cooper',
                'email' => 'alice@example.com',
                'password' => 'password123',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'slots' => 3,
                'nok_name' => 'Bob Cooper',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
                'nok_address' => '123 Test St'
            ]);

        $response->assertRedirect(route('admin.members.index'));

        $member = User::where('email', 'alice@example.com')->first();
        $this->assertNotNull($member);

        // Verify slots
        $this->assertEquals(3, $member->savingsSlots()->where('is_active', true)->count());

        // Verify history
        $this->assertDatabaseHas('member_slot_histories', [
            'user_id' => $member->id,
            'previous_slots' => 0,
            'current_slots' => 3,
            'changed_by' => $admin->id,
            'reason' => 'Initial registration'
        ]);
    }

    public function test_creating_member_with_custom_registration_year_sets_correct_member_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.members.store'), [
                'name' => 'Alice Cooper 2',
                'email' => 'alice2@example.com',
                'password' => 'password123',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'slots' => 3,
                'registration_year' => 2024,
                'nok_name' => 'Bob Cooper',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
                'nok_address' => '123 Test St'
            ]);

        $response->assertRedirect(route('admin.members.index'));

        $member = User::where('email', 'alice2@example.com')->first();
        $this->assertNotNull($member);
        $this->assertEquals(2024, $member->registration_year);
        $this->assertStringStartsWith('YLDA/24/', $member->member_code);
    }

    public function test_member_code_sequence_number_continues_across_different_registration_years(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // First member created for year 2024
        $this->actingAs($admin)
            ->post(route('admin.members.store'), [
                'name' => 'Member One',
                'email' => 'member1@example.com',
                'password' => 'password123',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'slots' => 1,
                'registration_year' => 2024,
                'nok_name' => 'Nok One',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
            ]);

        // Second member created for year 2025
        $this->actingAs($admin)
            ->post(route('admin.members.store'), [
                'name' => 'Member Two',
                'email' => 'member2@example.com',
                'password' => 'password123',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'slots' => 1,
                'registration_year' => 2025,
                'nok_name' => 'Nok Two',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
            ]);

        $member1 = User::where('email', 'member1@example.com')->first();
        $member2 = User::where('email', 'member2@example.com')->first();

        $this->assertNotNull($member1);
        $this->assertNotNull($member2);

        // Sequence numbers should be continuous: e.g. YLDA/24/0001 and YLDA/25/0002 (or sequential offsets depending on factory users created in this test)
        // Since factory creates admin (which doesn't have a YLDA member code by default, but let's be sure of the suffix order)
        // Let's assert that member2 has a suffix that is exactly member1's suffix + 1
        $parts1 = explode('/', $member1->member_code);
        $parts2 = explode('/', $member2->member_code);
        $this->assertEquals((int)end($parts1) + 1, (int)end($parts2));
    }

    public function test_admin_can_modify_member_slots_and_logs_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create initial member
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
            'is_active' => true
        ]);
        $member->nextOfKin()->create([
            'name' => 'Bob Cooper',
            'phone' => '0987654321',
            'relationship' => 'Spouse'
        ]);

        // Create 3 active slots
        for ($i = 1; $i <= 3; $i++) {
            $member->savingsSlots()->create([
                'slot_number' => $i,
                'is_active' => true
            ]);
        }

        // Alice starts with 3 slots. Change to 5 slots.
        $response = $this->actingAs($admin)
            ->patch(route('admin.members.update', $member), [
                'name' => 'Alice Cooper',
                'email' => 'alice@example.com',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'is_active' => 1,
                'slots' => 5,
                'nok_name' => 'Bob Cooper',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
                'nok_address' => '123 Test St'
            ]);

        $response->assertRedirect(route('admin.members.index'));

        // Verify slots changed to 5
        $this->assertEquals(5, $member->savingsSlots()->where('is_active', true)->count());

        // Verify history logged increase
        $this->assertDatabaseHas('member_slot_histories', [
            'user_id' => $member->id,
            'previous_slots' => 3,
            'current_slots' => 5,
            'changed_by' => $admin->id,
            'reason' => 'Updated by Admin'
        ]);

        // Now change from 5 slots down to 2 slots.
        $response = $this->actingAs($admin)
            ->patch(route('admin.members.update', $member), [
                'name' => 'Alice Cooper',
                'email' => 'alice@example.com',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'is_active' => 1,
                'slots' => 2,
                'nok_name' => 'Bob Cooper',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
                'nok_address' => '123 Test St'
            ]);

        $response->assertRedirect(route('admin.members.index'));

        // Verify slots changed to 2
        $this->assertEquals(2, $member->savingsSlots()->where('is_active', true)->count());

        // Verify slots 3, 4, 5 are inactive
        $this->assertFalse($member->savingsSlots()->where('slot_number', 3)->first()->is_active);
        $this->assertFalse($member->savingsSlots()->where('slot_number', 4)->first()->is_active);
        $this->assertFalse($member->savingsSlots()->where('slot_number', 5)->first()->is_active);

        // Verify history logged decrease
        $this->assertDatabaseHas('member_slot_histories', [
            'user_id' => $member->id,
            'previous_slots' => 5,
            'current_slots' => 2,
            'changed_by' => $admin->id,
            'reason' => 'Updated by Admin'
        ]);
    }

    public function test_admin_can_manage_slots_from_dedicated_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $member->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.members.slots', $member))
            ->assertOk()
            ->assertSee('Manage Savings Slots')
            ->assertSee($member->member_code);

        $this->actingAs($admin)
            ->put(route('admin.members.slots.update', $member), [
                'slots' => 3,
                'slot_change_date' => '2026-09-14',
            ])
            ->assertRedirect(route('admin.members.slots', $member));

        $this->assertEquals(3, $member->savingsSlots()->where('is_active', true)->count());
        $this->assertDatabaseHas('member_slot_histories', [
            'user_id' => $member->id,
            'previous_slots' => 1,
            'current_slots' => 3,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_non_admin_cannot_access_dedicated_slot_management_page(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($treasurer)
            ->get(route('admin.members.slots', $member))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_modify_member_slots(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
            'is_active' => true
        ]);
        $member->nextOfKin()->create([
            'name' => 'Bob Cooper',
            'phone' => '0987654321',
            'relationship' => 'Spouse'
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $member->savingsSlots()->create([
                'slot_number' => $i,
                'is_active' => true
            ]);
        }

        // Try to update slots as a treasurer
        $response = $this->actingAs($treasurer)
            ->patch(route('admin.members.update', $member), [
                'name' => 'Alice Cooper',
                'email' => 'alice@example.com',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'is_active' => 1,
                'slots' => 5,
                'nok_name' => 'Bob Cooper',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
                'nok_address' => '123 Test St'
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_modify_member_slots_with_custom_effective_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
            'is_active' => true
        ]);
        $member->nextOfKin()->create([
            'name' => 'Bob Cooper',
            'phone' => '0987654321',
            'relationship' => 'Spouse'
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $member->savingsSlots()->create([
                'slot_number' => $i,
                'is_active' => true
            ]);
        }

        $customDate = '2026-01-15';

        $response = $this->actingAs($admin)
            ->patch(route('admin.members.update', $member), [
                'name' => 'Alice Cooper',
                'email' => 'alice@example.com',
                'phone' => '1234567890',
                'address' => '123 Test St',
                'is_active' => 1,
                'slots' => 5,
                'slot_change_date' => $customDate,
                'nok_name' => 'Bob Cooper',
                'nok_phone' => '0987654321',
                'nok_relationship' => 'Spouse',
                'nok_address' => '123 Test St'
            ]);

        $response->assertRedirect(route('admin.members.index'));

        // Verify history has custom date
        $history = MemberSlotHistory::where('user_id', $member->id)
            ->where('previous_slots', 3)
            ->where('current_slots', 5)
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals($customDate, $history->created_at->format('Y-m-d'));
    }
}
