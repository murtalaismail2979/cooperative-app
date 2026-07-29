<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_authentication_profile_and_password_workflow()
    {
        // 1. User is created
        $user = User::factory()->create([
            'email' => 'user@coop.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'member',
        ]);

        // 2. User logs in
        $response = $this->post('/login', [
            'email' => 'user@coop.com',
            'password' => 'OldPassword123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // 3. User accesses member dashboard & profile
        $this->get('/member/dashboard')->assertStatus(200);
        $this->get('/profile')->assertStatus(200);

        // 4. User changes password
        $changePasswordResponse = $this->put('/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecurePassword456!',
            'password_confirmation' => 'NewSecurePassword456!',
        ]);

        $changePasswordResponse->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewSecurePassword456!', $user->fresh()->password));

        // 5. User logs out
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        // 6. User attempts login with OLD password (fails)
        $this->post('/login', [
            'email' => 'user@coop.com',
            'password' => 'OldPassword123!',
        ])->assertSessionHasErrors();

        // 7. User logs in with NEW password (succeeds)
        $loginNewResponse = $this->post('/login', [
            'email' => 'user@coop.com',
            'password' => 'NewSecurePassword456!',
        ]);

        $loginNewResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
