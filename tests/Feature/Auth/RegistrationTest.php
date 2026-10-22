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
        $response->assertSee('Member Details');
        $response->assertSee('Next of Kin Details');
        $response->assertSee('Account Password & Security');
        $response->assertSee('coop_slot_display');
        $response->assertSee('togglePasswordVisibility');
        $response->assertSee('Monthly Savings Commitment');
        $response->assertSee('id="slots"', false);
        $response->assertSee('I declare that the information provided is accurate');
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
            'phone' => '08012345678',
            'nok_name' => 'Kin Name',
            'nok_phone' => '08000000000',
            'nok_relationship' => 'Spouse',
            'terms' => '1',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'membership_status' => 'pending',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('savings_slots', [
            'slot_number' => 1,
            'is_active' => false,
        ]);
    }

    public function test_new_member_can_register_with_custom_savings_slots(): void
    {
        $coop = Cooperative::factory()->create([
            'is_active' => true,
            'base_slot_amount' => 5000,
        ]);

        $response = $this->post('/register', [
            'cooperative_id' => $coop->id,
            'slots' => 3,
            'name' => 'Slot Investor',
            'email' => 'investor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '08012345678',
            'nok_name' => 'Kin Name',
            'nok_phone' => '08000000000',
            'nok_relationship' => 'Spouse',
            'terms' => '1',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));

        $user = \App\Models\User::where('email', 'investor@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(3, $user->savingsSlots()->count());
        $this->assertEquals(0, $user->savingsSlots()->where('is_active', true)->count());
        $this->assertEquals(3, $user->savingsSlots()->where('is_active', false)->count());

        $this->assertDatabaseHas('member_slot_histories', [
            'user_id' => $user->id,
            'previous_slots' => 0,
            'current_slots' => 3,
        ]);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'cooperative_id' => $coop->id,
            'name' => 'Test User',
            'email' => 'test_noterms@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '08012345678',
            'nok_name' => 'Kin Name',
            'nok_phone' => '08000000000',
            'nok_relationship' => 'Spouse',
            // terms missing
        ]);

        $response->assertSessionHasErrors('terms');
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
