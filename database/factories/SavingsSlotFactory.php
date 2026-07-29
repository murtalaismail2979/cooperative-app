<?php

namespace Database\Factories;

use App\Models\SavingsSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingsSlotFactory extends Factory
{
    protected $model = SavingsSlot::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slot_number' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
