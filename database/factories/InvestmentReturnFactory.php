<?php

namespace Database\Factories;

use App\Models\Investment;
use App\Models\InvestmentReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentReturnFactory extends Factory
{
    protected $model = InvestmentReturn::class;

    public function definition(): array
    {
        return [
            'investment_id' => Investment::factory(),
            'amount' => 50000.00,
            'return_date' => now()->format('Y-m-d'),
            'description' => 'Monthly return',
            'recorded_by' => User::factory(),
        ];
    }
}
