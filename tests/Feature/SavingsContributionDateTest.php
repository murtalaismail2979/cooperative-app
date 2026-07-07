<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsContributionDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_recording_savings_requires_contribution_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // POST without payment_date should fail validation
        $response = $this->actingAs($admin)
            ->post(route('admin.savings.store'), [
                'member_id' => $member->id,
                'month' => '2026-06-01',
                'slots' => [$slot->id]
            ]);

        $response->assertSessionHasErrors(['payment_date']);
    }

    public function test_admin_recording_savings_saves_contribution_date_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.savings.store'), [
                'member_id' => $member->id,
                'month' => '2026-06-01',
                'slots' => [$slot->id],
                'payment_date' => '2026-06-15'
            ]);

        $response->assertRedirect(route('admin.running-charges.index'));

        // Verify the payment_date is saved correctly
        $this->assertDatabaseHas('monthly_savings', [
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'month' => '2026-06-01 00:00:00',
            'payment_date' => '2026-06-15 00:00:00',
            'status' => 'paid',
        ]);
    }

    public function test_treasurer_recording_savings_requires_contribution_date(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // POST without payment_date should fail validation
        $response = $this->actingAs($treasurer)
            ->post(route('treasurer.savings.store'), [
                'user_id' => $member->id,
                'month' => '2026-06-01',
                'slot_ids' => [$slot->id]
            ]);

        $response->assertSessionHasErrors(['payment_date']);
    }

    public function test_treasurer_recording_savings_saves_contribution_date_successfully(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        $response = $this->actingAs($treasurer)
            ->post(route('treasurer.savings.store'), [
                'user_id' => $member->id,
                'month' => '2026-06-01',
                'slot_ids' => [$slot->id],
                'payment_date' => '2026-06-20'
            ]);

        $response->assertRedirect(route('treasurer.running-charges.index'));

        // Verify the payment_date is saved correctly
        $this->assertDatabaseHas('monthly_savings', [
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'month' => '2026-06-01 00:00:00',
            'payment_date' => '2026-06-20 00:00:00',
            'status' => 'paid',
        ]);
    }
}
