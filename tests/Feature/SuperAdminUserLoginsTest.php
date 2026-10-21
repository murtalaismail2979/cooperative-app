<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminUserLoginsTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $coopA;
    protected Cooperative $coopB;
    protected User $superAdmin;
    protected User $adminA;
    protected User $treasurerA;
    protected User $memberA;
    protected User $memberB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coopA = Cooperative::create([
            'name' => 'First Cooperative',
            'slug' => 'FCOOP',
            'base_slot_amount' => 2000,
            'is_active' => true,
        ]);

        $this->coopB = Cooperative::create([
            'name' => 'Second Cooperative',
            'slug' => 'SCOOP',
            'base_slot_amount' => 2000,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'cooperative_id' => null,
            'name' => 'Chief Super Admin',
            'email' => 'superadmin@coopsuite.test',
        ]);

        $this->adminA = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $this->coopA->id,
            'name' => 'Admin User A',
            'email' => 'adminA@fcoop.test',
        ]);

        $this->treasurerA = User::factory()->create([
            'role' => 'treasurer',
            'cooperative_id' => $this->coopA->id,
            'name' => 'Treasurer User A',
            'email' => 'treasurerA@fcoop.test',
        ]);

        $this->memberA = User::factory()->create([
            'role' => 'member',
            'cooperative_id' => $this->coopA->id,
            'name' => 'Member Alpha',
            'email' => 'alpha@fcoop.test',
            'member_code' => 'FCOOP/26/0001',
            'phone' => '08011112222',
        ]);

        $this->memberB = User::factory()->create([
            'role' => 'member',
            'cooperative_id' => $this->coopB->id,
            'name' => 'Member Beta',
            'email' => 'beta@scoop.test',
            'member_code' => 'SCOOP/26/0002',
            'phone' => '08033334444',
        ]);
    }

    public function test_super_admin_can_access_user_login_details_window(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.user-logins.index'));

        $response->assertOk();
        $response->assertSee('User Login Details');
        $response->assertSee('Super Admin Only');

        // Can see all users across cooperatives
        $response->assertSee('Chief Super Admin');
        $response->assertSee('superadmin@coopsuite.test');
        $response->assertSee('Admin User A');
        $response->assertSee('adminA@fcoop.test');
        $response->assertSee('Member Alpha');
        $response->assertSee('alpha@fcoop.test');
        $response->assertSee('FCOOP/26/0001');
        $response->assertSee('Member Beta');
        $response->assertSee('beta@scoop.test');
        // Super Admin sees sidebar link
        $dashboardResponse = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee(route('admin.user-logins.index'));
        $dashboardResponse->assertSee('User Login Details');
    }

    public function test_super_admin_can_filter_user_logins_by_search(): void
    {
        // Filter by member code
        $response = $this->actingAs($this->superAdmin)->get(route('admin.user-logins.index', ['search' => '0001']));
        $response->assertOk();
        $response->assertSee('Member Alpha');
        $response->assertSee('alpha@fcoop.test');
        $response->assertDontSee('Member Beta');

        // Filter by email
        $responseEmail = $this->actingAs($this->superAdmin)->get(route('admin.user-logins.index', ['search' => 'beta@scoop.test']));
        $responseEmail->assertOk();
        $responseEmail->assertSee('Member Beta');
        $responseEmail->assertDontSee('Member Alpha');
    }

    public function test_super_admin_can_filter_user_logins_by_cooperative(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.user-logins.index', [
            'cooperative_id' => $this->coopB->id,
        ]));

        $response->assertOk();
        $response->assertSee('Member Beta');
        $response->assertDontSee('Member Alpha');
    }

    public function test_super_admin_can_filter_user_logins_by_role(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.user-logins.index', [
            'role' => 'treasurer',
        ]));

        $response->assertOk();
        $response->assertSee('Treasurer User A');
        $response->assertDontSee('Member Alpha');
        $response->assertDontSee('Member Beta');
    }

    public function test_super_admin_can_reset_any_user_password(): void
    {
        $this->assertFalse(Hash::check('NewSecretPassword123!', $this->memberA->password));

        $response = $this->actingAs($this->superAdmin)->post(route('admin.user-logins.reset-password', $this->memberA), [
            'password' => 'NewSecretPassword123!',
            'password_confirmation' => 'NewSecretPassword123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->memberA->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123!', $this->memberA->password));
    }

    public function test_non_super_admins_are_forbidden_from_viewing_user_logins(): void
    {
        // Regular cooperative admin
        $this->actingAs($this->adminA)
            ->get(route('admin.dashboard'))
            ->assertDontSee(route('admin.user-logins.index'));

        $this->actingAs($this->adminA)
            ->get(route('admin.user-logins.index'))
            ->assertForbidden();

        // Treasurer
        $this->actingAs($this->treasurerA)
            ->get(route('admin.user-logins.index'))
            ->assertForbidden();

        // Member
        $this->actingAs($this->memberA)
            ->get(route('admin.user-logins.index'))
            ->assertForbidden();

        // Guest
        $this->get(route('admin.user-logins.index'))
            ->assertForbidden();
    }

    public function test_non_super_admins_cannot_reset_passwords(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('admin.user-logins.reset-password', $this->memberB), [
                'password' => 'HackedPassword123!',
                'password_confirmation' => 'HackedPassword123!',
            ])
            ->assertForbidden();

        $this->memberB->refresh();
        $this->assertFalse(Hash::check('HackedPassword123!', $this->memberB->password));
    }
}
