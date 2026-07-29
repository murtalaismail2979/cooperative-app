<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $principal = $this->faker->randomElement([100000, 200000, 500000]);
        $profitRate = 10.0;
        $totalAmount = $principal + ($principal * ($profitRate / 100));
        $duration = 10;
        $monthlyPayment = $totalAmount / $duration;

        return [
            'user_id' => User::factory(),
            'principal_amount' => $principal,
            'profit_rate' => $profitRate,
            'total_amount' => $totalAmount,
            'monthly_payment' => $monthlyPayment,
            'duration_months' => $duration,
            'remaining_months' => $duration,
            'date_granted' => now()->format('Y-m-d'),
            'status' => 'active',
            'approved_by' => User::factory(),
        ];
    }
}
