<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MonthlySaving;
use App\Models\RunningCharge;
use App\Services\SavingsService;
use App\Services\DividendService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $savingsService;
    protected $dividendService;
    protected $registrationFeeService;

    public function __construct(SavingsService $savingsService, DividendService $dividendService, \App\Services\RegistrationFeeService $registrationFeeService)
    {
        $this->savingsService = $savingsService;
        $this->dividendService = $dividendService;
        $this->registrationFeeService = $registrationFeeService;
    }

    public function index()
    {
        $user = auth()->user();
        $user->load(['savingsSlots', 'nextOfKin']);

        $history = $this->getSavingsHistory($user);
        $registrationFee = $user->registrationFee ?: $this->registrationFeeService->createObligationForMember($user);

        $data = [
            'totalSlots' => $user->savingsSlots->where('is_active', true)->count(),
            'currentMonthSavings' => $this->savingsService->getCurrentMonthSavings($user),
            'totalSavings' => $this->savingsService->getTotalSavings($user),
            'activeLoan' => $user->loans()->where('status', 'active')->first(),
            'runningChargesPaid' => RunningCharge::where('user_id', $user->id)
                ->where('status', 'paid')
                ->count(),
            'registrationFee' => $registrationFee,
            'recentSavings' => $history->take(6),
        ];

        return view('member.dashboard', compact('data'));
    }

    public function savings(Request $request)
    {
        $user = auth()->user();
        $selectedYear = $request->input('year');

        $history = $this->getSavingsHistory($user);

        if ($selectedYear) {
            $history = $history->filter(function ($item) use ($selectedYear) {
                return (int) \Carbon\Carbon::parse($item->month)->format('Y') === (int) $selectedYear;
            })->values();
        }

        $page = request()->get('page', 1);
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        $paginatedItems = $history->slice($offset, $perPage)->all();
        
        $savings = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $history->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $totalSavings = $this->savingsService->getTotalSavings($user);

        $driver = \DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
        $yearsFromDb = MonthlySaving::where('user_id', $user->id)
            ->selectRaw("DISTINCT {$yearExpr} as yr")
            ->whereNotNull('month')
            ->orderBy('yr', 'desc')
            ->pluck('yr')
            ->map(fn($y) => (int)$y)
            ->filter()
            ->toArray();

        $regYear = (int) ($user->registration_year ?? date('Y'));
        $currentYear = (int) date('Y');
        $years = array_unique(array_merge($yearsFromDb, [$regYear, $currentYear]));
        rsort($years);
        $years = array_values($years);

        $quickViewYear = $selectedYear ? (int) $selectedYear : $currentYear;
        $monthExpr = $driver === 'sqlite' ? "CAST(strftime('%m', month) AS INTEGER)" : "MONTH(month)";

        // Monthly savings status for quickViewYear
        $yearSavings = MonthlySaving::where('user_id', $user->id)
            ->whereRaw("{$yearExpr} = ?", [(string) $quickViewYear])
            ->selectRaw("{$monthExpr} as month_num, SUM(amount) as total_amount, MAX(payment_date) as max_payment_date")
            ->groupBy(\DB::raw($monthExpr))
            ->get()
            ->keyBy('month_num');

        $quickViewStatus = [];
        $quickViewPaidCount = 0;
        $quickViewTotalAmount = 0.0;
        $isYear2021 = (int) $quickViewYear === 2021;
        $isYear2022 = (int) $quickViewYear === 2022;
        $quickViewPossibleMonths = $isYear2021 ? 3 : ($isYear2022 ? 4 : 12);

        $readmitHistory = $user->slotHistories()
            ->where('reason', 'like', 'Re-admitted%')
            ->latest('created_at')
            ->first();
        $readmitDate = $readmitHistory ? \Carbon\Carbon::parse($readmitHistory->created_at) : null;

        for ($m = 1; $m <= 12; $m++) {
            $isBeforeInception = ($isYear2021 && $m < 10);
            $isBeforeReadmission = $readmitDate && ((int) $quickViewYear === (int) $readmitDate->year && $m < (int) $readmitDate->month);
            $isBeforeRegistration = ((int) $quickViewYear === (int) $user->registration_year && !empty($user->registration_month) && $m < (int) $user->registration_month);
            $isNotApplicable = $isBeforeInception || ($isYear2022 && $m > 4) || $isBeforeReadmission || $isBeforeRegistration;

            if ($isBeforeInception) {
                $reason = 'Before Savings Inception (Started Oct 2021)';
            } elseif ($isYear2022 && $m > 4) {
                $reason = 'Not Applicable (Savings held Jan–Apr only in 2022)';
            } elseif ($isBeforeReadmission) {
                $reason = 'Inactive (Before Re-admission in ' . $readmitDate->format('M Y') . ')';
            } elseif ($isBeforeRegistration) {
                $reason = 'Not Applicable (Registered in ' . date('M Y', mktime(0, 0, 0, $user->registration_month, 1, $user->registration_year)) . ')';
            } else {
                $reason = 'Not Applicable';
            }

            $rec = $yearSavings->get($m);
            $isPaid = $rec && (float) $rec->total_amount > 0;
            if ($isPaid) {
                $quickViewPaidCount++;
                $quickViewTotalAmount += (float) $rec->total_amount;
            }
            $quickViewStatus[$m] = [
                'is_paid' => $isPaid,
                'is_before_inception' => $isBeforeInception,
                'is_not_applicable' => $isNotApplicable,
                'reason' => $reason,
                'amount' => $rec ? (float) $rec->total_amount : 0.0,
                'payment_date' => $rec && $rec->max_payment_date ? \Carbon\Carbon::parse($rec->max_payment_date)->format('d/m/Y') : null,
            ];
        }

        // All years overview for multi-year quick view
        $allYearsSavings = MonthlySaving::where('user_id', $user->id)
            ->selectRaw("{$yearExpr} as yr, {$monthExpr} as month_num, SUM(amount) as total_amount")
            ->groupBy(\DB::raw("{$yearExpr}"), \DB::raw($monthExpr))
            ->get();

        $yearlyOverview = [];
        foreach ($years as $yr) {
            $yrRecords = $allYearsSavings->where('yr', (string) $yr);
            $paidCount = 0;
            $yrTotal = 0.0;
            $possibleMonths = (int) $yr === 2021 ? 3 : ((int) $yr === 2022 ? 4 : 12);
            $monthsStatus = [];
            for ($m = 1; $m <= 12; $m++) {
                $isBeforeInception = ((int) $yr === 2021 && $m < 10);
                $isNotApplicable = $isBeforeInception || ((int) $yr === 2022 && $m > 4);
                $rec = $yrRecords->firstWhere('month_num', $m);
                $isPaid = $rec && (float) $rec->total_amount > 0;
                if ($isPaid) {
                    $paidCount++;
                    $yrTotal += (float) $rec->total_amount;
                }
                $monthsStatus[$m] = [
                    'is_paid' => $isPaid,
                    'is_before_inception' => $isBeforeInception,
                    'is_not_applicable' => $isNotApplicable,
                    'amount' => $rec ? (float) $rec->total_amount : 0.0,
                ];
            }
            $yearlyOverview[$yr] = [
                'year' => $yr,
                'paid_count' => $paidCount,
                'possible_months' => $possibleMonths,
                'total_amount' => $yrTotal,
                'months' => $monthsStatus,
            ];
        }

        return view('member.savings', compact(
            'savings', 'totalSavings', 'years', 'selectedYear',
            'quickViewYear', 'quickViewStatus', 'quickViewPaidCount', 'quickViewPossibleMonths', 'quickViewTotalAmount', 'yearlyOverview'
        ));
    }

    private function getSavingsHistory($user)
    {
        $earliestSaving = MonthlySaving::where('user_id', $user->id)->min('month');
        $regYearDate = $user->registration_year ? \Carbon\Carbon::create($user->registration_year, 1, 1)->startOfMonth() : null;
        $createdDate = $user->created_at ? \Carbon\Carbon::parse($user->created_at)->startOfMonth() : null;

        $dates = array_filter([
            $earliestSaving ? \Carbon\Carbon::parse($earliestSaving)->startOfMonth() : null,
            $regYearDate,
            $createdDate
        ]);

        $start = !empty($dates) ? collect($dates)->min() : now()->startOfYear();
        $end = now()->startOfMonth();

        // YLDA started savings in October 2021 - do not generate savings history prior to October 2021
        $coopSavingsStart = \Carbon\Carbon::create(2021, 10, 1)->startOfMonth();
        if ($start->lt($coopSavingsStart)) {
            $start = $coopSavingsStart->copy();
        }

        if ($start->gt($end)) {
            $start = $end->copy();
        }

        $months = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $year = (int) $current->format('Y');
            $month = (int) $current->format('n');
            // Skip Jan-Sep 2021 (started Oct 2021) and May-Dec 2022 (savings held Jan-Apr 2022 only)
            $isNotApplicable = ($year === 2021 && $month < 10) || ($year === 2022 && $month > 4);
            if (!$isNotApplicable) {
                $months[] = $current->format('Y-m-d');
            }
            $current->addMonth();
        }

        $paidSavings = MonthlySaving::where('user_id', $user->id)
            ->selectRaw('month, CASE WHEN SUM(amount) > 0 THEN CAST(ROUND(SUM(amount) / 2000) AS INTEGER) ELSE COUNT(id) END as slots_count, SUM(amount) as total_amount, MAX(payment_date) as latest_payment_date')
            ->groupBy('month')
            ->get()
            ->keyBy(function($item) {
                return \Carbon\Carbon::parse($item->month)->startOfMonth()->format('Y-m-d');
            });

        $history = collect();
        foreach ($months as $monthStr) {
            if (isset($paidSavings[$monthStr])) {
                $item = $paidSavings[$monthStr];
                $item->status = 'paid';
                $history->push($item);
            } else {
                $history->push((object)[
                    'month' => $monthStr,
                    'slots_count' => 0,
                    'total_amount' => 0.00,
                    'status' => 'unpaid',
                    'latest_payment_date' => null
                ]);
            }
        }

        return $history->sortByDesc('month')->values();
    }

    public function loans(Request $request)
    {
        $user = auth()->user();
        $year = $request->input('year');
        $month = $request->input('month');

        $query = $user->loans()->with('repayments');

        if (!empty($year)) {
            $query->whereYear('date_granted', $year);
        }

        if (!empty($month)) {
            $query->whereMonth('date_granted', $month);
        }

        $loans = $query->latest('date_granted')->latest('id')->get();

        $years = $user->loans()
            ->whereNotNull('date_granted')
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

        return view('member.loans', compact('loans', 'year', 'month', 'years', 'months'));
    }

    public function dividends(Request $request)
    {
        $user = auth()->user();

        $month = $request->input('month');
        $year = $request->input('year');

        $baseQuery = $user->dividendPayouts()->with('dividend.investment');

        if ($year) {
            $baseQuery->whereHas('dividend', function ($q) use ($year) {
                $q->where('year', $year)
                  ->orWhere(function ($q2) use ($year) {
                      $q2->whereNull('year')
                         ->whereHas('investment', function ($q3) use ($year) {
                             $q3->whereYear('start_date', $year);
                         });
                  });
            });
        }

        if ($month) {
            $baseQuery->where(function ($q) use ($month) {
                $q->whereHas('dividend.investment', function ($q2) use ($month) {
                    $q2->whereMonth('start_date', $month);
                })
                ->orWhereHas('dividend', function ($q2) use ($month) {
                    $q2->whereMonth('distributed_at', $month);
                });
            });
        }

        $payouts = (clone $baseQuery)->latest()->paginate(15)->withQueryString();

        $summary = $this->dividendService->getMemberDividendSummary($user);

        $adjustmentsQuery = \App\Models\DividendAdjustment::where('user_id', $user->id)
            ->with('reconciliation');
        if ($year) {
            $adjustmentsQuery->where('year', $year);
        }
        $adjustments = $adjustmentsQuery->latest()->get();

        $totalDividends = $summary['final_entitlement'];

        // Filtered calculations
        $grossOriginal = (float) (clone $baseQuery)->where('amount', '>', 0)->sum('amount');
        $businessLosses = abs((float) (clone $baseQuery)->where('amount', '<', 0)->sum('amount'));

        if ($year) {
            $annualLossAdjustment = (float) \App\Models\DividendAdjustment::where('user_id', $user->id)
                ->where('year', $year)
                ->whereHas('reconciliation', function ($q) {
                    $q->where('total_recognized_loss', '>', 0);
                })
                ->sum('loss_adjustment_amount');
        } else {
            $annualLossAdjustment = (float) \App\Models\DividendAdjustment::where('user_id', $user->id)
                ->whereHas('reconciliation', function ($q) {
                    $q->where('total_recognized_loss', '>', 0);
                })
                ->sum('loss_adjustment_amount');
        }

        $selectedYearLoss = $businessLosses + $annualLossAdjustment;
        $filteredTotalDividend = $grossOriginal > 0 ? $grossOriginal : (float) (clone $baseQuery)->sum('amount');
        $selectedYearFinal = max(0.00, $filteredTotalDividend - $selectedYearLoss);
        $filteredTotalPaid = (float) (clone $baseQuery)->where('paid', true)->where('amount', '>', 0)->sum('amount');

        // Get available years for filtering
        $yearsQuery = \App\Models\Dividend::query();
        if ($user->cooperative_id) {
            $yearsQuery->whereHas('payouts.user', fn($u) => $u->where('cooperative_id', $user->cooperative_id));
        }
        $years = $yearsQuery->selectRaw('year as yr')
            ->whereNotNull('year')
            ->distinct()
            ->pluck('yr')
            ->map(fn($y) => (int)$y)
            ->filter()
            ->toArray();

        $currentYearNum = (int) date('Y');
        if (!in_array($currentYearNum, $years)) {
            array_unshift($years, $currentYearNum);
        }
        $years = array_values(array_unique($years));
        rsort($years);

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return view('member.dividends', compact(
            'payouts',
            'totalDividends',
            'summary',
            'adjustments',
            'years',
            'months',
            'year',
            'month',
            'filteredTotalDividend',
            'filteredTotalPaid',
            'selectedYearLoss',
            'selectedYearFinal'
        ));
    }
}
