<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Loan;
use App\Services\SavingsService;
use App\Services\ExpenseService;

class DashboardController extends Controller
{
    protected $savingsService;
    protected $expenseService;

    public function __construct(SavingsService $savingsService, ExpenseService $expenseService)
    {
        $this->savingsService = $savingsService;
        $this->expenseService = $expenseService;
    }

    public function index()
    {
        $cooperativeId = auth()->user()->cooperative_id;

        $data = [
            'totalMembers' => User::where('role', 'member')->where('cooperative_id', $cooperativeId)->count(),
            'activeLoans' => Loan::where('status', 'active')->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId))->count(),
            'pendingApprovals' => \App\Models\Expense::where('status', 'pending')->whereHas('requestedBy', fn($q) => $q->where('cooperative_id', $cooperativeId))->count(),
            'currentMonthSavings' => $this->savingsService->getTotalCurrentMonthSavings($cooperativeId),
            'pendingSavings' => \App\Models\MonthlySaving::whereMonth('month', now()->month)
                ->whereYear('month', now()->year)
                ->where('status', 'pending')
                ->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId))
                ->count(),
        ];

        return view('treasurer.dashboard', compact('data'));
    }
}
