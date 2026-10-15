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
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->post('/retrieve-password/verify', [
            'account_identifier' => 'member@example.com',
            'registration_number' => 'MEM-2026-001',
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
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->post('/retrieve-password/verify', [
            'account_identifier' => 'MEM-2026-999',
            'registration_number' => 'MEM-2026-999',
        ]);

        $response->assertRedirect('/retrieve-password/reset');
        $response->assertSessionHas('recovery_user_id', $user->id);
    }

    public function test_invalid_registration_number_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'member2@example.com',
            'member_code' => 'MEM-2026-002',
        ]);

        $response = $this->from('/retrieve-password')->post('/retrieve-password/verify', [
            'account_identifier' => 'member2@example.com',
            'registration_number' => 'WRONG-REG-999', // Wrong registration number
        ]);

        $response->assertRedirect('/retrieve-password');
        $response->assertSessionHasErrors('secret_verification');
    }

    public function test_password_can_be_retrieved_and_updated_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'user-recovery@example.com',
            'member_code' => 'MEM-2026-777',
            'password' => Hash::make('old-password-123'),
        ]);

        // Step 1: Verify secret questions
        $this->post('/retrieve-password/verify', [
            'account_identifier' => 'user-recovery@example.com',
            'registration_number' => 'MEM-2026-777',
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
    }

    public function test_unverified_session_cannot_access_reset_form(): void
    {
        $response = $this->get('/retrieve-password/reset');
        $response->assertRedirect('/retrieve-password');
        $response->assertSessionHasErrors('secret_verification');
    }

    public function test_unverified_session_cannot_submit_reset_password(): void
    {
        $response = $this->post('/retrieve-password/reset', [
            'password' => 'new-secret-pass-999',
            'password_confirmation' => 'new-secret-pass-999',
        ]);
        $response->assertRedirect('/retrieve-password');
        $response->assertSessionHasErrors('secret_verification');
    }

    public function test_secret_question_verification_handles_whitespace_and_case_insensitivity(): void
    {
        $user = User::factory()->create([
            'email' => 'case.whitespace@example.com',
            'member_code' => 'MEM-2026-CASE',
            'password' => Hash::make('old-password-123'),
        ]);

        // Submit with surrounding whitespace and lower case
        $response = $this->post('/retrieve-password/verify', [
            'account_identifier' => '  case.whitespace@example.com  ',
            'registration_number' => '  mem-2026-case  ',
        ]);

        $response->assertRedirect('/retrieve-password/reset');
        $response->assertSessionHas('recovery_user_id', $user->id);
    }

    public function test_reset_password_validates_confirmation_and_minimum_length(): void
    {
        $user = User::factory()->create([
            'email' => 'validation-check@example.com',
            'member_code' => 'MEM-2026-VAL',
        ]);

        // Verify session first
        $this->post('/retrieve-password/verify', [
            'account_identifier' => 'validation-check@example.com',
            'registration_number' => 'MEM-2026-VAL',
        ]);

        // Mismatched confirmation
        $mismatch = $this->post('/retrieve-password/reset', [
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'MismatchPass123!',
        ]);
        $mismatch->assertSessionHasErrors('password');

        // Too short (< 8 characters)
        $tooShort = $this->post('/retrieve-password/reset', [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $tooShort->assertSessionHasErrors('password');
    }

    public function test_recovery_session_token_is_cleared_after_successful_reset_preventing_replay(): void
    {
        $user = User::factory()->create([
            'email' => 'replay-prevention@example.com',
            'member_code' => 'MEM-2026-REPLAY',
        ]);

        // Step 1: Verify
        $this->post('/retrieve-password/verify', [
            'account_identifier' => 'replay-prevention@example.com',
            'registration_number' => 'MEM-2026-REPLAY',
        ]);

        // Step 2: Reset
        $this->post('/retrieve-password/reset', [
            'password' => 'valid-new-pass-1234',
            'password_confirmation' => 'valid-new-pass-1234',
        ])->assertRedirect('/login');

        // Step 3: Replay attempt (trying to reset again without re-verifying)
        $replayAttempt = $this->get('/retrieve-password/reset');
        $replayAttempt->assertRedirect('/retrieve-password');
        $replayAttempt->assertSessionHasErrors('secret_verification');
    }
}

