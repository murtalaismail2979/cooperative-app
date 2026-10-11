<?php

namespace Database\Factories;

use App\Models\Cooperative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cooperative>
 */
class CooperativeFactory extends Factory
{
    protected $model = Cooperative::class;

    public function definition(): array
    {
        $name = $this->faker->company() . ' Cooperative';
        return [
            'name' => $name,
            'slug' => strtoupper($this->faker->unique()->lexify('??????')),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'is_active' => true,
            'enable_shares' => false,
            'enable_savings' => true,
            'base_slot_amount' => 2000.00,
            'share_unit_price' => 1000.00,
            'min_share_units' => 10,
            'dividend_allocation_basis' => 'savings_only',
            'hybrid_shares_percentage' => 50.00,
        ];
    }
}
