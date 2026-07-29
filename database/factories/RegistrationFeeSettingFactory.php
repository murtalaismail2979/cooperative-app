<?php

namespace Database\Factories;

use App\Models\RegistrationFeeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFeeSettingFactory extends Factory
{
    protected $model = RegistrationFeeSetting::class;

    public function definition(): array
    {
        return [
            'amount' => 5000.00,
            'effective_year' => 2026,
            'updated_by' => null,
        ];
    }
}
