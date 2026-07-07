<?php

namespace App\Services;

use App\Models\User;
use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use Illuminate\Support\Collection;

class SavingsService
{
    const SLOT_AMOUNT = 2000;

    protected $runningChargeService;

    public function __construct(RunningChargeService $runningChargeService)
    {
        $this->runningChargeService = $runningChargeService;
    }

    /**
     * Record monthly savings for a member across selected slots.
     */
    public function recordSavings(User $member, string $month, array $slotIds, ?string $paymentDate = null): void
    {
        $paymentDate = $paymentDate ?? now();
        $monthDate = \Carbon\Carbon::parse($month)->startOfMonth()->format('Y-m-d');

        foreach ($slotIds as $slotId) {
            MonthlySaving::updateOrCreate(
                [
                    'user_id' => $member->id,
                    'savings_slot_id' => $slotId,
                    'month' => $monthDate,
                ],
                [
                    'amount' => self::SLOT_AMOUNT,
                    'payment_date' => $paymentDate,
                    'status' => 'paid',
                    'recorded_by' => auth()->id(),
                ]
            );
        }

        // Also record running charge for this member for the same month
        $this->runningChargeService->recordCharge($member->id, $monthDate);
    }

    /**
     * Update recorded monthly savings.
     */
    public function updateSavings(User $member, string $oldMonth, string $newMonth, array $slotIds, string $paymentDate): void
    {
        $oldMonthDate = \Carbon\Carbon::parse($oldMonth)->startOfMonth()->format('Y-m-d');

        // Delete old monthly savings
        MonthlySaving::where('user_id', $member->id)
            ->whereDate('month', $oldMonthDate)
            ->delete();

        // Delete old running charge
        \App\Models\RunningCharge::where('user_id', $member->id)
            ->whereDate('month', $oldMonthDate)
            ->delete();

        // Record new savings and running charge
        $this->recordSavings($member, $newMonth, $slotIds, $paymentDate);
    }

    /**
     * Delete recorded monthly savings.
     */
    public function deleteSavings(User $member, string $month): void
    {
        $monthDate = \Carbon\Carbon::parse($month)->startOfMonth()->format('Y-m-d');

        MonthlySaving::where('user_id', $member->id)
            ->whereDate('month', $monthDate)
            ->delete();

        \App\Models\RunningCharge::where('user_id', $member->id)
            ->whereDate('month', $monthDate)
            ->delete();
    }


    /**
     * Get total savings for a member.
     */
    public function getTotalSavings(User $member): float
    {
        return MonthlySaving::where('user_id', $member->id)
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Get current month savings for a member.
     */
    public function getCurrentMonthSavings(User $member): float
    {
        return MonthlySaving::where('user_id', $member->id)
            ->whereMonth('month', now()->month)
            ->whereYear('month', now()->year)
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Get total savings across all members for current month.
     */
    public function getTotalCurrentMonthSavings(): float
    {
        return MonthlySaving::whereMonth('month', now()->month)
            ->whereYear('month', now()->year)
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Get members with their slot counts for the savings list view.
     */
    public function getMembersWithSlots(): Collection
    {
        return User::where('role', 'member')
            ->with('savingsSlots')
            ->get();
    }

    /**
     * Get active members with slots.
     */
    public function getActiveMembersWithSlots(): Collection
    {
        return User::where('role', 'member')
            ->where('is_active', true)
            ->with('savingsSlots')
            ->get();
    }

    /**
     * Check if member has paid for a specific month.
     */
    public function hasPaidForMonth(User $member, string $month): bool
    {
        $activeSlots = $member->savingsSlots()->where('is_active', true)->count();
        $paidCount = MonthlySaving::where('user_id', $member->id)
            ->where('month', $month)
            ->where('status', 'paid')
            ->count();

        return $activeSlots > 0 && $paidCount >= $activeSlots;
    }
}