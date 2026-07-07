<?php

namespace App\Services;

use App\Models\Dividend;
use App\Models\User;
use App\Models\MonthlySaving;

class DividendService
{
    /**
     * Get investments available for dividend distribution.
     */
    public function getAvailableInvestments()
    {
        return \App\Models\Investment::whereDoesntHave('dividend')
            ->latest()
            ->get();
    }

    /**
     * Distribute dividends for a given investment.
     */
    public function distributeDividends(int $investmentId, float $totalDividendAmount): Dividend
    {
        $investment = \App\Models\Investment::findOrFail($investmentId);

        if ($investment->dividend()->exists()) {
            throw new \RuntimeException('Dividends have already been distributed for this business/investment.');
        }

        $businessMonth = $investment->start_date->startOfMonth()->format('Y-m-d');

        // 2. Fetch active members and calculate units
        $members = User::where('role', 'member')->where('is_active', true)->get();
        $totalUnits = 0;
        $memberUnits = [];

        foreach ($members as $member) {
            // Get cumulative paid savings up to and including the start month of the investment
            $savingsUpToMonth = MonthlySaving::where('user_id', $member->id)
                ->where('status', 'paid')
                ->whereDate('month', '<=', $businessMonth)
                ->sum('amount');

            // Get number of 2000s as units
            $units = floor($savingsUpToMonth / 2000);

            if ($units > 0) {
                $memberUnits[$member->id] = $units;
                $totalUnits += $units;
            }
        }

        if ($totalUnits == 0) {
            throw new \RuntimeException('No savings data found for members at the time this business started.');
        }

        $originalSharableProfit = $totalDividendAmount;
        $cooperativeAmount = round($originalSharableProfit * 0.05, 2);
        $managementAmount = round($originalSharableProfit * 0.05, 2);
        $memberDistributionPool = $originalSharableProfit - $cooperativeAmount - $managementAmount;

        // 3. Calculate unit profit (member distribution pool / total units)
        $unitValue = $memberDistributionPool / $totalUnits;

        $year = (int) $investment->start_date->format('Y');

        // 4. Create the dividend record
        $dividend = Dividend::create([
            'year' => $year,
            'investment_id' => $investment->id,
            'total_dividend_amount' => $memberDistributionPool,
            'original_sharable_profit' => $originalSharableProfit,
            'cooperative_amount' => $cooperativeAmount,
            'management_amount' => $managementAmount,
            'member_distribution_pool' => $memberDistributionPool,
            'total_units' => $totalUnits,
            'unit_value' => $unitValue,
            'distributed_at' => now(),
            'distributed_by' => auth()->id(),
        ]);

        // 5. Create payout records
        foreach ($memberUnits as $userId => $units) {
            $dividend->payouts()->create([
                'user_id' => $userId,
                'units' => $units,
                'amount' => $units * $unitValue,
                'paid' => false,
            ]);
        }

        return $dividend;
    }

    /**
     * Get summary stats for a member's dividends.
     */
    public function getMemberDividendSummary(User $member): array
    {
        $payouts = $member->dividendPayouts;

        return [
            'total_amount' => $payouts->sum('amount'),
            'total_paid' => $payouts->where('paid', true)->sum('amount'),
            'total_pending' => $payouts->where('paid', false)->sum('amount'),
            'total_years' => $payouts->count(),
        ];
    }
}