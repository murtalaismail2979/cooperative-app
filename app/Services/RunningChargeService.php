<?php

namespace App\Services;

use App\Models\RunningCharge;

class RunningChargeService
{
    const DEFAULT_AMOUNT = 500;

    /**
     * Get default amount based on the year.
     */
    public function getAmountForYear(int $year): float
    {
        if ($year <= 2021) {
            return 100;
        } elseif ($year <= 2023) {
            return 300;
        } else {
            return 500;
        }
    }

    /**
     * Record a running charge for a member.
     */
    public function recordCharge(int $userId, string $month, ?float $amount = null): RunningCharge
    {
        if ($amount === null) {
            $year = \Carbon\Carbon::parse($month)->year;
            $amount = $this->getAmountForYear($year);
        }

        return RunningCharge::updateOrCreate(
            ['user_id' => $userId, 'month' => $month],
            [
                'amount' => $amount,
                'payment_date' => now(),
                'status' => 'paid',
                'recorded_by' => auth()->id(),
            ]
        );
    }

    /**
     * Get running charges history.
     */
    public function getHistory()
    {
        return RunningCharge::with('user')
            ->latest('month')
            ->paginate(20);
    }

    /**
     * Get total running charges collected this month.
     */
    public function getCurrentMonthTotal(): float
    {
        return RunningCharge::whereMonth('month', now()->month)
            ->whereYear('month', now()->year)
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Get count of members who paid running charges this month.
     */
    public function getCurrentMonthPaidCount(): int
    {
        return RunningCharge::whereMonth('month', now()->month)
            ->whereYear('month', now()->year)
            ->where('status', 'paid')
            ->count();
    }
}