<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RegistrationFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_member_management_workflow()
    {
        // 1. Admin logs in
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // 2. Admin creates a new member
        $memberData = [
            'name' => 'Alice Smith',
            'email' => 'alice@coop.com',
            'phone' => '08012345678',
            'address' => '123 Main Street',
            'registration_year' => 2026,
            'role' => 'member',
            'slots' => 2,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'nok_name' => 'Bob Smith',
            'nok_phone' => '08087654321',
            'nok_relationship' => 'Spouse',
            'nok_address' => '123 Main Street',
        ];

        $response = $this->post(route('admin.members.store'), $memberData);
        $response->assertRedirect(route('admin.members.index'));

        // 3. Member is saved in DB
        $member = User::where('email', 'alice@coop.com')->first();
        $this->assertNotNull($member);
        $this->assertEquals('Alice Smith', $member->name);
        $this->assertStringStartsWith('YLDA/26/', $member->member_code);
        $this->assertCount(2, $member->savingsSlots);
        $this->assertNotNull($member->nextOfKin);
        $this->assertEquals('Bob Smith', $member->nextOfKin->name);

        // 4. Registration fee obligation is automatically created
        $this->assertDatabaseHas('registration_fees', [
            'user_id' => $member->id,
        ]);

        // 5. Admin views member index and show/edit page
        $this->get(route('admin.members.index'))->assertStatus(200);
        $this->get(route('admin.members.edit', $member))->assertStatus(200);

        // 6. Admin updates member information
        $updateResponse = $this->put(route('admin.members.update', $member), [
            'name' => 'Alice Johnson',
            'email' => 'alice@coop.com',
            'phone' => '08099998888',
            'address' => '456 New Way',
            'role' => 'member',
            'slots' => 2,
            'is_active' => true,
            'nok_name' => 'Bob Smith',
            'nok_phone' => '08087654321',
            'nok_relationship' => 'Spouse',
            'nok_address' => '456 New Way',
        ]);

        $updateResponse->assertRedirect(route('admin.members.index'));

        // 7. Verify updated information in DB
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'name' => 'Alice Johnson',
            'phone' => '08099998888',
            'address' => '456 New Way',
        ]);

        // 8. Normal member tries to access admin member creation (blocked 403)
        $this->actingAs($member);
        $this->get(route('admin.members.index'))->assertStatus(403);
    }
}
