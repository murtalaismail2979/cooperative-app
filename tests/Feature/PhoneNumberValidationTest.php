<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhoneNumberValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_number_rule_accepts_valid_phone_numbers(): void
    {
        $validNumbers = [
            '08012345678',
            '07012345678',
            '09012345678',
            '08112345678',
            '+2348012345678',
            '+234 801 234 5678',
            '+234-801-234-5678',
            '080-1234-5678',
            '(080) 123-4567',
            '1234567890',
            '0987654321',
            '01234567',
        ];

        foreach ($validNumbers as $number) {
            $validator = Validator::make(
                ['phone' => $number],
                ['phone' => [new PhoneNumber]]
            );

            $this->assertTrue($validator->passes(), "Expected '{$number}' to be valid, but it failed with: " . implode(', ', $validator->errors()->all()));
        }
    }

    public function test_phone_number_rule_rejects_invalid_phone_numbers(): void
    {
        $invalidNumbers = [
            '123',                      // Too short (3 digits)
            '080',                      // Too short (3 digits)
            '123456',                   // Too short (6 digits)
            'abc12345678',              // Contains letters
            'none',                     // Word
            'N/A',                      // String
            'nil',                      // String
            '080123456789012345',       // Too long (18 digits)
            '080+12345678',             // Plus in the middle
            '++2348012345678',          // Double plus
            '(080 1234567',             // Unmatched parenthesis
            '080#123*4567',             // Invalid symbols
        ];

        foreach ($invalidNumbers as $number) {
            $validator = Validator::make(
                ['phone' => $number],
                ['phone' => [new PhoneNumber]]
            );

            $this->assertFalse($validator->passes(), "Expected '{$number}' to be rejected, but it passed validation.");
        }
    }

    public function test_public_registration_rejects_invalid_phone(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'cooperative_id' => $coop->id,
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => 'invalid-phone',
            'nok_name' => 'Jane Doe',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Spouse',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_public_registration_rejects_invalid_nok_phone(): void
    {
        $coop = Cooperative::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'cooperative_id' => $coop->id,
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '08012345678',
            'nok_name' => 'Jane Doe',
            'nok_phone' => '123', // Too short
            'nok_relationship' => 'Spouse',
        ]);

        $response->assertSessionHasErrors('nok_phone');
    }

    public function test_admin_member_creation_rejects_invalid_phone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/members', [
            'name' => 'Test Member',
            'email' => 'testmember@example.com',
            'password' => 'Password123!',
            'role' => 'member',
            'slots' => 2,
            'phone' => 'letters-only',
            'nok_name' => 'Next Kin',
            'nok_phone' => '08012345678',
            'nok_relationship' => 'Brother',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_admin_member_creation_rejects_invalid_nok_phone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/members', [
            'name' => 'Test Member',
            'email' => 'testmember@example.com',
            'password' => 'Password123!',
            'role' => 'member',
            'slots' => 2,
            'phone' => '08012345678',
            'nok_name' => 'Next Kin',
            'nok_phone' => '080', // Too short
            'nok_relationship' => 'Brother',
        ]);

        $response->assertSessionHasErrors('nok_phone');
    }

    public function test_cooperative_registration_rejects_invalid_phone(): void
    {
        $response = $this->post('/register-cooperative', [
            'cooperative_name' => 'Apex Horizon',
            'base_slot_amount' => 2000,
            'dividend_allocation_basis' => 'savings_only',
            'name' => 'Admin User',
            'email' => 'admin@apex.org',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cooperative_phone' => 'abcde123',
            'phone' => '08012345678',
        ]);

        $response->assertSessionHasErrors('cooperative_phone');
    }
}
