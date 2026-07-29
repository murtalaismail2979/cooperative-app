<?php

namespace Tests\Unit\Models;

use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlySavingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_saving_attributes_and_relationships()
    {
        $user = User::factory()->create();
        $recorder = User::factory()->create(['role' => 'admin']);
        $slot = SavingsSlot::factory()->create(['user_id' => $user->id]);

        $saving = MonthlySaving::factory()->create([
            'user_id' => $user->id,
            'savings_slot_id' => $slot->id,
            'amount' => 10000.00,
            'month' => '2026-06-01',
            'payment_date' => '2026-06-05',
            'status' => 'paid',
            'recorded_by' => $recorder->id,
        ]);

        $this->assertEquals(10000.00, $saving->amount);
        $this->assertEquals($user->id, $saving->user->id);
        $this->assertEquals($slot->id, $saving->savingsSlot->id);
        $this->assertEquals($recorder->id, $saving->recorder->id);
    }
}
