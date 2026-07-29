<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use App\Models\Loan;
use App\Models\NextOfKin;
use App\Models\RegistrationFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_and_attributes()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'member',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'john@example.com',
            'role' => 'member',
            'is_active' => 1,
        ]);

        $this->assertTrue($user->is_active);
    }

    public function test_user_role_helper_methods()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isTreasurer());
        $this->assertFalse($admin->isMember());
        $this->assertTrue($admin->isAdminOrTreasurer());

        $this->assertTrue($treasurer->isTreasurer());
        $this->assertFalse($treasurer->isAdmin());
        $this->assertTrue($treasurer->isAdminOrTreasurer());

        $this->assertTrue($member->isMember());
        $this->assertFalse($member->isAdmin());
        $this->assertFalse($member->isAdminOrTreasurer());
    }

    public function test_user_scopes()
    {
        User::factory()->create(['role' => 'member', 'is_active' => true]);
        User::factory()->create(['role' => 'member', 'is_active' => false]);
        User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $activeUsers = User::active()->get();
        $this->assertCount(2, $activeUsers);

        $memberUsers = User::role('member')->get();
        $this->assertCount(2, $memberUsers);
    }

    public function test_member_code_generation()
    {
        $code1 = User::generateMemberCode(2026);
        $this->assertStringStartsWith('YLDA/26/', $code1);

        $user = User::factory()->create([
            'member_code' => $code1,
            'registration_year' => 2026,
        ]);

        $code2 = User::generateMemberCode(2026);
        $this->assertNotEquals($code1, $code2);
        $this->assertStringStartsWith('YLDA/26/', $code2);
    }

    public function test_user_relationships()
    {
        $user = User::factory()->create();

        $slot = SavingsSlot::factory()->create(['user_id' => $user->id]);
        $saving = MonthlySaving::factory()->create(['user_id' => $user->id, 'savings_slot_id' => $slot->id]);
        $loan = Loan::factory()->create(['user_id' => $user->id]);
        $nok = NextOfKin::factory()->create(['user_id' => $user->id]);
        $regFee = RegistrationFee::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->savingsSlots->contains($slot));
        $this->assertTrue($user->monthlySavings->contains($saving));
        $this->assertTrue($user->loans->contains($loan));
        $this->assertEquals($nok->id, $user->nextOfKin->id);
        $this->assertEquals($regFee->id, $user->registrationFee->id);
    }
}
