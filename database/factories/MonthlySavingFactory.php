<?php

namespace Database\Factories;

use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlySavingFactory extends Factory
{
    protected $model = MonthlySaving::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'savings_slot_id' => SavingsSlot::factory(),
            'amount' => 10000.00,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'payment_date' => now()->format('Y-m-d'),
            'status' => 'paid',
            'recorded_by' => User::factory(),
        ];
    }
}
