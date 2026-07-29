<?php

namespace Database\Factories;

use App\Models\InvestmentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvestmentTypeFactory extends Factory
{
    protected $model = InvestmentType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true) . ' Business';
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
        ];
    }
}
