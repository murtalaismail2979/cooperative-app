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

    public function index(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $loanStats = $this->loanService->getActiveLoansStats($cooperativeId);
        $investmentStats = $this->investmentService->getInvestmentStats($cooperativeId);
        $expenseStats = $this->expenseService->getExpenseStats($cooperativeId);
        $regFeeStats = $this->registrationFeeService->getDashboardStats(null, null, $cooperativeId);

        $driver = \DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
        $monthExpr = $driver === 'sqlite' ? "strftime('%m', month)" : "MONTH(month)";

        $membersQuery = User::where('role', 'member');
        if ($cooperativeId) {
            $membersQuery->where('cooperative_id', $cooperativeId);
        }

        $savingsQuery = MonthlySaving::where('status', 'paid');
        if ($cooperativeId) {
            $savingsQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $breakdownQuery = MonthlySaving::selectRaw("{$yearExpr} as year, {$monthExpr} as month_num, SUM(amount) as total, COUNT(DISTINCT user_id) as members")
            ->where('status', 'paid');
        if ($cooperativeId) {
            $breakdownQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $data = [
            'totalMembers' => (clone $membersQuery)->count(),
            'activeMembers' => (clone $membersQuery)->where('is_active', true)->count(),
            'pendingMembers' => (clone $membersQuery)->where('membership_status', 'pending')->count(),
            'activeLoans' => $loanStats['count'],
            'totalOutstanding' => $loanStats['totalOutstanding'],
            'pendingExpenses' => $expenseStats['pending'],
            'approvedExpenses' => $expenseStats['totalApproved'],
            'activeInvestments' => $investmentStats['count'],
            'totalInvestmentCapital' => $investmentStats['totalCapital'],
            'currentMonthSavings' => $this->savingsService->getTotalCurrentMonthSavings($cooperativeId),
            'totalSavings' => $savingsQuery->sum('amount'),
            'registrationFeeStats' => $regFeeStats,
            'savingsBreakdown' => $breakdownQuery
                ->groupBy('year', 'month_num')
                ->orderBy('year', 'desc')
                ->orderBy('month_num', 'desc')
                ->take(12)
                ->get(),
            'recentMembers' => (clone $membersQuery)->latest()->take(5)->get(),
            'selectedCooperativeId' => $cooperativeId,
        ];

        return view('admin.dashboard', compact('data'));
    }
}
