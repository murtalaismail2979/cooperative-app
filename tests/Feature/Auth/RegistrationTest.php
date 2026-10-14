<?php

namespace Tests\Feature\Auth;

use App\Models\Cooperative;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        Cooperative::factory()->create(['is_active' => true]);

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_member_can_register(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'cooperative_id' => $coop->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'nok_name' => 'Kin Name',
            'nok_phone' => '08000000000',
            'nok_relationship' => 'Spouse',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_cooperative_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register-cooperative');

        $response->assertStatus(200);
        $response->assertSee('MyCoopSuite');
        $response->assertSee('Registered Office Address');
        $response->assertSee('State');
        $response->assertSee('LGA (Local Govt Area)');
        $response->assertSee('id="lga"', false);
        $response->assertSee('nigeria-states-lgas.js');
        $response->assertDontSee('YLDA Association');
    }

    public function test_new_cooperative_can_be_registered(): void
    {
        $response = $this->post('/register-cooperative', [
            'cooperative_name' => 'Test Mutual Cooperative',
            'cooperative_slug' => 'TEST-MUTUAL',
            'cooperative_address' => 'Plot 45 Broad Street',
            'state' => 'Lagos',
            'lga' => 'Lagos Island',
            'base_slot_amount' => 3000,
            'dividend_allocation_basis' => 'savings_only',
            'name' => 'Admin User',
            'email' => 'admin@testmutual.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('cooperatives', [
            'slug' => 'TEST-MUTUAL',
            'address' => 'Plot 45 Broad Street',
            'state' => 'Lagos',
            'lga' => 'Lagos Island',
        ]);
        $response->assertRedirect(route('admin.dashboard'));
    }
}
