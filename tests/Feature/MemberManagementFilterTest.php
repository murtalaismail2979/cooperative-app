<?php

namespace Tests\Feature;

// Import models
use App\Models\User;
use App\Models\SavingsSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_members_by_name_or_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'John Doe',
            'email' => 'john.doe@example.org',
            'member_code' => 'YLDA/26/0001'
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.org',
            'member_code' => 'YLDA/26/0002'
        ]);

        // Filter by name
        $response = $this->actingAs($admin)
            ->get(route('admin.members.index', ['search' => 'John']));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');

        // Filter by code
        $response = $this->actingAs($admin)
            ->get(route('admin.members.index', ['search' => '0002']));

        $response->assertOk();
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');
    }

    public function test_admin_can_filter_members_by_registration_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'registration_year' => 2024
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Bob Smith',
            'registration_year' => 2025
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.members.index', ['reg_year' => 2024]));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Smith');
    }

    public function test_admin_can_filter_members_by_slots_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper'
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Bob Smith'
        ]);

        // Create slots
        SavingsSlot::create(['user_id' => $member1->id, 'slot_number' => 1, 'is_active' => true]);
        SavingsSlot::create(['user_id' => $member1->id, 'slot_number' => 2, 'is_active' => true]);

        SavingsSlot::create(['user_id' => $member2->id, 'slot_number' => 1, 'is_active' => true]);

        // Filter by slots = 2
        $response = $this->actingAs($admin)
            ->get(route('admin.members.index', ['slots' => 2]));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Smith');
    }

    public function test_admin_can_filter_members_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'is_active' => true
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Bob Smith',
            'is_active' => false
        ]);

        // Filter by active
        $response = $this->actingAs($admin)
            ->get(route('admin.members.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Smith');

        // Filter by inactive
        $response = $this->actingAs($admin)
            ->get(route('admin.members.index', ['status' => 'inactive']));

        $response->assertOk();
        $response->assertSee('Bob Smith');
        $response->assertDontSee('Alice Cooper');
    }
}
