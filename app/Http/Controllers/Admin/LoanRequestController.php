<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveLoanRequestRequest;
use App\Http\Requests\RejectLoanRequestRequest;
use App\Models\LoanRequest;
use App\Services\LoanRequestService;
use App\Services\SavingsService;
use DomainException;
use Illuminate\Http\Request;

class LoanRequestController extends Controller
{
    protected $loanRequestService;
    protected $savingsService;

    public function __construct(LoanRequestService $loanRequestService, SavingsService $savingsService)
    {
        $this->loanRequestService = $loanRequestService;
        $this->savingsService = $savingsService;
    }

    /**
     * Display listing of financing requests.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $query = LoanRequest::with(['user', 'reviewer', 'loan']);

        if ($cooperativeId) {
            $query->where('cooperative_id', $cooperativeId);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $requests = $query->latest('id')->paginate(15)->withQueryString();
        $stats = $this->loanRequestService->getSummaryStats($cooperativeId);

        return view('admin.loan_requests.index', compact('requests', 'stats', 'status', 'search'));
    }

    /**
     * Display a specific financing request with applicant financial metrics.
     */
    public function show(LoanRequest $loanRequest)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $loanRequest->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized access to financing request of another cooperative.');
        }

        $loanRequest->load(['user.savingsSlots', 'user.loans.repayments', 'reviewer', 'loan']);

        $member = $loanRequest->user;
        $currentSavings = $this->savingsService->getTotalSavings($member);
        $maxEligibleNow = round($currentSavings * LoanRequestService::SAVINGS_MULTIPLIER, 2);
        $activeLoan = $member->loans()->where('status', 'active')->first();
        $pastLoans = $member->loans()->latest('date_granted')->get();

        return view('admin.loan_requests.show', compact(
            'loanRequest',
            'member',
            'currentSavings',
            'maxEligibleNow',
            'activeLoan',
            'pastLoans'
        ));
    }

    /**
     * Show edit form for a pending financing request.
     */
    public function edit(LoanRequest $loanRequest)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $loanRequest->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized access to financing request of another cooperative.');
        }

        if (!$loanRequest->isPending()) {
            return redirect()->route('admin.loan-requests.show', $loanRequest)
                ->withErrors(['general' => 'Only pending financing requests can be edited.']);
        }

        $member = $loanRequest->user;
        $currentSavings = (float) $this->savingsService->getTotalSavings($member);
        $maxEligibleNow = round($currentSavings * LoanRequestService::SAVINGS_MULTIPLIER, 2);

        return view('admin.loan_requests.edit', compact(
            'loanRequest',
            'member',
            'currentSavings',
            'maxEligibleNow'
        ));
    }

    /**
     * Update requested amount and duration for a pending financing request.
     */
    public function update(\App\Http\Requests\UpdateLoanRequestRequest $request, LoanRequest $loanRequest)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $loanRequest->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized to edit financing request of another cooperative.');
        }

        try {
            $this->loanRequestService->updateRequest($loanRequest, $request->validated(), $user);
            return redirect()->route('admin.loan-requests.show', $loanRequest)
                ->with('success', 'Financing request details (amount and payment duration) updated successfully.');
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * Approve and disburse financing request into an active loan.
     */
    public function approve(ApproveLoanRequestRequest $request, LoanRequest $loanRequest)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $loanRequest->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized to approve financing request of another cooperative.');
        }

        try {
            $loan = $this->loanRequestService->approveRequest($loanRequest, $request->validated(), $user);
            return redirect()->route('admin.loan-requests.show', $loanRequest)
                ->with('success', "Financing request has been approved and granted as Financing #{$loan->id}.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * Decline financing request with explanation.
     */
    public function reject(RejectLoanRequestRequest $request, LoanRequest $loanRequest)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $loanRequest->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized to decline financing request of another cooperative.');
        }

        try {
            $this->loanRequestService->rejectRequest(
                $loanRequest,
                $request->rejection_reason,
                $request->admin_notes,
                $user
            );
            return redirect()->route('admin.loan-requests.show', $loanRequest)
                ->with('success', 'Financing request has been declined.');
        } catch (DomainException $e) {
            return redirect()->back()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }
}
