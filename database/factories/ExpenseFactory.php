<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'category' => 'operational',
            'description' => $this->faker->sentence(),
            'amount' => 15000.00,
            'expense_date' => now()->format('Y-m-d'),
            'status' => 'approved',
            'requested_by' => User::factory(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'investment_id' => null,
        ];
    }
}
