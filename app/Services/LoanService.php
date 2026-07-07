<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;

class LoanService
{
    const DEFAULT_PROFIT_RATE = 20;
    const DEFAULT_DURATION_MONTHS = 12;

    /**
     * Create a new loan with calculated values.
     */
    public function createLoan(User $user, float $principalAmount, string $dateGranted, ?float $profitRate = null, ?int $durationMonths = null, string $status = 'active'): Loan
    {
        $profitRate = $profitRate !== null ? $profitRate : self::DEFAULT_PROFIT_RATE;
        $durationMonths = $durationMonths !== null ? $durationMonths : self::DEFAULT_DURATION_MONTHS;

        $totalAmount = $principalAmount * (1 + $profitRate / 100);
        $monthlyPayment = $totalAmount / $durationMonths;

        return Loan::create([
            'user_id' => $user->id,
            'principal_amount' => $principalAmount,
            'profit_rate' => $profitRate,
            'total_amount' => $totalAmount,
            'monthly_payment' => $monthlyPayment,
            'duration_months' => $durationMonths,
            'remaining_months' => $durationMonths,
            'date_granted' => $dateGranted,
            'status' => $status,
            'approved_by' => auth()->id(),
        ]);
    }

    /**
     * Record a loan repayment and update loan status.
     */
    public function recordRepayment(Loan $loan, float $amount, string $paymentDate): void
    {
        $monthNumber = $loan->repayments()->count() + 1;

        $loan->repayments()->create([
            'user_id' => $loan->user_id,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'month_number' => $monthNumber,
            'recorded_by' => auth()->id(),
        ]);

        $loan->decrement('remaining_months');

        $this->refreshLoanStatus($loan);
    }

    /**
     * Update loan details and recalculate.
     */
    public function updateLoan(Loan $loan, array $data): Loan
    {
        $profitRate = isset($data['profit_rate']) ? (float)$data['profit_rate'] : (float)$loan->profit_rate;
        $principalAmount = isset($data['principal_amount']) ? (float)$data['principal_amount'] : (float)$loan->principal_amount;
        $durationMonths = isset($data['duration_months']) ? (int)$data['duration_months'] : (int)$loan->duration_months;

        $totalAmount = $principalAmount * (1 + $profitRate / 100);
        $monthlyPayment = $totalAmount / $durationMonths;

        $repaymentsCount = $loan->repayments()->count();
        $remainingMonths = max(0, $durationMonths - $repaymentsCount);
        $status = $remainingMonths <= 0 ? 'fully_paid' : 'active';

        $loan->update([
            'user_id' => $data['user_id'] ?? $loan->user_id,
            'principal_amount' => $principalAmount,
            'profit_rate' => $profitRate,
            'total_amount' => $totalAmount,
            'monthly_payment' => $monthlyPayment,
            'duration_months' => $durationMonths,
            'remaining_months' => $remainingMonths,
            'date_granted' => $data['date_granted'] ?? $loan->date_granted,
            'status' => $data['status'] ?? $status,
        ]);

        return $loan;
    }

    /**
     * Delete loan and its repayments.
     */
    public function deleteLoan(Loan $loan): void
    {
        $loan->repayments()->delete();
        $loan->delete();
    }

    /**
     * Update repayment.
     */
    public function updateRepayment(\App\Models\LoanRepayment $repayment, float $amount, string $paymentDate): void
    {
        $repayment->update([
            'amount' => $amount,
            'payment_date' => $paymentDate,
        ]);

        $this->refreshLoanStatus($repayment->loan);
    }

    /**
     * Delete repayment and update loan status/remaining months.
     */
    public function deleteRepayment(\App\Models\LoanRepayment $repayment): void
    {
        $loan = $repayment->loan;
        $repayment->delete();

        // Increment remaining months and adjust status
        $loan->increment('remaining_months');
        
        $this->refreshLoanStatus($loan);
    }

    /**
     * Recalculate loan status and remaining months based on outstanding balance.
     */
    public function refreshLoanStatus(Loan $loan): void
    {
        $loan = $loan->fresh();
        $outstanding = $loan->outstanding_balance;
        if ($outstanding <= 0) {
            $loan->update([
                'status' => 'fully_paid',
                'remaining_months' => 0
            ]);
        } else {
            $data = ['status' => 'active'];
            if ($loan->remaining_months <= 0) {
                $repaymentsCount = $loan->repayments()->count();
                $data['remaining_months'] = max(1, $loan->duration_months - $repaymentsCount);
            }
            $loan->update($data);
        }
    }


    /**
     * Get active loans summary stats.
     */
    public function getActiveLoansStats(): array
    {
        $activeLoans = Loan::where('status', 'active')->get();

        return [
            'count' => $activeLoans->count(),
            'totalOutstanding' => $activeLoans->sum('outstanding_balance'),
            'totalPrincipal' => $activeLoans->sum('principal_amount'),
        ];
    }
}