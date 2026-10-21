<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class LoanRequestService
{
    const SAVINGS_MULTIPLIER = 3.0;

    protected $savingsService;
    protected $loanService;

    public function __construct(SavingsService $savingsService, LoanService $loanService)
    {
        $this->savingsService = $savingsService;
        $this->loanService = $loanService;
    }

    /**
     * Evaluate member's financing eligibility according to cooperative policies:
     * 1. Must be an approved and active member.
     * 2. Cannot have an existing active (unpaid) financing facility.
     * 3. Cannot have another pending request awaiting review.
     * 4. Must have positive savings balance (financing limit = 3x total savings).
     */
    public function checkEligibility(User $member): array
    {
        $totalSavings = (float) $this->savingsService->getTotalSavings($member);
        $maxEligibleAmount = round($totalSavings * self::SAVINGS_MULTIPLIER, 2);
        
        $activeLoan = $member->loans()->where('status', 'active')->first();
        $hasActiveLoan = (bool) $activeLoan;

        $pendingRequest = LoanRequest::where('user_id', $member->id)
            ->where('status', LoanRequest::STATUS_PENDING)
            ->first();
        $hasPendingRequest = (bool) $pendingRequest;

        $isEligible = true;
        $reasons = [];

        if (!$member->isApprovedMembership()) {
            $isEligible = false;
            $reasons[] = 'Your membership application is currently pending executive approval.';
        }

        if (!$member->is_active) {
            $isEligible = false;
            $reasons[] = 'Your membership account is currently inactive.';
        }

        if ($hasActiveLoan) {
            $isEligible = false;
            $outstanding = number_format($activeLoan->outstanding_balance, 2);
            $reasons[] = "You currently have an active financing facility (Financing #{$activeLoan->id}) with an outstanding balance of ₦{$outstanding}. Cooperative policy requires all existing financing to be fully repaid before requesting a new facility.";
        }

        if ($hasPendingRequest) {
            $isEligible = false;
            $reqAmount = number_format($pendingRequest->amount_requested, 2);
            $reasons[] = "You already have a pending financing request for ₦{$reqAmount} (Request #{$pendingRequest->id}) currently under executive review.";
        }

        if ($totalSavings <= 0) {
            $isEligible = false;
            $reasons[] = 'You do not have any recorded savings yet. Financing eligibility is based on 3 times your cumulative savings balance.';
        }

        return [
            'is_eligible' => $isEligible,
            'reasons' => $reasons,
            'total_savings' => $totalSavings,
            'max_eligible_amount' => $maxEligibleAmount,
            'savings_multiplier' => self::SAVINGS_MULTIPLIER,
            'has_active_loan' => $hasActiveLoan,
            'active_loan' => $activeLoan,
            'has_pending_request' => $hasPendingRequest,
            'pending_request' => $pendingRequest,
            'profit_rate_tiers' => [
                ['label' => '1 – 2 Months', 'rate' => 5.0, 'min' => 1, 'max' => 2],
                ['label' => '3 – 6 Months', 'rate' => 10.0, 'min' => 3, 'max' => 6],
                ['label' => '7 – 12 Months', 'rate' => 20.0, 'min' => 7, 'max' => 12],
            ],
            'default_profit_rate' => self::getProfitRateForDuration(12),
            'default_duration_months' => LoanService::DEFAULT_DURATION_MONTHS,
        ];
    }

    /**
     * Get the tiered profit rate based on the repayment duration:
     * 1. 1 month to 2 months = 5%
     * 2. 3 months to 6 months = 10%
     * 3. 7 months to 12 months = 20%
     */
    public static function getProfitRateForDuration(int $months): float
    {
        if ($months <= 2) {
            return 5.0;
        } elseif ($months <= 6) {
            return 10.0;
        } else {
            return 20.0;
        }
    }

    /**
     * Submit a new financing request for an eligible member.
     */
    public function submitRequest(User $member, array $data): LoanRequest
    {
        $eligibility = $this->checkEligibility($member);

        if (!$eligibility['is_eligible']) {
            throw new DomainException(implode(' ', $eligibility['reasons']));
        }

        $amount = (float) $data['amount_requested'];
        $duration = (int) ($data['duration_months'] ?? LoanService::DEFAULT_DURATION_MONTHS);
        $purpose = !empty($data['purpose']) ? trim($data['purpose']) : null;

        if ($amount <= 0) {
            throw new DomainException('The requested financing amount must be greater than zero.');
        }

        if ($amount > $eligibility['max_eligible_amount']) {
            $formattedReq = number_format($amount, 2);
            $formattedSavings = number_format($eligibility['total_savings'], 2);
            $formattedMax = number_format($eligibility['max_eligible_amount'], 2);
            throw new DomainException(
                "The requested amount (₦{$formattedReq}) exceeds your maximum financing limit of 3 times your total savings (₦{$formattedSavings}). Your maximum allowable financing is ₦{$formattedMax}."
            );
        }

        return LoanRequest::create([
            'user_id' => $member->id,
            'cooperative_id' => $member->cooperative_id,
            'amount_requested' => $amount,
            'duration_months' => $duration,
            'purpose' => $purpose,
            'member_total_savings_at_request' => $eligibility['total_savings'],
            'max_eligible_amount_at_request' => $eligibility['max_eligible_amount'],
            'status' => LoanRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Update a pending financing request's requested amount and payment duration (Admin privilege).
     */
    public function updateRequest(LoanRequest $request, array $data, ?User $editor = null): LoanRequest
    {
        if (!$request->isPending()) {
            throw new DomainException('Only pending financing requests can be edited.');
        }

        $member = $request->user;
        $currentSavings = (float) $this->savingsService->getTotalSavings($member);
        $maxEligible = round($currentSavings * self::SAVINGS_MULTIPLIER, 2);

        $amount = (float) $data['amount_requested'];
        $duration = (int) $data['duration_months'];

        if ($amount <= 0) {
            throw new DomainException('The requested financing amount must be greater than zero.');
        }

        if ($amount > $maxEligible) {
            $formattedReq = number_format($amount, 2);
            $formattedSavings = number_format($currentSavings, 2);
            $formattedMax = number_format($maxEligible, 2);
            throw new DomainException(
                "The updated requested amount (₦{$formattedReq}) exceeds the member's maximum financing limit of 3 times total savings (₦{$formattedSavings}). Maximum allowable is ₦{$formattedMax}."
            );
        }

        $updateData = [
            'amount_requested' => $amount,
            'duration_months' => $duration,
        ];

        if (array_key_exists('purpose', $data)) {
            $updateData['purpose'] = !empty($data['purpose']) ? trim($data['purpose']) : null;
        }

        if (array_key_exists('admin_notes', $data)) {
            $updateData['admin_notes'] = !empty($data['admin_notes']) ? trim($data['admin_notes']) : null;
        }

        $request->update($updateData);

        return $request->fresh();
    }

    /**
     * Executive approval & disbursement of a financing request into an active loan.
     */
    public function approveRequest(LoanRequest $request, array $data = [], ?User $reviewer = null): Loan
    {
        if (!$request->isPending()) {
            throw new DomainException('This request is not pending review.');
        }

        $reviewerId = $reviewer ? $reviewer->id : auth()->id();

        // Check if member got an active loan between submission and review
        if ($request->user->loans()->where('status', 'active')->exists()) {
            throw new DomainException('Member currently has an active financing facility. Multiple active facilities are not permitted.');
        }

        $principalAmount = isset($data['approved_amount']) && $data['approved_amount'] !== null && $data['approved_amount'] !== ''
            ? (float) $data['approved_amount']
            : (float) $request->amount_requested;

        $durationMonths = isset($data['approved_duration_months']) && $data['approved_duration_months'] !== null && $data['approved_duration_months'] !== ''
            ? (int) $data['approved_duration_months']
            : (int) $request->duration_months;

        $profitRate = isset($data['approved_profit_rate']) && $data['approved_profit_rate'] !== null && $data['approved_profit_rate'] !== ''
            ? (float) $data['approved_profit_rate']
            : self::getProfitRateForDuration($durationMonths);

        $dateGranted = !empty($data['date_granted']) ? $data['date_granted'] : now()->format('Y-m-d');
        $adminNotes = $data['admin_notes'] ?? null;

        return DB::transaction(function () use ($request, $principalAmount, $durationMonths, $profitRate, $dateGranted, $adminNotes, $reviewerId) {
            $loan = $this->loanService->createLoan(
                $request->user,
                $principalAmount,
                $dateGranted,
                $profitRate,
                $durationMonths
            );

            $request->update([
                'status' => LoanRequest::STATUS_APPROVED,
                'approved_amount' => $principalAmount,
                'approved_duration_months' => $durationMonths,
                'approved_profit_rate' => $profitRate,
                'date_granted' => $dateGranted,
                'loan_id' => $loan->id,
                'admin_notes' => $adminNotes,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            return $loan;
        });
    }

    /**
     * Decline a financing request with explanation.
     */
    public function rejectRequest(LoanRequest $request, string $reason, ?string $adminNotes = null, ?User $reviewer = null): void
    {
        if (!$request->isPending()) {
            throw new DomainException('This request is not pending review.');
        }

        $reviewerId = $reviewer ? $reviewer->id : auth()->id();

        $request->update([
            'status' => LoanRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'admin_notes' => $adminNotes,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Cancel a pending financing request by the member.
     */
    public function cancelRequest(LoanRequest $request, User $member): void
    {
        if ($request->user_id !== $member->id) {
            throw new DomainException('Unauthorized to cancel this request.');
        }

        if (!$request->isPending()) {
            throw new DomainException('Only pending financing requests can be cancelled.');
        }

        $request->update([
            'status' => LoanRequest::STATUS_CANCELLED,
        ]);
    }

    /**
     * Count pending requests for sidebar badge & alerts.
     */
    public function getPendingRequestsCount(?int $cooperativeId = null): int
    {
        $query = LoanRequest::where('status', LoanRequest::STATUS_PENDING);
        if ($cooperativeId) {
            $query->where('cooperative_id', $cooperativeId);
        }
        return $query->count();
    }

    /**
     * Get summary statistics for admin view.
     */
    public function getSummaryStats(?int $cooperativeId = null): array
    {
        $baseQuery = LoanRequest::query();
        if ($cooperativeId) {
            $baseQuery->where('cooperative_id', $cooperativeId);
        }

        $pending = (clone $baseQuery)->where('status', LoanRequest::STATUS_PENDING);
        $approved = (clone $baseQuery)->where('status', LoanRequest::STATUS_APPROVED);
        $rejected = (clone $baseQuery)->where('status', LoanRequest::STATUS_REJECTED);

        return [
            'pendingCount' => $pending->count(),
            'pendingTotalAmount' => (float) $pending->sum('amount_requested'),
            'approvedCount' => $approved->count(),
            'approvedTotalAmount' => (float) $approved->sum('approved_amount'),
            'rejectedCount' => $rejected->count(),
        ];
    }
}
