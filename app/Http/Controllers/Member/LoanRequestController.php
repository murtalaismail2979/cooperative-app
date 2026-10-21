<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanApplicationRequest;
use App\Models\LoanRequest;
use App\Services\LoanRequestService;
use DomainException;
use Illuminate\Http\Request;

class LoanRequestController extends Controller
{
    protected $loanRequestService;

    public function __construct(LoanRequestService $loanRequestService)
    {
        $this->loanRequestService = $loanRequestService;
    }

    /**
     * Show the financing application screen with pre-qualification metrics and live estimator.
     */
    public function create()
    {
        $user = auth()->user();
        $eligibility = $this->loanRequestService->checkEligibility($user);
        $recentRequests = $user->loanRequests()->with('loan')->latest()->take(5)->get();

        return view('member.loans.request', compact('eligibility', 'recentRequests'));
    }

    /**
     * Submit a new financing request.
     */
    public function store(StoreLoanApplicationRequest $request)
    {
        $user = auth()->user();

        try {
            $loanRequest = $this->loanRequestService->submitRequest($user, $request->validated());
            $amount = number_format($loanRequest->amount_requested, 2);
            return redirect()->route('member.loans')
                ->with('success', "Your financing application for ₦{$amount} has been successfully submitted and is awaiting executive review.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['amount_requested' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a pending financing request.
     */
    public function cancel(LoanRequest $loanRequest)
    {
        $user = auth()->user();

        if ($loanRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this financing request.');
        }

        try {
            $this->loanRequestService->cancelRequest($loanRequest, $user);
            return redirect()->back()
                ->with('success', 'Your financing request has been cancelled.');
        } catch (DomainException $e) {
            return redirect()->back()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }
}
