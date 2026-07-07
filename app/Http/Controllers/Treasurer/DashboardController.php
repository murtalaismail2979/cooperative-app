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
        $data = [
            'totalMembers' => User::where('role', 'member')->count(),
            'activeLoans' => Loan::where('status', 'active')->count(),
            'pendingApprovals' => \App\Models\Expense::where('status', 'pending')->count(),
            'currentMonthSavings' => $this->savingsService->getTotalCurrentMonthSavings(),
            'pendingSavings' => \App\Models\MonthlySaving::whereMonth('month', now()->month)
                ->whereYear('month', now()->year)
                ->where('status', 'pending')
                ->count(),
        ];

        return view('treasurer.dashboard', compact('data'));
    }
}
