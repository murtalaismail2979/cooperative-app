<?php

namespace App\Services;

use App\Models\Investment;
use Illuminate\Http\Request;

class InvestmentService
{
    /**
     * Create a new investment.
     */
    public function createInvestment(array $data): Investment
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'active';

        return Investment::create($data);
    }

    /**
     * Record a return on investment.
     */
    public function recordReturn(Investment $investment, float $amount, string $returnDate, ?string $description = null): void
    {
        $investment->returns()->create([
            'amount' => $amount,
            'return_date' => $returnDate,
            'description' => $description,
            'recorded_by' => auth()->id(),
        ]);

        $investment->increment('total_returns', $amount);
    }

    /**
     * Get investment summary stats.
     */
    public function getInvestmentStats(): array
    {
        $activeInvestments = Investment::where('status', 'active')->get();

        return [
            'count' => $activeInvestments->count(),
            'totalCapital' => $activeInvestments->sum('capital_amount'),
            'totalReturns' => $activeInvestments->sum('total_returns'),
            'averageRoi' => $activeInvestments->count() > 0
                ? $activeInvestments->avg(function ($inv) { return $inv->roi; })
                : 0,
        ];
    }

    /**
     * Close an investment and calculate final returns.
     */
    public function closeInvestment(Investment $investment, ?string $endDate = null): void
    {
        $investment->update([
            'status' => 'completed',
            'end_date' => $endDate ?? now(),
        ]);
    }

    /**
     * Delete a return of investment and update the total returns.
     */
    public function deleteReturn(\App\Models\InvestmentReturn $return): void
    {
        $investment = $return->investment;
        if ($investment->status === 'active') {
            $investment->decrement('total_returns', $return->amount);
            $return->delete();
        }
    }

    /**
     * Update investment details.
     */
    public function updateInvestment(Investment $investment, array $data): Investment
    {
        $investment->update($data);
        return $investment;
    }

    /**
     * Delete investment and its returns & expenses.
     */
    public function deleteInvestment(Investment $investment): void
    {
        $investment->returns()->delete();
        $investment->expenses()->delete();
        $investment->delete();
    }

    /**
     * Update investment return.
     */
    public function updateReturn(\App\Models\InvestmentReturn $return, float $amount, string $returnDate, ?string $description = null): void
    {
        $investment = $return->investment;
        if ($investment->status === 'active') {
            $difference = $amount - $return->amount;
            $investment->increment('total_returns', $difference);
            $return->update([
                'amount' => $amount,
                'return_date' => $returnDate,
                'description' => $description,
            ]);
        }
    }

}