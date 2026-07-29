<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_profile_or_change_password(): void
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/login');

        $responsePut = $this->put('/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $responsePut->assertRedirect('/login');
    }

    public function test_password_can_be_updated_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_incorrect_current_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('real-password-123'),
        ]);

        $response = $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'wrong-password-123',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrorsIn('updatePassword', 'current_password');
        $response->assertRedirect('/profile');

        $this->assertTrue(Hash::check('real-password-123', $user->fresh()->password));
    }

    public function test_new_password_must_match_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('real-password-123'),
        ]);

        $response = $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'real-password-123',
            'password' => 'new-password-123',
            'password_confirmation' => 'different-confirm-123',
        ]);

        $response->assertSessionHasErrorsIn('updatePassword', 'password');
        $response->assertRedirect('/profile');

        $this->assertTrue(Hash::check('real-password-123', $user->fresh()->password));
    }

    public function test_new_password_cannot_be_same_as_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('same-password-123'),
        ]);

        $response = $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'same-password-123',
            'password' => 'same-password-123',
            'password_confirmation' => 'same-password-123',
        ]);

        $response->assertSessionHasErrorsIn('updatePassword', 'password');
        $response->assertRedirect('/profile');

        $this->assertTrue(Hash::check('same-password-123', $user->fresh()->password));
    }

    public function test_user_can_login_with_new_password_and_old_password_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password-123'),
        ]);

        // Change password
        $this->actingAs($user)->put('/password', [
            'current_password' => 'old-password-123',
            'password' => 'brand-new-password-456',
            'password_confirmation' => 'brand-new-password-456',
        ]);

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // Attempt login with old password (must fail)
        $failLogin = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'old-password-123',
        ]);
        $failLogin->assertSessionHasErrors();
        $this->assertGuest();

        // Attempt login with new password (must succeed)
        $successLogin = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'brand-new-password-456',
        ]);
        $successLogin->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
