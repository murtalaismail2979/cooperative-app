<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Loan;
use App\Models\Expense;
use App\Models\Investment;
use App\Services\LoanService;
use App\Services\SavingsService;
use App\Services\InvestmentService;
use App\Services\ExpenseService;
use App\Services\RegistrationFeeService;
use App\Models\MonthlySaving;

class DashboardController extends Controller
{
    protected $loanService;
    protected $savingsService;
    protected $investmentService;
    protected $expenseService;
    protected $registrationFeeService;

    public function __construct(
        LoanService $loanService,
        SavingsService $savingsService,
        InvestmentService $investmentService,
        ExpenseService $expenseService,
        RegistrationFeeService $registrationFeeService
    ) {
        $this->loanService = $loanService;
        $this->savingsService = $savingsService;
        $this->investmentService = $investmentService;
        $this->expenseService = $expenseService;
        $this->registrationFeeService = $registrationFeeService;
    }

    public function index()
    {
        $loanStats = $this->loanService->getActiveLoansStats();
        $investmentStats = $this->investmentService->getInvestmentStats();
        $expenseStats = $this->expenseService->getExpenseStats();
        $regFeeStats = $this->registrationFeeService->getDashboardStats();

        $driver = \DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
        $monthExpr = $driver === 'sqlite' ? "strftime('%m', month)" : "MONTH(month)";

        $data = [
            'totalMembers' => User::where('role', 'member')->count(),
            'activeMembers' => User::where('role', 'member')->where('is_active', true)->count(),
            'activeLoans' => $loanStats['count'],
            'totalOutstanding' => $loanStats['totalOutstanding'],
            'pendingExpenses' => $expenseStats['pending'],
            'approvedExpenses' => $expenseStats['totalApproved'],
            'activeInvestments' => $investmentStats['count'],
            'totalInvestmentCapital' => $investmentStats['totalCapital'],
            'currentMonthSavings' => $this->savingsService->getTotalCurrentMonthSavings(),
            'totalSavings' => MonthlySaving::where('status', 'paid')->sum('amount'),
            'registrationFeeStats' => $regFeeStats,
            'savingsBreakdown' => MonthlySaving::selectRaw("{$yearExpr} as year, {$monthExpr} as month_num, SUM(amount) as total, COUNT(DISTINCT user_id) as members")
                ->where('status', 'paid')
                ->groupBy('year', 'month_num')
                ->orderBy('year', 'desc')
                ->orderBy('month_num', 'desc')
                ->take(12)
                ->get(),
            'recentMembers' => User::where('role', 'member')->latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('data'));
    }
}
