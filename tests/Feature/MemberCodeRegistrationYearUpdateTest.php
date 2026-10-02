<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCodeRegistrationYearUpdateTest extends TestCase
{
    use RefreshDatabase;
    public function test_updating_registration_year_updates_member_code_year_prefix(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $member = User::factory()->create([
            'role' => 'member',
            'registration_year' => 2026,
            'member_code' => 'YLDA/26/0099',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.members.update', $member), [
            'name' => $member->name,
            'email' => $member->email,
            'phone' => '1234567890',
            'address' => '123 Street',
            'role' => 'member',
            'registration_year' => 2023,
            'slots' => 1,
            'nok_name' => 'Next Kin',
            'nok_phone' => '0987654321',
            'nok_relationship' => 'Brother',
        ]);

        $response->assertRedirect(route('admin.members.index'));

        $member->refresh();
        $this->assertEquals(2023, $member->registration_year);
        $this->assertEquals('YLDA/23/0099', $member->member_code);
    }
}
