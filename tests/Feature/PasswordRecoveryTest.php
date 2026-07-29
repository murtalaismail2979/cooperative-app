<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_recovery_page_can_be_rendered(): void
    {
        $response = $this->get('/retrieve-password');
        $response->assertStatus(200);
        $response->assertSee('Retrieve Password');
    }

    public function test_valid_secret_questions_verification_grants_access_to_reset_form(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'member_code' => 'MEM-2026-001',
            'registration_year' => 2026,
            'date_of_birth' => '1990-05-15',
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->post('/retrieve-password/verify', [
            'account_identifier' => 'member@example.com',
            'registration_number' => 'MEM-2026-001',
            'date_of_birth' => '1990-05-15',
        ]);

        $response->assertRedirect('/retrieve-password/reset');
        $response->assertSessionHas('recovery_user_id', $user->id);

        $resetPageResponse = $this->get('/retrieve-password/reset');
        $resetPageResponse->assertStatus(200);
        $resetPageResponse->assertSee('Set New Password');
    }

    public function test_verification_using_member_code_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'membercode@example.com',
            'member_code' => 'MEM-2026-999',
            'registration_year' => 2026,
            'date_of_birth' => '1992-08-20',
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->post('/retrieve-password/verify', [
            'account_identifier' => 'MEM-2026-999',
            'registration_number' => 'MEM-2026-999',
            'date_of_birth' => '1992-08-20',
        ]);

        $response->assertRedirect('/retrieve-password/reset');
        $response->assertSessionHas('recovery_user_id', $user->id);
    }

    public function test_invalid_registration_number_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'member2@example.com',
            'member_code' => 'MEM-2026-002',
            'date_of_birth' => '1990-05-15',
        ]);

        $response = $this->from('/retrieve-password')->post('/retrieve-password/verify', [
            'account_identifier' => 'member2@example.com',
            'registration_number' => 'WRONG-REG-999', // Wrong registration number
            'date_of_birth' => '1990-05-15',
        ]);

        $response->assertRedirect('/retrieve-password');
        $response->assertSessionHasErrors('secret_verification');
    }

    public function test_invalid_date_of_birth_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'member3@example.com',
            'member_code' => 'MEM-2026-003',
            'date_of_birth' => '1990-05-15',
        ]);

        $response = $this->from('/retrieve-password')->post('/retrieve-password/verify', [
            'account_identifier' => 'member3@example.com',
            'registration_number' => 'MEM-2026-003',
            'date_of_birth' => '1995-01-01', // Wrong date of birth
        ]);

        $response->assertRedirect('/retrieve-password');
        $response->assertSessionHasErrors('secret_verification');
    }

    public function test_password_can_be_retrieved_and_updated_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'user-recovery@example.com',
            'member_code' => 'MEM-2026-777',
            'date_of_birth' => '1988-12-10',
            'password' => Hash::make('old-password-123'),
        ]);

        // Step 1: Verify secret questions
        $this->post('/retrieve-password/verify', [
            'account_identifier' => 'user-recovery@example.com',
            'registration_number' => 'MEM-2026-777',
            'date_of_birth' => '1988-12-10',
        ]);

        // Step 2: Set new password
        $response = $this->post('/retrieve-password/reset', [
            'password' => 'retrieved-new-pass-789',
            'password_confirmation' => 'retrieved-new-pass-789',
        ]);

        $response->assertRedirect('/login');

        // Verify database has updated hashed password
        $this->assertTrue(Hash::check('retrieved-new-pass-789', $user->fresh()->password));

        // Test login with old password fails
        $failLogin = $this->post('/login', [
            'email' => 'user-recovery@example.com',
            'password' => 'old-password-123',
        ]);
        $failLogin->assertSessionHasErrors();

        // Test login with new retrieved password succeeds
        $successLogin = $this->post('/login', [
            'email' => 'user-recovery@example.com',
            'password' => 'retrieved-new-pass-789',
        ]);
        $successLogin->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
