<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanRepaymentFactory extends Factory
{
    protected $model = LoanRepayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'user_id' => User::factory(),
            'amount' => 11000.00,
            'payment_date' => now()->format('Y-m-d'),
            'month_number' => 1,
            'recorded_by' => User::factory(),
        ];
    }
}
