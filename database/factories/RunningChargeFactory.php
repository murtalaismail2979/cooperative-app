<?php

namespace Database\Factories;

use App\Models\RunningCharge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RunningChargeFactory extends Factory
{
    protected $model = RunningCharge::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 1000.00,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'payment_date' => now()->format('Y-m-d'),
            'status' => 'paid',
            'recorded_by' => User::factory(),
        ];
    }
}
