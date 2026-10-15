<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlySaving;
use App\Models\Loan;
use App\Models\RunningCharge;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Dividend;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function savings(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $driver = \DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
        $monthExpr = $driver === 'sqlite' ? "strftime('%m', month)" : "MONTH(month)";

        // 1. Monthly Breakdown
        $monthlySavingsQuery = MonthlySaving::selectRaw("{$yearExpr} as year, {$monthExpr} as month_num, SUM(amount) as total, COUNT(DISTINCT user_id) as members")
            ->where('status', 'paid');

        if ($cooperativeId) {
            $monthlySavingsQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $monthlySavings = $monthlySavingsQuery
            ->groupBy('year', 'month_num')
            ->orderBy('year', 'desc')
            ->orderBy('month_num', 'desc')
            ->paginate(12, ['*'], 'monthly_page')
            ->withQueryString();

        // 2. Yearly Savings by Member
        $yearlySearch = $request->input('yearly_search');
        $yearlyYear = $request->input('yearly_year');

        $yearExprOuter = $driver === 'sqlite' ? "strftime('%Y', monthly_savings.month)" : "YEAR(monthly_savings.month)";
        $monthYearExpr = $driver === 'sqlite' ? "strftime('%Y-%m', monthly_savings.month)" : "DATE_FORMAT(monthly_savings.month, '%Y-%m')";
        $readmitMonthYearExpr = $driver === 'sqlite' ? "strftime('%Y-%m', users.readmitted_at)" : "DATE_FORMAT(users.readmitted_at, '%Y-%m')";

        // 1) Find distinct member user_ids that match search and year criteria
        $yearlyUserIdsQuery = MonthlySaving::join('users', 'users.id', '=', 'monthly_savings.user_id')
            ->where('monthly_savings.status', 'paid')
            ->where('monthly_savings.amount', '>', 0)
            ->whereRaw("(users.readmitted_at IS NULL OR {$monthYearExpr} >= {$readmitMonthYearExpr})");

        if ($cooperativeId) {
            $yearlyUserIdsQuery->where('users.cooperative_id', $cooperativeId);
        }

        if ($yearlySearch) {
            $yearlyUserIdsQuery->where(function ($q) use ($yearlySearch) {
                $q->where('users.name', 'like', "%{$yearlySearch}%")
                  ->orWhere('users.member_code', 'like', "%{$yearlySearch}%");
            });
        }

        if ($yearlyYear) {
            if ($driver === 'sqlite') {
                $yearlyUserIdsQuery->whereRaw("strftime('%Y', monthly_savings.month) = ?", [$yearlyYear]);
            } else {
                $yearlyUserIdsQuery->whereRaw("YEAR(monthly_savings.month) = ?", [$yearlyYear]);
            }
        }

        $paginatedUsers = $yearlyUserIdsQuery
            ->select('monthly_savings.user_id')
            ->distinct()
            ->orderBy('monthly_savings.user_id', 'asc')
            ->paginate(10, ['*'], 'yearly_page')
            ->withQueryString();

        $pageUserIds = collect($paginatedUsers->items())->pluck('user_id')->filter()->toArray();

        if (!empty($pageUserIds)) {
            $yearlySavingsQuery = MonthlySaving::join('users', 'users.id', '=', 'monthly_savings.user_id')
                ->selectRaw("
                    monthly_savings.user_id,
                    {$yearExprOuter} as year,
                    SUM(monthly_savings.amount) as total_saved
                ")
                ->where('monthly_savings.status', 'paid')
                ->where('monthly_savings.amount', '>', 0)
                ->whereRaw("(users.readmitted_at IS NULL OR {$monthYearExpr} >= {$readmitMonthYearExpr})")
                ->whereIn('monthly_savings.user_id', $pageUserIds)
                ->with('user');

            if ($cooperativeId) {
                $yearlySavingsQuery->where('users.cooperative_id', $cooperativeId);
            }

            if ($yearlyYear) {
                if ($driver === 'sqlite') {
                    $yearlySavingsQuery->whereRaw("strftime('%Y', monthly_savings.month) = ?", [$yearlyYear]);
                } else {
                    $yearlySavingsQuery->whereRaw("YEAR(monthly_savings.month) = ?", [$yearlyYear]);
                }
            }

            $yearlyRecords = $yearlySavingsQuery
                ->groupBy('monthly_savings.user_id', \DB::raw($yearExprOuter))
                ->havingRaw('SUM(monthly_savings.amount) > 0')
                ->orderBy('monthly_savings.user_id', 'asc')
                ->orderBy('year', 'desc')
                ->get();
        } else {
            $yearlyRecords = collect();
        }

        // Calculate cumulative savings for items on current page
        $yearlyRecords->transform(function ($item) use ($driver) {
            $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
            $cumQuery = MonthlySaving::where('user_id', $item->user_id)
                ->where('status', 'paid')
                ->whereRaw("{$yearExpr} <= ?", [$item->year]);

            if ($item->user && ($startDate = $item->user->getEffectiveSavingsStartDate())) {
                $cumQuery->whereDate('month', '>=', $startDate->format('Y-m-d'));
            }

            $item->cumulative_saved = $cumQuery->sum('amount');
            return $item;
        });

        // Wrap current page records in paginator based on member count
        $yearlySavings = new \Illuminate\Pagination\LengthAwarePaginator(
            $yearlyRecords,
            $paginatedUsers->total(),
            $paginatedUsers->perPage(),
            $paginatedUsers->currentPage(),
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'yearly_page',
            ]
        );
        $yearlySavings->withQueryString();

        // Get list of years for selection in dropdown
        $availableYearsQuery = MonthlySaving::where('status', 'paid')
            ->where('amount', '>', 0);
        if ($cooperativeId) {
            $availableYearsQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $availableYears = $availableYearsQuery
            ->selectRaw("DISTINCT {$yearExpr} as year")
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->map(fn($y) => (int) $y)
            ->unique()
            ->toArray();

        // 3. Lifetime Savings by Member
        $lifetimeSearch = $request->input('lifetime_search');

        $lifetimeQuery = MonthlySaving::join('users', 'users.id', '=', 'monthly_savings.user_id')
            ->selectRaw("
                monthly_savings.user_id,
                MIN({$yearExprOuter}) as start_year,
                MAX({$yearExprOuter}) as end_year,
                SUM(monthly_savings.amount) as total_saved
            ")
            ->where('monthly_savings.status', 'paid')
            ->where('monthly_savings.amount', '>', 0)
            ->whereRaw("(users.readmitted_at IS NULL OR {$monthYearExpr} >= {$readmitMonthYearExpr})")
            ->with('user');

        if ($cooperativeId) {
            $lifetimeQuery->where('users.cooperative_id', $cooperativeId);
        }

        if ($lifetimeSearch) {
            $lifetimeQuery->where(function ($q) use ($lifetimeSearch) {
                $q->where('users.name', 'like', "%{$lifetimeSearch}%")
                  ->orWhere('users.member_code', 'like', "%{$lifetimeSearch}%");
            });
        }

        $lifetimeSavings = $lifetimeQuery
            ->groupBy('monthly_savings.user_id')
            ->havingRaw('SUM(monthly_savings.amount) > 0')
            ->orderBy('total_saved', 'desc')
            ->paginate(15, ['*'], 'lifetime_page')
            ->withQueryString();

        $totalSavingsQuery = MonthlySaving::where('status', 'paid');
        $totalChargesQuery = RunningCharge::where('status', 'paid');

        if ($cooperativeId) {
            $totalSavingsQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $totalChargesQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $totalSavings = $totalSavingsQuery->sum('amount');
        $totalCharges = $totalChargesQuery->sum('amount');

        // Determine which tab is active based on request
        $activeTab = $request->input('tab', 'monthly');

        $paginatedLoans = null;
        if ($activeTab === 'financing') {
            $yearExprGranted = $driver === 'sqlite' ? "strftime('%Y', date_granted)" : "YEAR(date_granted)";
            $monthExprGranted = $driver === 'sqlite' ? "strftime('%m', date_granted)" : "MONTH(date_granted)";

            $loanDatesQuery = Loan::selectRaw("{$yearExprGranted} as year, {$monthExprGranted} as month_num")
                ->whereNotNull('date_granted');

            if ($cooperativeId) {
                $loanDatesQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $loanDates = $loanDatesQuery
                ->groupBy('year', 'month_num')
                ->get()
                ->map(fn($item) => ['year' => (int) $item->year, 'month' => (int) $item->month_num])
                ->toArray();

            $allPeriods = [];
            foreach ($loanDates as $period) {
                $key = sprintf('%04d-%02d', $period['year'], $period['month']);
                $allPeriods[$key] = $period;
            }
            krsort($allPeriods);
            $periods = array_values($allPeriods);

            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $perPage = 12;
            $currentItems = array_slice($periods, ($currentPage - 1) * $perPage, $perPage);

            foreach ($currentItems as &$period) {
                $year = $period['year'];
                $month = $period['month'];

                if ($driver === 'sqlite') {
                    $loanSummaryQuery = Loan::whereRaw("strftime('%Y', date_granted) = ? AND strftime('%m', date_granted) = ?", [(string)$year, sprintf('%02d', $month)]);
                } else {
                    $loanSummaryQuery = Loan::whereRaw("YEAR(date_granted) = ? AND MONTH(date_granted) = ?", [$year, $month]);
                }

                if ($cooperativeId) {
                    $loanSummaryQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
                }

                $loanSummary = $loanSummaryQuery
                    ->selectRaw("COUNT(*) as count, SUM(principal_amount) as principal, SUM(total_amount - principal_amount) as profit")
                    ->first();

                $period['loan_count'] = (int) ($loanSummary->count ?? 0);
                $period['loan_principal'] = (float) ($loanSummary->principal ?? 0);
                $period['loan_profit'] = (float) ($loanSummary->profit ?? 0);
            }

            $paginatedLoans = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentItems,
                count($periods),
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page']
            );
        }

        $paginatedBusinesses = null;
        if ($activeTab === 'businesses') {
            $periods = $this->getInvestmentPeriods($cooperativeId);

            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $perPage = 12;
            $currentItems = array_slice($periods, ($currentPage - 1) * $perPage, $perPage);

            foreach ($currentItems as &$period) {
                $year = $period['year'];
                $month = $period['month'];

                if ($driver === 'sqlite') {
                    $invQuery = Investment::whereRaw("strftime('%Y', start_date) = ? AND strftime('%m', start_date) = ?", [(string)$year, sprintf('%02d', $month)]);
                } else {
                    $invQuery = Investment::whereRaw("YEAR(start_date) = ? AND MONTH(start_date) = ?", [$year, $month]);
                }

                if ($cooperativeId) {
                    $invQuery->whereHas('createdBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
                }

                $investments = $invQuery->get();

                $period['investment_count'] = $investments->count();
                $period['investment_capital'] = (float) $investments->sum('capital_amount');
                $period['investment_profit'] = (float) $investments->sum(function ($inv) {
                    return $inv->sharable_profit;
                });
            }

            $paginatedBusinesses = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentItems,
                count($periods),
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page']
            );
        }

        $chargesSearch = null;
        $chargesYear = null;
        $chargesMonth = null;
        $runningChargesReport = null;

        if ($activeTab === 'charges') {
            $chargesSearch = $request->input('charges_search');
            $chargesYear = $request->input('charges_year');
            $chargesMonth = $request->input('charges_month');

            $yearExprCharges = $driver === 'sqlite' ? "strftime('%Y', running_charges.month)" : "YEAR(running_charges.month)";
            $monthExprCharges = $driver === 'sqlite' ? "strftime('%m', running_charges.month)" : "MONTH(running_charges.month)";

            $chargesQuery = RunningCharge::selectRaw("
                    running_charges.*,
                    {$yearExprCharges} as year,
                    {$monthExprCharges} as month_num
                ")
                ->with('user');

            if ($cooperativeId) {
                $chargesQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            if ($chargesSearch) {
                $chargesQuery->whereHas('user', function ($q) use ($chargesSearch) {
                    $q->where('name', 'like', "%{$chargesSearch}%")
                      ->orWhere('member_code', 'like', "%{$chargesSearch}%");
                });
            }

            if ($chargesYear) {
                if ($driver === 'sqlite') {
                    $chargesQuery->whereRaw("strftime('%Y', running_charges.month) = ?", [$chargesYear]);
                } else {
                    $chargesQuery->whereRaw("YEAR(running_charges.month) = ?", [$chargesYear]);
                }
            }

            if ($chargesMonth) {
                if ($driver === 'sqlite') {
                    $chargesQuery->whereRaw("strftime('%m', running_charges.month) = ?", [sprintf('%02d', $chargesMonth)]);
                } else {
                    $chargesQuery->whereRaw("MONTH(running_charges.month) = ?", [$chargesMonth]);
                }
            }

            $runningChargesReport = $chargesQuery
                ->orderBy('running_charges.month', 'desc')
                ->orderBy('running_charges.user_id', 'asc')
                ->paginate(15, ['*'], 'charges_page')
                ->withQueryString();
        }

        $dividendsSearch = null;
        $paginatedDividends = null;
        $totalAllShared = 0.0;
        $totalAllPaid = 0.0;
        $totalAllPending = 0.0;
        $totalCooperativeDeductions = 0.0;
        $totalManagementDeductions = 0.0;
        $totalDeductedAmount = 0.0;

        if ($activeTab === 'dividends') {
            $dividendsSearch = $request->input('dividends_search');

            $memberQuery = \App\Models\User::where('role', 'member');
            if ($cooperativeId) {
                $memberQuery->where('cooperative_id', $cooperativeId);
            }

            if ($dividendsSearch) {
                $memberQuery->where(function ($q) use ($dividendsSearch) {
                    $q->where('name', 'like', "%{$dividendsSearch}%")
                      ->orWhere('member_code', 'like', "%{$dividendsSearch}%");
                });
            }

            $paginatedDividends = $memberQuery
                ->withSum('dividendPayouts as total_earned', 'amount')
                ->withSum(['dividendPayouts as total_paid' => function($q) {
                    $q->where('paid', true);
                }], 'amount')
                ->withSum(['dividendPayouts as total_pending' => function($q) {
                    $q->where('paid', false);
                }], 'amount')
                ->orderByRaw('CASE WHEN member_code IS NULL OR member_code = "" THEN 1 ELSE 0 END, member_code ASC, name ASC')
                ->paginate(15, ['*'], 'dividends_page')
                ->withQueryString();

            $payoutQuery = \App\Models\DividendPayout::query();
            $dividendQuery = \App\Models\Dividend::query();
            if ($cooperativeId) {
                $payoutQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
                $dividendQuery->whereHas('payouts.user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $totalAllShared = (float) (clone $payoutQuery)->sum('amount');
            $totalAllPaid = (float) (clone $payoutQuery)->where('paid', true)->sum('amount');
            $totalAllPending = (float) (clone $payoutQuery)->where('paid', false)->sum('amount');
            $totalCooperativeDeductions = (float) (clone $dividendQuery)->sum('cooperative_amount');
            $totalManagementDeductions = (float) (clone $dividendQuery)->sum('management_amount');
            $totalDeductedAmount = $totalCooperativeDeductions + $totalManagementDeductions;
        }

        $paginatedEarnings = null;
        $totalCooperativeEarnings = 0.0;
        $totalManagementEarnings = 0.0;
        $totalCombinedEarnings = 0.0;

        if ($activeTab === 'earnings') {
            list($headers, $rows) = $this->getEarningsReportData($cooperativeId);
            
            $earningsData = [];
            foreach ($rows as $row) {
                $earningsData[] = [
                    'year' => (int) $row[0],
                    'business_profit' => (float) $row[1],
                    'business_coop' => (float) $row[2],
                    'business_mgmt' => (float) $row[3],
                    'financing_profit' => (float) $row[4],
                    'financing_coop' => (float) $row[5],
                    'financing_mgmt' => (float) $row[6],
                    'coop_total' => (float) $row[7],
                    'mgmt_total' => (float) $row[8],
                    'grand_total' => (float) $row[9],
                ];
            }

            $totalCooperativeEarnings = array_sum(array_column($earningsData, 'coop_total'));
            $totalManagementEarnings = array_sum(array_column($earningsData, 'mgmt_total'));
            $totalCombinedEarnings = $totalCooperativeEarnings + $totalManagementEarnings;

            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $perPage = 12;
            $currentItems = array_slice($earningsData, ($currentPage - 1) * $perPage, $perPage);

            $paginatedEarnings = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentItems,
                count($earningsData),
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page']
            );
        }

        return view('admin.reports.savings', compact(
            'monthlySavings',
            'yearlySavings',
            'lifetimeSavings',
            'availableYears',
            'totalSavings',
            'totalCharges',
            'yearlySearch',
            'yearlyYear',
            'lifetimeSearch',
            'activeTab',
            'paginatedLoans',
            'paginatedBusinesses',
            'runningChargesReport',
            'chargesSearch',
            'chargesYear',
            'chargesMonth',
            'paginatedDividends',
            'dividendsSearch',
            'totalAllShared',
            'totalAllPaid',
            'totalAllPending',
            'totalCooperativeDeductions',
            'totalManagementDeductions',
            'totalDeductedAmount',
            'paginatedEarnings',
            'totalCooperativeEarnings',
            'totalManagementEarnings',
            'totalCombinedEarnings'
        ));
    }

    public function loans(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $loansQuery = Loan::with('user');
        $activeLoansQuery = Loan::where('status', 'active');
        $totalPrincipalQuery = Loan::query();
        $totalAmountQuery = Loan::query();

        if ($cooperativeId) {
            $loansQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $activeLoansQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $totalPrincipalQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $totalAmountQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $loans = $loansQuery->latest()->paginate(15);
        $activeLoans = $activeLoansQuery->get();
        $totalOutstanding = $activeLoans->sum('outstanding_balance');
        $totalPrincipal = $totalPrincipalQuery->sum('principal_amount');
        $totalProfit = $totalAmountQuery->sum('total_amount') - (clone $totalAmountQuery)->sum('principal_amount');

        return view('admin.reports.loans', compact('loans', 'totalOutstanding', 'totalPrincipal', 'totalProfit'));
    }

    public function financial(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $investmentQuery = Investment::query();
        $expenseQuery = Expense::where('status', 'approved');
        $loanQuery = Loan::query();
        $dividendQuery = Dividend::query();

        if ($cooperativeId) {
            $investmentQuery->whereHas('createdBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $expenseQuery->whereHas('requestedBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $loanQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $dividendQuery->whereHas('payouts.user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        // Revenue from investments
        $investmentReturns = $investmentQuery->sum('total_returns');
        
        // Expenses
        $totalExpenses = $expenseQuery->sum('amount');
        
        // Loan profit income
        $totalLoanProfit = $loanQuery->sum('total_amount') - (clone $loanQuery)->sum('principal_amount');
        
        // Total revenue
        $totalRevenue = $investmentReturns + $totalLoanProfit;
        $netIncome = $totalRevenue - $totalExpenses;

        $driver = \DB::connection()->getDriverName();
        
        $yearExprGranted = $driver === 'sqlite' ? "strftime('%Y', date_granted)" : "YEAR(date_granted)";
        $monthExprGranted = $driver === 'sqlite' ? "strftime('%m', date_granted)" : "MONTH(date_granted)";

        // Get unique periods
        $loanDatesQuery = Loan::selectRaw("
                {$yearExprGranted} as year,
                {$monthExprGranted} as month_num
            ")
            ->whereNotNull('date_granted');

        if ($cooperativeId) {
            $loanDatesQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $loanDates = $loanDatesQuery
            ->groupBy('year', 'month_num')
            ->get()
            ->map(fn($item) => ['year' => (int) $item->year, 'month' => (int) $item->month_num])
            ->toArray();

        $investmentDates = $this->getInvestmentPeriods($cooperativeId);

        $allPeriods = [];
        foreach (array_merge($loanDates, $investmentDates) as $period) {
            $key = sprintf('%04d-%02d', $period['year'], $period['month']);
            $allPeriods[$key] = $period;
        }
        
        krsort($allPeriods);
        $periods = array_values($allPeriods);

        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 12; // 12 months/periods per page
        $currentItems = array_slice($periods, ($currentPage - 1) * $perPage, $perPage);

        foreach ($currentItems as &$period) {
            $year = $period['year'];
            $month = $period['month'];

            // Query loans for this month/year
            if ($driver === 'sqlite') {
                $loanSummaryQuery = Loan::whereRaw("strftime('%Y', date_granted) = ? AND strftime('%m', date_granted) = ?", [(string)$year, sprintf('%02d', $month)]);
            } else {
                $loanSummaryQuery = Loan::whereRaw("YEAR(date_granted) = ? AND MONTH(date_granted) = ?", [$year, $month]);
            }

            if ($cooperativeId) {
                $loanSummaryQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $loanSummary = $loanSummaryQuery
                ->selectRaw("COUNT(*) as count, SUM(principal_amount) as principal, SUM(total_amount - principal_amount) as profit")
                ->first();

            // Query investments for this month/year
            if ($driver === 'sqlite') {
                $invQuery = Investment::whereRaw("strftime('%Y', start_date) = ? AND strftime('%m', start_date) = ?", [(string)$year, sprintf('%02d', $month)]);
            } else {
                $invQuery = Investment::whereRaw("YEAR(start_date) = ? AND MONTH(start_date) = ?", [$year, $month]);
            }

            if ($cooperativeId) {
                $invQuery->whereHas('createdBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $investments = $invQuery->get();

            $period['loan_count'] = (int) ($loanSummary->count ?? 0);
            $period['loan_principal'] = (float) ($loanSummary->principal ?? 0);
            $period['loan_profit'] = (float) ($loanSummary->profit ?? 0);
            $period['investment_count'] = $investments->count();
            $period['investment_capital'] = (float) $investments->sum('capital_amount');
            $period['investment_profit'] = (float) $investments->sum(function ($inv) {
                return $inv->sharable_profit;
            });
        }

        $cooperativeDeductions = (float) (clone $dividendQuery)->sum('cooperative_amount');
        $managementDeductions = (float) (clone $dividendQuery)->sum('management_amount');
        $totalDeductions = $cooperativeDeductions + $managementDeductions;
        $totalMemberDividends = (float) (clone $dividendQuery)->sum('total_dividend_amount');

        $paginatedPeriods = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            count($periods),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        return view('admin.reports.financial', compact(
            'investmentReturns', 'totalExpenses', 'totalLoanProfit', 'totalRevenue', 'netIncome',
            'paginatedPeriods', 'cooperativeDeductions', 'managementDeductions', 'totalDeductions', 'totalMemberDividends'
        ));
    }

    public function exportSavings(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $tab = $request->input('tab', 'monthly');
        if ($tab === 'yearly') {
            $search = $request->input('yearly_search');
            $year = $request->input('yearly_year');
            list($headers, $rows) = $this->getYearlySavingsData($search, $year, $cooperativeId);
            return $this->streamCsv($headers, $rows, 'yearly_savings_report_' . date('Y-m-d') . '.csv');
        } elseif ($tab === 'lifetime') {
            $search = $request->input('lifetime_search');
            list($headers, $rows) = $this->getLifetimeSavingsData($search, $cooperativeId);
            return $this->streamCsv($headers, $rows, 'lifetime_savings_report_' . date('Y-m-d') . '.csv');
        } elseif ($tab === 'financing') {
            list($headers, $rows) = $this->getFinancingActivityData($cooperativeId);
            return $this->streamCsv($headers, $rows, 'financing_report_' . date('Y-m-d') . '.csv');
        } elseif ($tab === 'businesses') {
            list($headers, $rows) = $this->getBusinessesActivityData($cooperativeId);
            return $this->streamCsv($headers, $rows, 'businesses_report_' . date('Y-m-d') . '.csv');
        } elseif ($tab === 'charges') {
            $search = $request->input('charges_search');
            $year = $request->input('charges_year');
            $month = $request->input('charges_month');
            list($headers, $rows) = $this->getRunningChargesReportData($search, $year, $month, $cooperativeId);
            return $this->streamCsv($headers, $rows, 'running_charges_report_' . date('Y-m-d') . '.csv');
        } elseif ($tab === 'dividends') {
            $search = $request->input('dividends_search');
            list($headers, $rows) = $this->getDividendsReportData($search, $cooperativeId);
            return $this->streamCsv($headers, $rows, 'dividends_report_' . date('Y-m-d') . '.csv');
        } elseif ($tab === 'earnings') {
            list($headers, $rows) = $this->getEarningsReportData($cooperativeId);
            return $this->streamCsv($headers, $rows, 'cooperative_and_management_earnings_report_' . date('Y-m-d') . '.csv');
        } elseif ($tab === 'registration_fees') {
            $search = $request->input('search');
            $status = $request->input('status');
            list($headers, $rows) = $this->getRegistrationFeesData($search, $status, $cooperativeId);
            return $this->streamCsv($headers, $rows, 'registration_fees_report_' . date('Y-m-d') . '.csv');
        } else {
            list($headers, $rows) = $this->getMonthlySavingsData($cooperativeId);
            return $this->streamCsv($headers, $rows, 'monthly_savings_report_' . date('Y-m-d') . '.csv');
        }
    }

    public function exportLoans(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        list($headers, $rows) = $this->getLoansData($cooperativeId);
        return $this->streamCsv($headers, $rows, 'financing_report_' . date('Y-m-d') . '.csv');
    }

    public function exportFinancial(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        list($summaryHeaders, $summaryRows) = $this->getFinancialData($cooperativeId);
        list($breakdownHeaders, $breakdownRows) = $this->getActivityBreakdownData($cooperativeId);

        $responseHeaders = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=financial_report_" . date('Y-m-d') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($summaryHeaders, $summaryRows, $breakdownHeaders, $breakdownRows) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            
            // Financial Summary Section
            fputcsv($file, ['FINANCIAL SUMMARY']);
            fputcsv($file, $summaryHeaders);
            foreach ($summaryRows as $row) {
                fputcsv($file, $row);
            }
            
            // Empty rows spacer
            fputcsv($file, []);
            fputcsv($file, []);
            
            // Financing and Businesses Breakdown Section
            fputcsv($file, ['FINANCING AND BUSINESSES BREAKDOWN']);
            fputcsv($file, $breakdownHeaders);
            foreach ($breakdownRows as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    public function exportAll(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $zipFileName = 'ylda_cooperative_all_reports_' . date('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            
            // Generate Monthly Savings CSV
            $monthlyFile = storage_path('app/monthly_savings_breakdown.csv');
            $this->writeCsvFile($monthlyFile, ...$this->getMonthlySavingsData($cooperativeId));
            $zip->addFile($monthlyFile, 'monthly_savings_breakdown.csv');

            // Generate Yearly Savings CSV
            $yearlyFile = storage_path('app/yearly_savings_by_member.csv');
            $this->writeCsvFile($yearlyFile, ...$this->getYearlySavingsData(null, null, $cooperativeId));
            $zip->addFile($yearlyFile, 'yearly_savings_by_member.csv');

            // Generate Lifetime Savings CSV
            $lifetimeFile = storage_path('app/lifetime_savings_by_member.csv');
            $this->writeCsvFile($lifetimeFile, ...$this->getLifetimeSavingsData(null, $cooperativeId));
            $zip->addFile($lifetimeFile, 'lifetime_savings_by_member.csv');

            // Generate Loans CSV
            $loansFile = storage_path('app/loans_report.csv');
            $this->writeCsvFile($loansFile, ...$this->getLoansData($cooperativeId));
            $zip->addFile($loansFile, 'loans_report.csv');

            // Generate Financial CSV
            $financialFile = storage_path('app/financial_summary.csv');
            $this->writeCsvFile($financialFile, ...$this->getFinancialData($cooperativeId));
            $zip->addFile($financialFile, 'financial_summary.csv');

            // Generate Financing & Businesses Breakdown CSV
            $breakdownFile = storage_path('app/financing_and_businesses_breakdown.csv');
            $this->writeCsvFile($breakdownFile, ...$this->getActivityBreakdownData($cooperativeId));
            $zip->addFile($breakdownFile, 'financing_and_businesses_breakdown.csv');

            // Generate Running Charges CSV
            $chargesFile = storage_path('app/running_charges_by_member.csv');
            $this->writeCsvFile($chargesFile, ...$this->getRunningChargesReportData(null, null, null, $cooperativeId));
            $zip->addFile($chargesFile, 'running_charges_by_member.csv');

            // Generate Dividends CSV
            $dividendsFile = storage_path('app/dividends_by_member.csv');
            $this->writeCsvFile($dividendsFile, ...$this->getDividendsReportData(null, $cooperativeId));
            $zip->addFile($dividendsFile, 'dividends_by_member.csv');

            // Generate Cooperative & Management Earnings CSV
            $earningsFile = storage_path('app/cooperative_and_management_earnings.csv');
            $this->writeCsvFile($earningsFile, ...$this->getEarningsReportData($cooperativeId));
            $zip->addFile($earningsFile, 'cooperative_and_management_earnings.csv');

            $zip->close();

            // Delete the temporary CSV files
            @unlink($monthlyFile);
            @unlink($yearlyFile);
            @unlink($lifetimeFile);
            @unlink($loansFile);
            @unlink($financialFile);
            @unlink($breakdownFile);
            @unlink($chargesFile);
            @unlink($dividendsFile);
            @unlink($earningsFile);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return redirect()->back()->with('error', 'Failed to generate ZIP archive.');
    }

    protected function getActivityBreakdownData(?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        
        $yearExprGranted = $driver === 'sqlite' ? "strftime('%Y', date_granted)" : "YEAR(date_granted)";
        $monthExprGranted = $driver === 'sqlite' ? "strftime('%m', date_granted)" : "MONTH(date_granted)";

        // Get unique periods
        $loanDatesQuery = Loan::selectRaw("
                {$yearExprGranted} as year,
                {$monthExprGranted} as month_num
            ")
            ->whereNotNull('date_granted');

        if ($cooperativeId) {
            $loanDatesQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $loanDates = $loanDatesQuery
            ->groupBy('year', 'month_num')
            ->get()
            ->map(fn($item) => ['year' => (int) $item->year, 'month' => (int) $item->month_num])
            ->toArray();

        $investmentDates = $this->getInvestmentPeriods($cooperativeId);

        $allPeriods = [];
        foreach (array_merge($loanDates, $investmentDates) as $period) {
            $key = sprintf('%04d-%02d', $period['year'], $period['month']);
            $allPeriods[$key] = $period;
        }
        
        krsort($allPeriods);
        $periods = array_values($allPeriods);

        $headers = [
            'Year', 
            'Month', 
            'Financing Count', 
            'Principal Amount (₦)', 
            'Projected Profit (₦)', 
            'Business Count', 
            'Capital Invested (₦)',
            'Business Sharable Profit (₦)'
        ];
        
        $rows = [];
        foreach ($periods as $period) {
            $year = $period['year'];
            $month = $period['month'];

            // Query loans for this month/year
            if ($driver === 'sqlite') {
                $loanSummaryQuery = Loan::whereRaw("strftime('%Y', date_granted) = ? AND strftime('%m', date_granted) = ?", [(string)$year, sprintf('%02d', $month)]);
            } else {
                $loanSummaryQuery = Loan::whereRaw("YEAR(date_granted) = ? AND MONTH(date_granted) = ?", [$year, $month]);
            }

            if ($cooperativeId) {
                $loanSummaryQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $loanSummary = $loanSummaryQuery
                ->selectRaw("COUNT(*) as count, SUM(principal_amount) as principal, SUM(total_amount - principal_amount) as profit")
                ->first();

            // Query investments for this month/year
            if ($driver === 'sqlite') {
                $invQuery = Investment::whereRaw("strftime('%Y', start_date) = ? AND strftime('%m', start_date) = ?", [(string)$year, sprintf('%02d', $month)]);
            } else {
                $invQuery = Investment::whereRaw("YEAR(start_date) = ? AND MONTH(start_date) = ?", [$year, $month]);
            }

            if ($cooperativeId) {
                $invQuery->whereHas('createdBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $investments = $invQuery->get();

            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            
            $rows[] = [
                $year,
                $monthName,
                (int) ($loanSummary->count ?? 0),
                number_format((float) ($loanSummary->principal ?? 0), 2, '.', ''),
                number_format((float) ($loanSummary->profit ?? 0), 2, '.', ''),
                $investments->count(),
                number_format((float) $investments->sum('capital_amount'), 2, '.', ''),
                number_format((float) $investments->sum(function ($inv) { return $inv->sharable_profit; }), 2, '.', '')
            ];
        }

        return [$headers, $rows];
    }

    protected function getFinancingActivityData(?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        $yearExprGranted = $driver === 'sqlite' ? "strftime('%Y', date_granted)" : "YEAR(date_granted)";
        $monthExprGranted = $driver === 'sqlite' ? "strftime('%m', date_granted)" : "MONTH(date_granted)";

        $loanDatesQuery = Loan::selectRaw("{$yearExprGranted} as year, {$monthExprGranted} as month_num")
            ->whereNotNull('date_granted');

        if ($cooperativeId) {
            $loanDatesQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $loanDates = $loanDatesQuery
            ->groupBy('year', 'month_num')
            ->get()
            ->map(fn($item) => ['year' => (int) $item->year, 'month' => (int) $item->month_num])
            ->toArray();

        $allPeriods = [];
        foreach ($loanDates as $period) {
            $key = sprintf('%04d-%02d', $period['year'], $period['month']);
            $allPeriods[$key] = $period;
        }
        krsort($allPeriods);
        $periods = array_values($allPeriods);

        $headers = ['Year', 'Month', 'Financing Count', 'Principal Amount (₦)', 'Projected Profit (₦)'];
        $rows = [];
        foreach ($periods as $period) {
            $year = $period['year'];
            $month = $period['month'];

            if ($driver === 'sqlite') {
                $loanSummaryQuery = Loan::whereRaw("strftime('%Y', date_granted) = ? AND strftime('%m', date_granted) = ?", [(string)$year, sprintf('%02d', $month)]);
            } else {
                $loanSummaryQuery = Loan::whereRaw("YEAR(date_granted) = ? AND MONTH(date_granted) = ?", [$year, $month]);
            }

            if ($cooperativeId) {
                $loanSummaryQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $loanSummary = $loanSummaryQuery
                ->selectRaw("COUNT(*) as count, SUM(principal_amount) as principal, SUM(total_amount - principal_amount) as profit")
                ->first();

            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $rows[] = [
                $year,
                $monthName,
                (int) ($loanSummary->count ?? 0),
                number_format((float) ($loanSummary->principal ?? 0), 2, '.', ''),
                number_format((float) ($loanSummary->profit ?? 0), 2, '.', '')
            ];
        }

        return [$headers, $rows];
    }

    protected function getBusinessesActivityData(?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        $periods = $this->getInvestmentPeriods($cooperativeId);

        $headers = ['Year', 'Month', 'Business Count', 'Capital Invested (₦)', 'Sharable Profit (₦)'];
        $rows = [];
        foreach ($periods as $period) {
            $year = $period['year'];
            $month = $period['month'];

            if ($driver === 'sqlite') {
                $invQuery = Investment::whereRaw("strftime('%Y', start_date) = ? AND strftime('%m', start_date) = ?", [(string)$year, sprintf('%02d', $month)]);
            } else {
                $invQuery = Investment::whereRaw("YEAR(start_date) = ? AND MONTH(start_date) = ?", [$year, $month]);
            }

            if ($cooperativeId) {
                $invQuery->whereHas('createdBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $investments = $invQuery->get();

            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $rows[] = [
                $year,
                $monthName,
                $investments->count(),
                number_format((float) $investments->sum('capital_amount'), 2, '.', ''),
                number_format((float) $investments->sum(function ($inv) { return $inv->sharable_profit; }), 2, '.', '')
            ];
        }

        return [$headers, $rows];
    }

    protected function getMonthlySavingsData(?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
        $monthExpr = $driver === 'sqlite' ? "strftime('%m', month)" : "MONTH(month)";

        $monthlySavingsQuery = MonthlySaving::selectRaw("{$yearExpr} as year, {$monthExpr} as month_num, SUM(amount) as total, COUNT(DISTINCT user_id) as members")
            ->where('status', 'paid');

        if ($cooperativeId) {
            $monthlySavingsQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $monthlySavings = $monthlySavingsQuery
            ->groupBy('year', 'month_num')
            ->orderBy('year', 'desc')
            ->orderBy('month_num', 'desc')
            ->get();

        $headers = ['Year', 'Month', 'Total Savings (₦)', 'Member Count'];
        $rows = [];
        foreach ($monthlySavings as $ms) {
            $monthName = date('F', mktime(0,0,0,(int)$ms->month_num,1));
            $rows[] = [
                $ms->year,
                $monthName,
                number_format($ms->total, 2, '.', ''),
                $ms->members
            ];
        }

        return [$headers, $rows];
    }

    protected function getYearlySavingsData(?string $search = null, ?string $year = null, ?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        $yearExprOuter = $driver === 'sqlite' ? "strftime('%Y', monthly_savings.month)" : "YEAR(monthly_savings.month)";
        $monthYearExpr = $driver === 'sqlite' ? "strftime('%Y-%m', monthly_savings.month)" : "DATE_FORMAT(monthly_savings.month, '%Y-%m')";
        $readmitMonthYearExpr = $driver === 'sqlite' ? "strftime('%Y-%m', users.readmitted_at)" : "DATE_FORMAT(users.readmitted_at, '%Y-%m')";

        $query = MonthlySaving::join('users', 'users.id', '=', 'monthly_savings.user_id')
            ->selectRaw("
                monthly_savings.user_id,
                {$yearExprOuter} as year,
                SUM(monthly_savings.amount) as total_saved
            ")
            ->where('monthly_savings.status', 'paid')
            ->where('monthly_savings.amount', '>', 0)
            ->whereRaw("(users.readmitted_at IS NULL OR {$monthYearExpr} >= {$readmitMonthYearExpr})")
            ->with('user');

        if ($cooperativeId) {
            $query->where('users.cooperative_id', $cooperativeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.member_code', 'like', "%{$search}%");
            });
        }

        if ($year) {
            if ($driver === 'sqlite') {
                $query->whereRaw("strftime('%Y', monthly_savings.month) = ?", [$year]);
            } else {
                $query->whereRaw("YEAR(monthly_savings.month) = ?", [$year]);
            }
        }

        $results = $query
            ->groupBy('monthly_savings.user_id', \DB::raw($yearExprOuter))
            ->havingRaw('SUM(monthly_savings.amount) > 0')
            ->orderBy('monthly_savings.user_id', 'asc')
            ->orderBy('year', 'desc')
            ->get();

        $headers = ['Member Code', 'Member Name', 'Year', 'Savings in Year (₦)', 'Cumulative Savings (₦)'];
        $rows = [];
        foreach ($results as $item) {
            $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
            $cumQuery = MonthlySaving::where('user_id', $item->user_id)
                ->where('status', 'paid')
                ->whereRaw("{$yearExpr} <= ?", [$item->year]);

            if ($item->user && ($startDate = $item->user->getEffectiveSavingsStartDate())) {
                $cumQuery->whereDate('month', '>=', $startDate->format('Y-m-d'));
            }

            $cumulative = $cumQuery->sum('amount');

            $rows[] = [
                $item->user->member_code ?? 'N/A',
                $item->user->name ?? 'N/A',
                $item->year,
                number_format($item->total_saved, 2, '.', ''),
                number_format($cumulative, 2, '.', '')
            ];
        }

        return [$headers, $rows];
    }

    protected function getLifetimeSavingsData(?string $search = null, ?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        $yearExprOuter = $driver === 'sqlite' ? "strftime('%Y', monthly_savings.month)" : "YEAR(monthly_savings.month)";
        $monthYearExpr = $driver === 'sqlite' ? "strftime('%Y-%m', monthly_savings.month)" : "DATE_FORMAT(monthly_savings.month, '%Y-%m')";
        $readmitMonthYearExpr = $driver === 'sqlite' ? "strftime('%Y-%m', users.readmitted_at)" : "DATE_FORMAT(users.readmitted_at, '%Y-%m')";

        $query = MonthlySaving::join('users', 'users.id', '=', 'monthly_savings.user_id')
            ->selectRaw("
                monthly_savings.user_id,
                MIN({$yearExprOuter}) as start_year,
                MAX({$yearExprOuter}) as end_year,
                SUM(monthly_savings.amount) as total_saved
            ")
            ->where('monthly_savings.status', 'paid')
            ->where('monthly_savings.amount', '>', 0)
            ->whereRaw("(users.readmitted_at IS NULL OR {$monthYearExpr} >= {$readmitMonthYearExpr})")
            ->with('user');

        if ($cooperativeId) {
            $query->where('users.cooperative_id', $cooperativeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.member_code', 'like', "%{$search}%");
            });
        }

        $results = $query
            ->groupBy('monthly_savings.user_id')
            ->orderBy('total_saved', 'desc')
            ->get();

        $headers = ['Member Code', 'Member Name', 'Contribution Period', 'Total Savings (₦)'];
        $rows = [];
        foreach ($results as $ls) {
            $period = ($ls->start_year == $ls->end_year) ? $ls->start_year : "{$ls->start_year} - {$ls->end_year}";
            $rows[] = [
                $ls->user->member_code ?? 'N/A',
                $ls->user->name ?? 'N/A',
                $period,
                number_format($ls->total_saved, 2, '.', '')
            ];
        }

        return [$headers, $rows];
    }

    protected function getLoansData(?int $cooperativeId = null)
    {
        $loansQuery = Loan::with('user');
        if ($cooperativeId) {
            $loansQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $loans = $loansQuery->latest()->get();
        $headers = ['Financing ID', 'Member Code', 'Member Name', 'Principal Amount (₦)', 'Total Financing Amount (₦)', 'Outstanding Balance (₦)', 'Status'];
        $rows = [];
        foreach ($loans as $l) {
            $rows[] = [
                $l->id,
                $l->user->member_code ?? 'N/A',
                $l->user->name ?? 'N/A',
                number_format($l->principal_amount, 2, '.', ''),
                number_format($l->total_amount, 2, '.', ''),
                number_format($l->outstanding_balance, 2, '.', ''),
                $l->status
            ];
        }

        return [$headers, $rows];
    }

    protected function getFinancialData(?int $cooperativeId = null)
    {
        $investmentQuery = Investment::query();
        $expenseQuery = Expense::where('status', 'approved');
        $loanQuery = Loan::query();
        $dividendQuery = Dividend::query();

        if ($cooperativeId) {
            $investmentQuery->whereHas('createdBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $expenseQuery->whereHas('requestedBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $loanQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $dividendQuery->whereHas('payouts.user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $investmentReturns = $investmentQuery->sum('total_returns');
        $totalExpenses = $expenseQuery->sum('amount');
        $totalLoanProfit = $loanQuery->sum('total_amount') - (clone $loanQuery)->sum('principal_amount');
        $totalRevenue = $investmentReturns + $totalLoanProfit;
        $netIncome = $totalRevenue - $totalExpenses;

        $cooperativeDeductions = (float) (clone $dividendQuery)->sum('cooperative_amount');
        $managementDeductions = (float) (clone $dividendQuery)->sum('management_amount');
        $totalDeductions = $cooperativeDeductions + $managementDeductions;
        $totalMemberDividends = (float) (clone $dividendQuery)->sum('total_dividend_amount');

        $headers = ['Financial Indicator', 'Amount (₦)'];
        $rows = [
            ['Investment Returns', number_format($investmentReturns, 2, '.', '')],
            ['Financing Profit', number_format($totalLoanProfit, 2, '.', '')],
            ['Total Revenue', number_format($totalRevenue, 2, '.', '')],
            ['Total Expenses', number_format($totalExpenses, 2, '.', '')],
            ['Net Income', number_format($netIncome, 2, '.', '')],
            ['Cooperative Earnings from Profit Deductions', number_format($cooperativeDeductions, 2, '.', '')],
            ['Management Earnings from Profit Deductions', number_format($managementDeductions, 2, '.', '')],
            ['Total Profit Deductions', number_format($totalDeductions, 2, '.', '')],
            ['Total Distributed to Members', number_format($totalMemberDividends, 2, '.', '')]
        ];

        return [$headers, $rows];
    }

    protected function streamCsv(array $headers, array $rows, string $filename)
    {
        $responseHeaders = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    protected function writeCsvFile(string $path, array $headers, array $rows)
    {
        $file = fopen($path, 'w');
        fputs($file, "\xEF\xBB\xBF");
        fputcsv($file, $headers);
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    }

    protected function getRunningChargesReportData(?string $search = null, ?string $year = null, ?string $month = null, ?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        $yearExprOuter = $driver === 'sqlite' ? "strftime('%Y', running_charges.month)" : "YEAR(running_charges.month)";
        $monthExprOuter = $driver === 'sqlite' ? "strftime('%m', running_charges.month)" : "MONTH(running_charges.month)";

        $query = RunningCharge::selectRaw("
                running_charges.*,
                {$yearExprOuter} as year,
                {$monthExprOuter} as month_num
            ")
            ->with('user');

        if ($cooperativeId) {
            $query->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        if ($year) {
            if ($driver === 'sqlite') {
                $query->whereRaw("strftime('%Y', running_charges.month) = ?", [$year]);
            } else {
                $query->whereRaw("YEAR(running_charges.month) = ?", [$year]);
            }
        }

        if ($month) {
            if ($driver === 'sqlite') {
                $query->whereRaw("strftime('%m', running_charges.month) = ?", [sprintf('%02d', $month)]);
            } else {
                $query->whereRaw("MONTH(running_charges.month) = ?", [$month]);
            }
        }

        $results = $query
            ->orderBy('running_charges.month', 'desc')
            ->orderBy('running_charges.user_id', 'asc')
            ->get();

        $headers = ['Member Code', 'Member Name', 'Year', 'Month', 'Amount (₦)', 'Status', 'Payment Date'];
        $rows = [];
        foreach ($results as $item) {
            $monthName = date('F', mktime(0, 0, 0, (int)$item->month_num, 1));
            $rows[] = [
                $item->user->member_code ?? 'N/A',
                $item->user->name ?? 'N/A',
                $item->year,
                $monthName,
                number_format($item->amount, 2, '.', ''),
                ucfirst($item->status),
                $item->payment_date ? \Carbon\Carbon::parse($item->payment_date)->format('Y-m-d') : 'N/A'
            ];
        }

        return [$headers, $rows];
    }

    protected function getInvestmentPeriods(?int $cooperativeId = null)
    {
        $invQuery = Investment::whereNotNull('start_date');
        if ($cooperativeId) {
            $invQuery->whereHas('createdBy', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $investments = $invQuery->get();
        $periods = [];
        foreach ($investments as $inv) {
            $start = \Carbon\Carbon::parse($inv->start_date);
            $key = $start->format('Y-m');
            $periods[$key] = [
                'year' => (int) $start->format('Y'),
                'month' => (int) $start->format('m'),
            ];
        }

        krsort($periods);
        return array_values($periods);
    }

    protected function getDividendsReportData(?string $search = null, ?int $cooperativeId = null)
    {
        $query = \App\Models\User::where('role', 'member');
        if ($cooperativeId) {
            $query->where('cooperative_id', $cooperativeId);
        }
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $results = $query
            ->withSum('dividendPayouts as total_earned', 'amount')
            ->withSum(['dividendPayouts as total_paid' => function($q) {
                $q->where('paid', true);
            }], 'amount')
            ->withSum(['dividendPayouts as total_pending' => function($q) {
                $q->where('paid', false);
            }], 'amount')
            ->orderBy('name')
            ->get();

        $headers = ['Member Code', 'Member Name', 'Total Earned (₦)', 'Total Paid (₦)', 'Total Pending (₦)'];
        $rows = [];
        foreach ($results as $item) {
            $rows[] = [
                $item->member_code ?? 'N/A',
                $item->name ?? 'N/A',
                number_format($item->total_earned ?? 0, 2, '.', ''),
                number_format($item->total_paid ?? 0, 2, '.', ''),
                number_format($item->total_pending ?? 0, 2, '.', '')
            ];
        }

        return [$headers, $rows];
    }

    protected function getEarningsReportData(?int $cooperativeId = null)
    {
        $driver = \DB::connection()->getDriverName();
        $yearExprGranted = $driver === 'sqlite' ? "strftime('%Y', date_granted)" : "YEAR(date_granted)";
        
        $dividendQuery = \App\Models\Dividend::query();
        $loanQuery = \App\Models\Loan::query();

        if ($cooperativeId) {
            $dividendQuery->whereHas('payouts.user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            $loanQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        $dividendYears = (clone $dividendQuery)->select('year')->distinct()->pluck('year')->toArray();
        $loanYears = (clone $loanQuery)->selectRaw("DISTINCT {$yearExprGranted} as year")
            ->whereNotNull('date_granted')
            ->pluck('year')
            ->map(fn($y) => (int)$y)
            ->toArray();
        
        $years = array_unique(array_merge($dividendYears, $loanYears));
        rsort($years);

        $headers = [
            'Year',
            'Business Profit (₦)',
            'Business Cooperative Earnings (5%) (₦)',
            'Business Management Earnings (5%) (₦)',
            'Financing Profit (₦)',
            'Financing Cooperative Earnings (5%) (₦)',
            'Financing Management Earnings (5%) (₦)',
            'Total Cooperative Earnings (₦)',
            'Total Management Earnings (₦)',
            'Total Combined Earnings (₦)'
        ];

        $rows = [];
        foreach ($years as $year) {
            $divYearQuery = \App\Models\Dividend::where('year', $year);
            if ($cooperativeId) {
                $divYearQuery->whereHas('payouts.user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $businessProfit = (float) (clone $divYearQuery)->sum('original_sharable_profit');
            $businessCoop = (float) (clone $divYearQuery)->sum('cooperative_amount');
            $businessMgmt = (float) (clone $divYearQuery)->sum('management_amount');

            if ($driver === 'sqlite') {
                $loanYearQuery = \App\Models\Loan::whereRaw("strftime('%Y', date_granted) = ?", [(string)$year]);
            } else {
                $loanYearQuery = \App\Models\Loan::whereRaw("YEAR(date_granted) = ?", [$year]);
            }

            if ($cooperativeId) {
                $loanYearQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
            }

            $financingProfit = (float) $loanYearQuery
                ->selectRaw("SUM(total_amount - principal_amount) as profit")
                ->value('profit');

            $financingCoop = round($financingProfit * 0.05, 2);
            $financingMgmt = round($financingProfit * 0.05, 2);

            $yearCoop = $businessCoop + $financingCoop;
            $yearMgmt = $businessMgmt + $financingMgmt;
            $yearTotal = $yearCoop + $yearMgmt;

            $rows[] = [
                $year,
                number_format($businessProfit, 2, '.', ''),
                number_format($businessCoop, 2, '.', ''),
                number_format($businessMgmt, 2, '.', ''),
                number_format($financingProfit, 2, '.', ''),
                number_format($financingCoop, 2, '.', ''),
                number_format($financingMgmt, 2, '.', ''),
                number_format($yearCoop, 2, '.', ''),
                number_format($yearMgmt, 2, '.', ''),
                number_format($yearTotal, 2, '.', '')
            ];
        }

        return [$headers, $rows];
    }

    protected function getRegistrationFeesData(?string $search = null, ?string $status = null, ?int $cooperativeId = null)
    {
        $query = \App\Models\User::where('role', 'member')->with('registrationFee');
        if ($cooperativeId) {
            $query->where('cooperative_id', $cooperativeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->whereHas('registrationFee', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $members = $query->get();

        $headers = ['Member Code', 'Member Name', 'Email', 'Registration Fee (₦)', 'Total Paid (₦)', 'Outstanding Balance (₦)', 'Status'];
        $rows = [];

        foreach ($members as $member) {
            $fee = $member->registrationFee;
            $feeAmount = $fee ? (float) $fee->fee_amount : 10000.00;
            $totalPaid = $fee ? (float) $fee->total_paid : 0.00;
            $outstanding = max(0.00, $feeAmount - $totalPaid);
            $statusText = $fee ? str_replace('_', ' ', ucfirst($fee->status)) : 'Unpaid';

            $rows[] = [
                $member->member_code ?? 'N/A',
                $member->name,
                $member->email,
                number_format($feeAmount, 2, '.', ''),
                number_format($totalPaid, 2, '.', ''),
                number_format($outstanding, 2, '.', ''),
                $statusText,
            ];
        }

        return [$headers, $rows];
    }
}