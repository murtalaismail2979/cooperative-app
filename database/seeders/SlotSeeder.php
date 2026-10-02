<?php

namespace Database\Seeders;

use App\Models\Slot;
use Illuminate\Database\Seeder;

class SlotSeeder extends Seeder
{
    public function run(): void
    {
        for ($slotNumber = 1; $slotNumber <= 10; $slotNumber++) {
            Slot::updateOrCreate(
                ['slot_number' => $slotNumber],
                ['amount' => $slotNumber * 2000.00, 'is_active' => true]
            );
        }
    }
}