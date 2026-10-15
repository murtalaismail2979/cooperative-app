<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_include_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_member_role_cannot_access_admin_dashboard(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($member)->get('/admin/dashboard');

        // Should be forbidden or redirected away from admin dashboard
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    public function test_admin_cannot_approve_member_of_different_cooperative(): void
    {
        $coopA = Cooperative::factory()->create(['is_active' => true]);
        $coopB = Cooperative::factory()->create(['is_active' => true]);

        $adminA = User::factory()->create([
            'role' => 'admin',
            'cooperative_id' => $coopA->id,
        ]);

        $memberB = User::factory()->create([
            'role' => 'member',
            'cooperative_id' => $coopB->id,
            'membership_status' => 'pending',
            'is_active' => false,
        ]);

        $response = $this->actingAs($adminA)->post("/admin/members/{$memberB->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals('pending', $memberB->fresh()->membership_status);
        $this->assertFalse($memberB->fresh()->is_active);
    }

    public function test_password_recovery_verification_is_rate_limited(): void
    {
        $coop = Cooperative::factory()->create();
        $user = User::factory()->create([
            'cooperative_id' => $coop->id,
            'email' => 'victim@example.com',
            'member_code' => 'YLDA/26/0001',
        ]);

        // Attempt 10 failed verifications within the throttle limit
        for ($i = 0; $i < 10; $i++) {
            $this->post('/retrieve-password/verify', [
                'account_identifier' => 'victim@example.com',
                'registration_number' => 'WRONG_GUESS_' . $i,
            ]);
        }

        // The 11th request must be rate-limited (429 Too Many Requests)
        $rateLimitedResponse = $this->post('/retrieve-password/verify', [
            'account_identifier' => 'victim@example.com',
            'registration_number' => 'WRONG_GUESS_11',
        ]);

        $rateLimitedResponse->assertStatus(429);
    }
}
