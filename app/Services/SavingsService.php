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
    public function recordSavings(User $member, string $month, array $slotIds, ?string $paymentDate = null, ?array $slotAmounts = null): void
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
                    'amount' => $slotAmounts !== null && array_key_exists($slotId, $slotAmounts)
                        ? (float) $slotAmounts[$slotId]
                        : self::SLOT_AMOUNT,
                    'payment_date' => $paymentDate,
                    'status' => 'paid',
                    'recorded_by' => auth()->id(),
                ]
            );
        }

        // Running charges apply only when the member has paid a positive savings amount.
        $hasPositiveSavings = MonthlySaving::where('user_id', $member->id)
            ->whereDate('month', $monthDate)
            ->where('status', 'paid')
            ->where('amount', '>', 0)
            ->exists();

        if ($hasPositiveSavings) {
            $this->runningChargeService->recordCharge($member->id, $monthDate);
        }
    }

    /**
     * Update recorded monthly savings.
     */
    public function updateSavings(User $member, string $oldMonth, string $newMonth, array $slotIds, string $paymentDate, ?array $slotAmounts = null): void
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
        $this->recordSavings($member, $newMonth, $slotIds, $paymentDate, $slotAmounts);
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

    /**
     * Get registered slots for a member as of a specific month.
     */
    public function getRegisteredSlotsForMonth(User $member, string $month): Collection
    {
        $adjustmentDate = \Carbon\Carbon::parse($month)->endOfMonth();

        // Check slot history on or before adjustmentDate
        $latestHistoryBefore = $member->slotHistories()
            ->where('created_at', '<=', $adjustmentDate)
            ->latest('created_at')
            ->first();

        if ($latestHistoryBefore) {
            $allowedMaxSlotNumber = (int) $latestHistoryBefore->current_slots;
        } else {
            // If there's an earliest history after adjustmentDate, take its previous_slots
            $earliestHistoryAfter = $member->slotHistories()
                ->orderBy('created_at', 'asc')
                ->first();

            if ($earliestHistoryAfter) {
                $allowedMaxSlotNumber = (int) $earliestHistoryAfter->previous_slots;
            } else {
                // Fallback to active savings slots count or total savings slots count
                $allowedMaxSlotNumber = $member->savingsSlots()->where('is_active', true)->count();
                if ($allowedMaxSlotNumber === 0) {
                    $allowedMaxSlotNumber = $member->savingsSlots()->count();
                }
            }
        }

        $paidSlotIds = MonthlySaving::where('user_id', $member->id)
            ->where('month', \Carbon\Carbon::parse($month)->startOfMonth()->format('Y-m-d'))
            ->pluck('savings_slot_id')
            ->toArray();

        return $member->savingsSlots()
            ->orderBy('slot_number')
            ->get()
            ->filter(function ($slot) use ($allowedMaxSlotNumber, $paidSlotIds) {
                return $slot->slot_number <= $allowedMaxSlotNumber || in_array($slot->id, $paidSlotIds);
            });
    }
}