<?php

namespace Tests\Unit\Models;

use App\Models\SavingsSlot;
use App\Models\User;
use App\Models\MonthlySaving;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsSlotModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_savings_slot_creation_and_user_relationship()
    {
        $user = User::factory()->create();
        $slot = SavingsSlot::factory()->create([
            'user_id' => $user->id,
            'slot_number' => 1,
            'is_active' => true,
        ]);

        $this->assertEquals($user->id, $slot->user->id);
        $this->assertTrue($slot->is_active);
    }

    public function test_savings_slot_monthly_savings_relationship()
    {
        $slot = SavingsSlot::factory()->create();
        $saving = MonthlySaving::factory()->create([
            'user_id' => $slot->user_id,
            'savings_slot_id' => $slot->id,
        ]);

        $this->assertTrue($slot->monthlySavings->contains($saving));
    }
}
