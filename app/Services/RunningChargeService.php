<?php

namespace App\Services;

use App\Models\RunningCharge;
use App\Models\RunningChargeRate;

class RunningChargeService
{
    const DEFAULT_AMOUNT = 500;

    /**
     * Get default amount based on the year.
     */
    public function getAmountForYear(int $year): float
    {
        return RunningChargeRate::getAmountForYear($year);
    }

    /**
     * Record a running charge for a member.
     */
    public function recordCharge(int $userId, string $month, ?float $amount = null, ?string $paymentDate = null): RunningCharge
    {
        $month = \Carbon\Carbon::parse($month)->startOfMonth()->format('Y-m-d');

        if ($amount === null) {
            $year = (int) substr($month, 0, 4);
            $amount = $this->getAmountForYear($year);
        }

        $charge = RunningCharge::where('user_id', $userId)
            ->whereDate('month', $month)
            ->first();

        $attributes = [
            'amount' => $amount,
            'payment_date' => $paymentDate ? \Carbon\Carbon::parse($paymentDate)->format('Y-m-d') : now()->format('Y-m-d'),
            'status' => 'paid',
            'recorded_by' => auth()->id(),
        ];

        if ($charge) {
            $charge->update($attributes);
            return $charge->refresh();
        }

        return RunningCharge::create(array_merge(
            ['user_id' => $userId, 'month' => $month],
            $attributes
        ));
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

    /**
     * Bulk update charge amount for a year interval and persist rate rule.
     */
    public function updateAmountForYearInterval(int $startYear, int $endYear, float $amount, bool $updateExisting = true): int
    {
        $rate = RunningChargeRate::where('start_year', $startYear)
            ->where('end_year', $endYear)
            ->first();

        if ($rate) {
            $rate->update(['amount' => $amount]);
        } else {
            RunningChargeRate::create([
                'start_year' => $startYear,
                'end_year' => $endYear,
                'amount' => $amount,
            ]);
        }

        if ($updateExisting) {
            $startDate = \Carbon\Carbon::create($startYear, 1, 1)->startOfDay()->format('Y-m-d');
            $endDate = \Carbon\Carbon::create($endYear, 12, 31)->endOfDay()->format('Y-m-d');

            return RunningCharge::whereDate('month', '>=', $startDate)
                ->whereDate('month', '<=', $endDate)
                ->update(['amount' => $amount]);
        }

        return 0;
    }
}