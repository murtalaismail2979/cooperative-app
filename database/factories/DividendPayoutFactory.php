<?php

namespace Database\Factories;

use App\Models\Dividend;
use App\Models\DividendPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DividendPayoutFactory extends Factory
{
    protected $model = DividendPayout::class;

    public function definition(): array
    {
        return [
            'dividend_id' => Dividend::factory(),
            'user_id' => User::factory(),
            'units' => 2,
            'amount' => 90000.00,
            'paid' => false,
            'paid_date' => null,
        ];
    }
}
