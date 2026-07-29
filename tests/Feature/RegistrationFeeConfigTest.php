<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RegistrationFeeSetting;
use App\Models\RegistrationFeeHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFeeConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_registration_fee_settings_page()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.registration-fees.settings'));

        $response->assertStatus(200);
        $response->assertSee('Registration Fee Settings');
    }

    public function test_admin_can_update_registration_fee_amount()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.registration-fees.settings.update'), [
            'amount' => 15000.00,
            'reason' => 'Annual fee increase approved by board',
        ]);

        $response->assertRedirect(route('admin.registration-fees.settings'));
        $this->assertDatabaseHas('registration_fee_settings', [
            'amount' => 15000.00,
        ]);
        $this->assertDatabaseHas('registration_fee_histories', [
            'new_amount' => 15000.00,
            'reason' => 'Annual fee increase approved by board',
        ]);
    }

    public function test_member_cannot_access_or_update_registration_fee_settings()
    {
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($member)->get(route('admin.registration-fees.settings'));
        $response->assertStatus(403);

        $updateResponse = $this->actingAs($member)->post(route('admin.registration-fees.settings.update'), [
            'amount' => 20000.00,
        ]);
        $updateResponse->assertStatus(403);
    }
}
