<?php

namespace Database\Factories;

use App\Models\RegistrationFeePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFeePaymentFactory extends Factory
{
    protected $model = RegistrationFeePayment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 5000.00,
            'payment_date' => now()->format('Y-m-d'),
            'receipt_number' => 'RF-' . strtoupper(uniqid()),
            'status' => 'completed',
            'recorded_by' => User::factory(),
            'notes' => null,
        ];
    }
}
