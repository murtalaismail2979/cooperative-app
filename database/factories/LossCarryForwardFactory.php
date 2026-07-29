<?php

namespace Database\Factories;

use App\Models\LossCarryForward;
use Illuminate\Database\Eloquent\Factories\Factory;

class LossCarryForwardFactory extends Factory
{
    protected $model = LossCarryForward::class;

    public function definition(): array
    {
        return [
            'from_year' => 2025,
            'to_year' => 2026,
            'loss_amount' => 50000.00,
            'absorbed_amount' => 0.00,
            'status' => 'active',
        ];
    }
}
