<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialYearFactory extends Factory
{
    protected $model = FinancialYear::class;

    public function definition(): array
    {
        return [
            'year' => (int) date('Y'),
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ];
    }
}
