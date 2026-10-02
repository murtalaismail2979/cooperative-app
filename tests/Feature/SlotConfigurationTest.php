<?php

namespace Tests\Feature;

use App\Models\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_configuration_can_store_slot_numbers_and_amounts(): void
    {
        $slot = Slot::create([
            'slot_number' => 1,
            'amount' => 2500.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'slot_number' => 1,
            'amount' => 2500.00,
            'is_active' => 1,
        ]);
    }

    public function test_default_slot_seeder_creates_slots_one_to_ten(): void
    {
        $this->seed(\Database\Seeders\SlotSeeder::class);

        $this->assertDatabaseCount('slots', 10);
        $this->assertSame(
            range(1, 10),
            Slot::orderBy('slot_number')->pluck('slot_number')->all()
        );
        $this->assertSame(10, Slot::where('is_active', true)->count());
        $this->assertSame('2000.00', (string) Slot::where('slot_number', 1)->value('amount'));
        $this->assertSame('20000.00', (string) Slot::where('slot_number', 10)->value('amount'));
    }
}