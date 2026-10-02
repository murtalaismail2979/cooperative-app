<?php

namespace Tests\Feature;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_slot_settings_from_admin_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Slot::create(['slot_number' => 1, 'amount' => 2000, 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('admin.slots.index'));

        $response->assertOk();
        $response->assertSee('Slot Settings');
        $response->assertSee(route('admin.slots.index'));
    }

    public function test_admin_can_update_slot_amount_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $slot = Slot::create(['slot_number' => 1, 'amount' => 2000, 'is_active' => true]);

        $this->actingAs($admin)
            ->put(route('admin.slots.update', $slot), [
                'amount' => 2500,
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.slots.index'));

        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'amount' => 2500,
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_add_a_future_slot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), [
                'slot_number' => 11,
                'amount' => 22000,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.slots.index'));

        $this->assertDatabaseHas('slots', [
            'slot_number' => 11,
            'amount' => 22000,
            'is_active' => 1,
        ]);
    }

    public function test_non_admin_cannot_access_slot_settings(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)
            ->get(route('admin.slots.index'))
            ->assertForbidden();
    }
}