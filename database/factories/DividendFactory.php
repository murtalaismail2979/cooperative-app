<?php

namespace Database\Factories;

use App\Models\Dividend;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DividendFactory extends Factory
{
    protected $model = Dividend::class;

    public function definition(): array
    {
        $amount = 500000.00;
        $coop = $amount * 0.05;
        $mgmt = $amount * 0.05;
        $distPool = $amount - ($coop + $mgmt);

        return [
            'year' => (int) date('Y'),
            'total_dividend_amount' => $amount,
            'total_units' => 10,
            'unit_value' => $distPool / 10,
            'distributed_at' => now(),
            'distributed_by' => User::factory(),
            'investment_id' => null,
            'original_sharable_profit' => $amount,
            'cooperative_amount' => $coop,
            'management_amount' => $mgmt,
            'member_distribution_pool' => $distPool,
        ];
    }
}
