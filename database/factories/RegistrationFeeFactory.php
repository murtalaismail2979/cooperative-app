<?php

namespace Database\Factories;

use App\Models\RegistrationFee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFeeFactory extends Factory
{
    protected $model = RegistrationFee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fee_amount' => 5000.00,
            'total_paid' => 5000.00,
            'status' => 'fully_paid',
            'notes' => null,
        ];
    }
}
