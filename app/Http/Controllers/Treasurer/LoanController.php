<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepaymentRequest;
use App\Models\Loan;
use App\Models\User;
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
        $cooperativeId = auth()->user()->cooperative_id;

        $search = $request->input('search');
        $memberId = $request->input('member_id');
        $year = $request->input('year');
        $month = $request->input('month');

        $query = Loan::with('user')
            ->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        if ($memberId) {
            $query->where('user_id', $memberId);
        }

        if (!empty($year)) {
            $query->whereYear('date_granted', $year);
        }

        if (!empty($month)) {
            $query->whereMonth('date_granted', $month);
        }

        $loans = $query->latest('date_granted')->latest('id')->paginate(15)->withQueryString();
        $members = User::where('role', 'member')->where('cooperative_id', $cooperativeId)->orderBy('name')->get();

        $years = Loan::whereNotNull('date_granted')
            ->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId))
            ->pluck('date_granted')
            ->map(function ($date) {
                return $date instanceof \Carbon\Carbon ? $date->year : \Carbon\Carbon::parse($date)->year;
            })
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return view('treasurer.loans.index', compact('loans', 'search', 'memberId', 'members', 'year', 'month', 'years', 'months'));
    }

    public function show(Loan $loan)
    {
        $cooperativeId = auth()->user()->cooperative_id;
        $loan->load(['user', 'repayments', 'approver']);

        $previousLoan = Loan::whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId))->where('id', '<', $loan->id)->latest('id')->first();
        $nextLoan = Loan::whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId))->where('id', '>', $loan->id)->oldest('id')->first();
        $allLoans = Loan::with('user')->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId))->orderBy('id', 'desc')->get();

        return view('treasurer.loans.show', compact('loan', 'previousLoan', 'nextLoan', 'allLoans'));
    }

    public function addRepayment(StoreRepaymentRequest $request, Loan $loan)
    {
        $this->loanService->recordRepayment($loan, $request->amount, $request->payment_date);

        return redirect()->route('treasurer.loans.show', $loan)->with('success', 'Repayment recorded.');
    }

    public function edit(Loan $loan)
    {
        $cooperativeId = auth()->user()->cooperative_id;
        $members = User::where('role', 'member')->where('cooperative_id', $cooperativeId)->where('is_active', true)->get();
        return view('treasurer.loans.edit', compact('loan', 'members'));
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

        return redirect()->route('treasurer.loans.show', $loan)->with('success', 'Loan details updated successfully.');
    }

    public function destroy(Loan $loan)
    {
        $this->loanService->deleteLoan($loan);
        return redirect()->route('treasurer.loans.index')->with('success', 'Loan and associated repayments deleted successfully.');
    }

    public function editRepayment(Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        return view('treasurer.loans.repayments.edit', compact('loan', 'repayment'));
    }

    public function updateRepayment(Request $request, Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        $this->loanService->updateRepayment($repayment, $validated['amount'], $validated['payment_date']);

        return redirect()->route('treasurer.loans.show', $loan)->with('success', 'Repayment updated successfully.');
    }

    public function destroyRepayment(Loan $loan, \App\Models\LoanRepayment $repayment)
    {
        $this->loanService->deleteRepayment($repayment);
        return redirect()->route('treasurer.loans.show', $loan)->with('success', 'Repayment deleted successfully.');
    }
}
