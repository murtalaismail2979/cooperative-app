<?php

namespace Database\Factories;

use App\Models\Investment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Venture',
            'type' => 'real-estate',
            'description' => $this->faker->paragraph(),
            'capital_amount' => 1000000.00,
            'total_returns' => 1200000.00,
            'start_date' => now()->startOfYear()->format('Y-m-d'),
            'end_date' => now()->endOfYear()->format('Y-m-d'),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
