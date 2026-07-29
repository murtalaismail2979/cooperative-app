<?php

namespace Database\Factories;

use App\Models\NextOfKin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NextOfKinFactory extends Factory
{
    protected $model = NextOfKin::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'relationship' => $this->faker->randomElement(['Spouse', 'Child', 'Sibling', 'Parent']),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
        ];
    }
}
