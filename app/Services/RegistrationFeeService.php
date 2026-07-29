<?php

namespace App\Services;

use App\Models\User;
use App\Models\RegistrationFeeSetting;
use App\Models\RegistrationFeeHistory;
use App\Models\RegistrationFee;
use App\Models\RegistrationFeePayment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrationFeeService
{
    const DEFAULT_FEE_AMOUNT = 1000.00;

    /**
     * Get the current active registration fee amount.
     */
    public function getCurrentFeeAmount(): float
    {
        $setting = RegistrationFeeSetting::first();
        return $setting ? (float) $setting->amount : self::DEFAULT_FEE_AMOUNT;
    }

    /**
     * Update the system registration fee amount with audit trail.
     */
    public function updateFeeAmount(float $newAmount, ?string $reason = null, ?int $changedBy = null): RegistrationFeeSetting
    {
        if ($newAmount < 0) {
            throw new InvalidArgumentException('Registration fee amount cannot be negative.');
        }

        return DB::transaction(function () use ($newAmount, $reason, $changedBy) {
            $setting = RegistrationFeeSetting::first();
            $oldAmount = $setting ? (float) $setting->amount : self::DEFAULT_FEE_AMOUNT;

            if (!$setting) {
                $setting = RegistrationFeeSetting::create([
                    'amount' => $newAmount,
                    'updated_by' => $changedBy,
                ]);
            } else {
                $setting->update([
                    'amount' => $newAmount,
                    'updated_by' => $changedBy,
                ]);
            }

            RegistrationFeeHistory::create([
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'changed_by' => $changedBy,
                'reason' => $reason ?? 'Fee configuration updated',
            ]);

            return $setting;
        });
    }

    /**
     * Create or retrieve registration fee obligation for a member.
     */
    public function createObligationForMember(User $member, ?float $amount = null, string $status = 'unpaid', ?string $notes = null): RegistrationFee
    {
        $feeAmount = $amount ?? $this->getCurrentFeeAmount();

        return RegistrationFee::firstOrCreate(
            ['user_id' => $member->id],
            [
                'fee_amount' => $feeAmount,
                'total_paid' => 0.00,
                'status' => $status,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Record a registration fee payment.
     */
    public function recordPayment(
        User|RegistrationFee $feeOrUser,
        float $amount,
        string $paymentDate,
        string $paymentMethod = 'Cash',
        ?string $referenceNumber = null,
        ?int $recordedBy = null
    ): RegistrationFeePayment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($feeOrUser, $amount, $paymentDate, $paymentMethod, $referenceNumber, $recordedBy) {
            $registrationFee = $feeOrUser instanceof RegistrationFee
                ? $feeOrUser
                : $this->createObligationForMember($feeOrUser);

            $receiptNumber = $this->generateReceiptNumber();

            $payment = RegistrationFeePayment::create([
                'registration_fee_id' => $registrationFee->id,
                'user_id' => $registrationFee->user_id,
                'receipt_number' => $receiptNumber,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'status' => 'completed',
                'recorded_by' => $recordedBy ?? auth()->id(),
            ]);

            $registrationFee->updatePaymentTotals();

            return $payment;
        });
    }

    /**
     * Cancel/reverse a registration fee payment.
     */
    public function cancelPayment(RegistrationFeePayment $payment, string $reason, ?int $cancelledBy = null): RegistrationFeePayment
    {
        if ($payment->status === 'cancelled') {
            throw new InvalidArgumentException('Payment is already cancelled.');
        }

        return DB::transaction(function () use ($payment, $reason, $cancelledBy) {
            $payment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy ?? auth()->id(),
                'cancelled_at' => now(),
            ]);

            $payment->registrationFee->updatePaymentTotals();

            return $payment;
        });
    }

    /**
     * Reconcile an existing member's registration fee status manually.
     */
    public function reconcileMember(
        User $member,
        string $status,
        ?float $feeAmount = null,
        float $amountPaid = 0.00,
        ?string $notes = null
    ): RegistrationFee {
        return DB::transaction(function () use ($member, $status, $feeAmount, $amountPaid, $notes) {
            $registrationFee = RegistrationFee::where('user_id', $member->id)->first();

            $defaultFee = $feeAmount ?? $this->getCurrentFeeAmount();

            if (!$registrationFee) {
                $registrationFee = RegistrationFee::create([
                    'user_id' => $member->id,
                    'fee_amount' => $defaultFee,
                    'total_paid' => $amountPaid,
                    'status' => $status,
                    'notes' => $notes,
                ]);
            } else {
                $registrationFee->update([
                    'fee_amount' => $feeAmount ?? $registrationFee->fee_amount,
                    'total_paid' => $amountPaid,
                    'status' => $status,
                    'notes' => $notes ?? $registrationFee->notes,
                ]);
            }

            return $registrationFee;
        });
    }

    /**
     * Generate unique receipt number: REC-REG-YYYY-XXXXX
     */
    public function generateReceiptNumber(): string
    {
        $year = date('Y');
        $lastPayment = RegistrationFeePayment::where('receipt_number', 'like', "REC-REG-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPayment && $lastPayment->receipt_number) {
            $parts = explode('-', $lastPayment->receipt_number);
            $lastNum = (int) end($parts);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return sprintf('REC-REG-%s-%05d', $year, $nextNum);
    }

    /**
     * Get summary metrics for Admin & Reports.
     */
    public function getDashboardStats(?string $startDate = null, ?string $endDate = null): array
    {
        $memberCount = User::where('role', 'member')->count();

        $query = RegistrationFee::query();

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $fees = $query->get();

        $fullyPaidCount = $fees->where('status', 'fully_paid')->count();
        $partiallyPaidCount = $fees->where('status', 'partially_paid')->count();
        $unpaidCount = $fees->where('status', 'unpaid')->count();
        $requiresVerificationCount = $fees->where('status', 'requires_verification')->count();

        $totalExpected = $fees->sum('fee_amount');
        $totalCollected = RegistrationFeePayment::where('status', 'completed')
            ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('payment_date', '<=', $endDate))
            ->sum('amount');
        
        $totalOutstanding = max(0.00, $totalExpected - $totalCollected);

        return [
            'totalMembers' => $memberCount,
            'fullyPaidCount' => $fullyPaidCount,
            'partiallyPaidCount' => $partiallyPaidCount,
            'unpaidCount' => $unpaidCount,
            'requiresVerificationCount' => $requiresVerificationCount,
            'outstandingCount' => $partiallyPaidCount + $unpaidCount + $requiresVerificationCount,
            'totalExpected' => (float) $totalExpected,
            'totalCollected' => (float) $totalCollected,
            'totalOutstanding' => (float) $totalOutstanding,
            'currentFeeAmount' => $this->getCurrentFeeAmount(),
        ];
    }
}
