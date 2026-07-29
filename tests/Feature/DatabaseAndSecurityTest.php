<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class DatabaseAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/treasurer/dashboard')->assertRedirect('/login');
        $this->get('/member/dashboard')->assertRedirect('/login');
    }

    public function test_role_authorization_matrix()
    {
        $member = User::factory()->create(['role' => 'member']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        // Member cannot access Admin or Treasurer routes
        $this->actingAs($member);
        $this->get('/admin/dashboard')->assertStatus(403);
        $this->get('/admin/members')->assertStatus(403);
        $this->get('/treasurer/dashboard')->assertStatus(403);

        // Treasurer cannot access Admin user management
        $this->actingAs($treasurer);
        $this->get('/admin/members')->assertStatus(403);
        $this->get('/admin/investment-types')->assertStatus(403);
    }

    public function test_cascading_deletions_on_user_delete()
    {
        $user = User::factory()->create();
        $slot = SavingsSlot::factory()->create(['user_id' => $user->id]);
        $saving = MonthlySaving::factory()->create(['user_id' => $user->id, 'savings_slot_id' => $slot->id]);

        $this->assertDatabaseHas('savings_slots', ['id' => $slot->id]);
        $this->assertDatabaseHas('monthly_savings', ['id' => $saving->id]);

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('savings_slots', ['id' => $slot->id]);
        $this->assertDatabaseMissing('monthly_savings', ['id' => $saving->id]);
    }

    public function test_unique_email_constraint()
    {
        User::factory()->create(['email' => 'unique@coop.com']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'unique@coop.com']);
    }
}
