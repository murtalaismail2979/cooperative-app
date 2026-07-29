<?php

namespace App\Services;

use App\Models\Dividend;
use App\Models\DividendAdjustment;
use App\Models\DividendPayout;
use App\Models\DividendRecoveryPayment;
use App\Models\FinancialYear;
use App\Models\FinancialYearReconciliation;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\LossCarryForward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DividendReconciliationService
{
    /**
     * Check if a financial year is closed.
     */
    public function isYearClosed(int $year): bool
    {
        return FinancialYear::where('year', $year)->where('is_closed', true)->exists();
    }

    /**
     * Calculate net financial result and loss adjustment requirements for a given year.
     */
    public function calculateAnnualNetResult(int $year): array
    {
        // 1. Calculate Business Profits and Recognized Losses for the year
        $investments = Investment::whereYear('start_date', $year)->get();

        $totalBusinessProfit = 0.00;
        $totalRecognizedLoss = 0.00;

        foreach ($investments as $inv) {
            $returns = (float) $inv->total_returns;
            $expenses = (float) $inv->approved_expenses;
            $capital = (float) $inv->capital_amount;

            $sharable = $returns - $expenses;

            if ($returns >= $capital) {
                if ($sharable > 0) {
                    $totalBusinessProfit += $sharable;
                } elseif ($sharable < 0) {
                    $totalRecognizedLoss += abs($sharable);
                }
            } else {
                $lossAmount = ($capital - $returns) + $expenses;
                $totalRecognizedLoss += $lossAmount;
            }
        }

        // 2. Financing Profit (from Loans in the year)
        $loans = Loan::whereYear('date_granted', $year)->get();
        $totalFinancingProfit = 0.00;
        foreach ($loans as $loan) {
            $profit = (float) $loan->total_amount - (float) $loan->principal_amount;
            if ($profit > 0) {
                $totalFinancingProfit += $profit;
            }
        }

        // 3. Check for Loss Carry Forwards from previous years
        $previousCarriedLoss = LossCarryForward::where('to_year', $year)
            ->where('status', 'active')
            ->sum('unabsorbed_loss_amount');

        $totalRecognizedLoss += (float) $previousCarriedLoss;

        $grossSharableProfit = $totalBusinessProfit + $totalFinancingProfit;
        $netSharableProfit = max(0.00, $grossSharableProfit - $totalRecognizedLoss);

        // 4. Apply 10% Profit Sharing Rule (5% Coop, 5% Management, 90% Member Pool)
        $cooperativeAmount = round($netSharableProfit * 0.05, 2);
        $managementAmount = round($netSharableProfit * 0.05, 2);
        $adjustedMemberPool = max(0.00, $netSharableProfit - $cooperativeAmount - $managementAmount);

        // 5. Calculate Total Member Dividends Distributed for the year
        $dividends = Dividend::where('year', $year)->get();
        $totalDistributed = (float) $dividends->sum('member_distribution_pool');

        // 6. Calculate Overall Loss Adjustment & Unabsorbed Loss
        $totalLossAdjustment = max(0.00, $totalDistributed - $adjustedMemberPool);
        $unabsorbedLoss = max(0.00, $totalRecognizedLoss - $grossSharableProfit);

        return [
            'year' => $year,
            'total_business_profit' => round($totalBusinessProfit, 2),
            'total_financing_profit' => round($totalFinancingProfit, 2),
            'total_recognized_loss' => round($totalRecognizedLoss, 2),
            'gross_sharable_profit' => round($grossSharableProfit, 2),
            'previous_carried_loss' => round($previousCarriedLoss, 2),
            'net_sharable_profit' => round($netSharableProfit, 2),
            'cooperative_amount' => round($cooperativeAmount, 2),
            'management_amount' => round($managementAmount, 2),
            'adjusted_member_pool' => round($adjustedMemberPool, 2),
            'total_distributed' => round($totalDistributed, 2),
            'total_loss_adjustment' => round($totalLossAdjustment, 2),
            'unabsorbed_loss' => round($unabsorbedLoss, 2),
        ];
    }

    /**
     * Preview member-level dividend adjustments for a financial year.
     */
    public function previewReconciliation(int $year): array
    {
        $summary = $this->calculateAnnualNetResult($year);
        $totalDistributed = $summary['total_distributed'];
        $totalLossAdjustment = $summary['total_loss_adjustment'];

        $payouts = DividendPayout::whereHas('dividend', function ($q) use ($year) {
            $q->where('year', $year);
        })->with('user')->get();

        $memberData = [];
        foreach ($payouts as $payout) {
            $uId = $payout->user_id;
            if (!isset($memberData[$uId])) {
                $memberData[$uId] = [
                    'user' => $payout->user,
                    'user_id' => $uId,
                    'original_dividend' => 0.00,
                    'amount_paid' => 0.00,
                ];
            }

            $memberData[$uId]['original_dividend'] += (float) $payout->amount;
            if ($payout->paid) {
                $memberData[$uId]['amount_paid'] += (float) $payout->amount;
            }
        }

        $memberAdjustments = [];
        $totalOverDistributed = 0.00;
        $totalOutstandingPayable = 0.00;

        foreach ($memberData as $uId => $data) {
            $origDiv = $data['original_dividend'];
            $paid = $data['amount_paid'];

            // Calculate member proportional loss adjustment based on original dividend ratio
            $lossAdj = 0.00;
            if ($totalDistributed > 0 && $totalLossAdjustment > 0) {
                $ratio = $origDiv / $totalDistributed;
                $lossAdj = round($totalLossAdjustment * $ratio, 2);
            }

            $finalEntitlement = max(0.00, $origDiv - $lossAdj);

            $overpayment = 0.00;
            $underpayment = 0.00;

            if ($paid > $finalEntitlement) {
                $overpayment = round($paid - $finalEntitlement, 2);
                $totalOverDistributed += $overpayment;
            } elseif ($paid < $finalEntitlement) {
                $underpayment = round($finalEntitlement - $paid, 2);
                $totalOutstandingPayable += $underpayment;
            }

            $memberAdjustments[] = [
                'user_id' => $uId,
                'user_name' => $data['user']->name ?? 'Unknown',
                'member_code' => $data['user']->member_code ?? 'N/A',
                'original_dividend_amount' => $origDiv,
                'loss_adjustment_amount' => $lossAdj,
                'final_entitlement_amount' => $finalEntitlement,
                'amount_already_paid' => $paid,
                'overpayment_amount' => $overpayment,
                'underpayment_amount' => $underpayment,
            ];
        }

        $summary['total_over_distributed'] = round($totalOverDistributed, 2);
        $summary['total_outstanding_payable'] = round($totalOutstandingPayable, 2);
        $summary['member_adjustments'] = $memberAdjustments;

        return $summary;
    }

    /**
     * Finalize and store year-end dividend reconciliation.
     */
    public function processYearEndReconciliation(int $year, ?string $notes = null, ?int $approvedBy = null): FinancialYearReconciliation
    {
        if ($this->isYearClosed($year)) {
            throw new RuntimeException("Financial year {$year} is closed and cannot be reconciled without reopening.");
        }

        $preview = $this->previewReconciliation($year);

        return DB::transaction(function () use ($year, $preview, $notes, $approvedBy) {
            $reconciliation = FinancialYearReconciliation::updateOrCreate(
                ['year' => $year],
                [
                    'total_business_profit' => $preview['total_business_profit'],
                    'total_financing_profit' => $preview['total_financing_profit'],
                    'total_recognized_loss' => $preview['total_recognized_loss'],
                    'net_sharable_profit' => $preview['net_sharable_profit'],
                    'cooperative_amount' => $preview['cooperative_amount'],
                    'management_amount' => $preview['management_amount'],
                    'adjusted_member_pool' => $preview['adjusted_member_pool'],
                    'total_distributed' => $preview['total_distributed'],
                    'total_loss_adjustment' => $preview['total_loss_adjustment'],
                    'total_over_distributed' => $preview['total_over_distributed'],
                    'total_outstanding_payable' => $preview['total_outstanding_payable'],
                    'status' => 'approved',
                    'approved_by' => $approvedBy ?? auth()->id(),
                    'approved_at' => now(),
                    'notes' => $notes,
                ]
            );

            // Clear previous adjustments for this reconciliation if re-running
            $reconciliation->adjustments()->delete();

            foreach ($preview['member_adjustments'] as $adj) {
                $status = 'pending';
                if ($adj['overpayment_amount'] == 0 && $adj['underpayment_amount'] == 0) {
                    $status = 'settled';
                }

                DividendAdjustment::create([
                    'reconciliation_id' => $reconciliation->id,
                    'user_id' => $adj['user_id'],
                    'year' => $year,
                    'original_dividend_amount' => $adj['original_dividend_amount'],
                    'loss_adjustment_amount' => $adj['loss_adjustment_amount'],
                    'final_entitlement_amount' => $adj['final_entitlement_amount'],
                    'amount_already_paid' => $adj['amount_already_paid'],
                    'overpayment_amount' => $adj['overpayment_amount'],
                    'underpayment_amount' => $adj['underpayment_amount'],
                    'amount_recovered' => 0.00,
                    'status' => $status,
                    'recovery_method' => $adj['overpayment_amount'] > 0 ? 'future_dividend' : null,
                ]);
            }

            // Create Loss Carry Forward if unabsorbed loss exists
            if ($preview['unabsorbed_loss'] > 0) {
                LossCarryForward::updateOrCreate(
                    [
                        'from_year' => $year,
                        'to_year' => $year + 1,
                    ],
                    [
                        'unabsorbed_loss_amount' => $preview['unabsorbed_loss'],
                        'absorbed_amount' => 0.00,
                        'status' => 'active',
                        'notes' => "Carried forward from {$year} financial year net loss.",
                    ]
                );
            }

            return $reconciliation;
        });
    }

    /**
     * Record recovery payment from an overpaid member.
     */
    public function recordRecoveryPayment(
        DividendAdjustment $adjustment,
        float $amount,
        string $paymentDate,
        string $paymentMethod = 'Cash',
        ?string $referenceNumber = null,
        ?int $recordedBy = null
    ): DividendRecoveryPayment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Recovery payment amount must be greater than zero.');
        }

        if ($adjustment->overpayment_amount <= 0) {
            throw new RuntimeException('This member does not have an outstanding overpayment recovery requirement.');
        }

        return DB::transaction(function () use ($adjustment, $amount, $paymentDate, $paymentMethod, $referenceNumber, $recordedBy) {
            $payment = $adjustment->recoveryPayments()->create([
                'user_id' => $adjustment->user_id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'recorded_by' => $recordedBy ?? auth()->id(),
            ]);

            $newTotalRecovered = (float) $adjustment->amount_recovered + $amount;
            $overpayment = (float) $adjustment->overpayment_amount;

            $status = 'partially_recovered';
            if ($newTotalRecovered >= $overpayment) {
                $status = 'fully_recovered';
            }

            $adjustment->update([
                'amount_recovered' => $newTotalRecovered,
                'status' => $status,
            ]);

            return $payment;
        });
    }

    /**
     * Close a financial year.
     */
    public function closeFinancialYear(int $year, ?int $closedBy = null): FinancialYear
    {
        return FinancialYear::updateOrCreate(
            ['year' => $year],
            [
                'is_closed' => true,
                'closed_at' => now(),
                'closed_by' => $closedBy ?? auth()->id(),
            ]
        );
    }

    /**
     * Reopen a closed financial year.
     */
    public function reopenFinancialYear(int $year): FinancialYear
    {
        $financialYear = FinancialYear::where('year', $year)->firstOrFail();
        $financialYear->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return $financialYear;
    }
}
