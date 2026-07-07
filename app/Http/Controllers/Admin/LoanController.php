<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\StoreRepaymentRequest;
use App\Models\User;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    protected $loanService;

    public function __construct(LoanService $loanService)
    {
        $this->loanService = $loanService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Loan::with('user');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $loans = $query->latest()->paginate(15)->withQueryString();

        return view('admin.loans.index', compact('loans', 'search'));
    }

    public function create()
    {
        $members = User::where('role', 'member')->where('is_active', true)->get();
        return view('admin.loans.create', compact('members'));
    }

    public function store(StoreLoanRequest $request)
    {
        $member = User::findOrFail($request->user_id);
        $this->loanService->createLoan(
            $member,
            $request->principal_amount,
            $request->date_granted,
            $request->filled('profit_rate') ? (float) $request->profit_rate : null,
            $request->filled('duration_months') ? (int) $request->duration_months : null
        );

        return redirect()->route('admin.loans.index')->with('success', 'Loan granted successfully.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['user', 'repayments', 'approver']);
        return view('admin.loans.show', compact('loan'));
    }

    public function addRepayment(StoreRepaymentRequest $request, Loan $loan)
    {
        $this->loanService->recordRepayment($loan, $request->amount, $request->payment_date);

        return redirect()->route('admin.loans.show', $loan)->with('success', 'Repayment recorded.');
    }

    public function edit(Loan $loan)
    {
        $members = User::where('role', 'member')->where('is_active', true)->get();
        return view('admin.loans.edit', compact('loan', 'members'));
    }

    public function update(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'principal_amount' => 'required|numeric|min:1',
            'profit_rate' => 'nullable|numeric|min:0',
            'duration_months' => 'nullable|integer|min:1',
            'date_granted' => 'required|date',
            'status' => 'required|in:active,fully_paid',
        ]);

        $this->loanService->updateLoan($loan, $validated);

        return redirect()->route('admin.loans.show', $loan)->with('success', 'Loan details updated successfully.');
    }

    public function destroy(Loan $loan)
    {
        $this->loanService->deleteLoan($loan);
        return redirect()->route('admin.loans.index')->with('success', 'Loan and associated repayments deleted successfully.');
    }

    public function editRepayment(Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        return view('admin.loans.repayments.edit', compact('loan', 'repayment'));
    }

    public function updateRepayment(Request $request, Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        $this->loanService->updateRepayment($repayment, $validated['amount'], $validated['payment_date']);

        return redirect()->route('admin.loans.show', $loan)->with('success', 'Repayment updated successfully.');
    }

    public function destroyRepayment(Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        $this->loanService->deleteRepayment($repayment);
        return redirect()->route('admin.loans.show', $loan)->with('success', 'Repayment deleted successfully.');
    }
}

