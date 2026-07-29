<?php

namespace Tests\Feature;

use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsSlotWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_savings_and_slot_workflow()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $member = User::factory()->create(['role' => 'member']);
        $slot1 = SavingsSlot::factory()->create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);
        $slot2 = SavingsSlot::factory()->create(['user_id' => $member->id, 'slot_number' => 2, 'is_active' => true]);

        // 1. Retrieve member active slots API
        $response = $this->get(route('members.active-slots', $member));
        $response->assertStatus(200);
        $response->assertJsonCount(2);

        // 2. Admin records monthly savings for member
        $postData = [
            'member_id' => $member->id,
            'month' => '2026-06-01',
            'payment_date' => '2026-06-05',
            'slots' => [
                $slot1->id,
                $slot2->id,
            ],
        ];

        $saveResponse = $this->post(route('admin.savings.store'), $postData);
        $saveResponse->assertRedirect(route('admin.running-charges.index'));

        // 3. Verify savings stored in database
        $this->assertDatabaseHas('monthly_savings', [
            'user_id' => $member->id,
            'savings_slot_id' => $slot1->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('monthly_savings', [
            'user_id' => $member->id,
            'savings_slot_id' => $slot2->id,
            'status' => 'paid',
        ]);

        // 4. Verify savings appears in history and reports
        $this->get(route('admin.savings.history'))->assertStatus(200);
        $this->get(route('admin.reports.savings'))->assertStatus(200);
    }
}
