<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunningChargeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_year',
        'end_year',
        'amount',
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'amount' => 'float',
    ];

    /**
     * Get rate amount for a specific year.
     */
    public static function getAmountForYear(int $year): float
    {
        $rate = self::where('start_year', '<=', $year)
            ->where('end_year', '>=', $year)
            ->orderBy('start_year', 'desc')
            ->first();

        if ($rate) {
            return (float) $rate->amount;
        }

        if ($year <= 2021) {
            return 100.00;
        } elseif ($year <= 2023) {
            return 300.00;
        } else {
            return 500.00;
        }
    }
}
