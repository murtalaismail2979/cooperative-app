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

        return view('member.savings', compact('savings', 'totalSavings', 'years', 'selectedYear'));
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

        if ($start->gt($end)) {
            $start = $end->copy();
        }

        $months = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $months[] = $current->format('Y-m-d');
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

    public function loans()
    {
        $user = auth()->user();
        $loans = $user->loans()->with('repayments')->latest()->get();
        return view('member.loans', compact('loans'));
    }

    public function dividends(Request $request)
    {
        $user = auth()->user();

        $month = $request->input('month');
        $year = $request->input('year');

        $baseQuery = $user->dividendPayouts()->with('dividend.investment');

        if ($year) {
            $baseQuery->where(function ($q) use ($year) {
                $q->whereHas('dividend.investment', function ($q2) use ($year) {
                    $q2->whereYear('start_date', $year);
                })
                ->orWhereHas('dividend', function ($q2) use ($year) {
                    $q2->where('year', $year)
                       ->orWhereYear('distributed_at', $year);
                })
                ->orWhereYear('paid_date', $year);
            });
        }

        if ($month) {
            $baseQuery->where(function ($q) use ($month) {
                $q->whereHas('dividend.investment', function ($q2) use ($month) {
                    $q2->whereMonth('start_date', $month);
                })
                ->orWhereHas('dividend', function ($q2) use ($month) {
                    $q2->whereMonth('distributed_at', $month)
                       ->orWhereMonth('created_at', $month);
                })
                ->orWhereMonth('paid_date', $month)
                ->orWhereMonth('created_at', $month);
            });
        }

        $payouts = (clone $baseQuery)->latest()->paginate(15)->withQueryString();

        $summary = $this->dividendService->getMemberDividendSummary($user);
        $adjustments = \App\Models\DividendAdjustment::where('user_id', $user->id)->with('reconciliation')->latest()->get();
        $totalDividends = $summary['final_entitlement'];

        // Filtered calculations
        $grossOriginal = (float) (clone $baseQuery)->where('amount', '>', 0)->sum('amount');
        $businessLosses = abs((float) (clone $baseQuery)->where('amount', '<', 0)->sum('amount'));

        if ($year) {
            $selectedYearAdjustment = \App\Models\DividendAdjustment::where('user_id', $user->id)
                ->where('year', $year)
                ->whereHas('reconciliation', function ($q) {
                    $q->where('total_recognized_loss', '>', 0);
                })
                ->first();
            $annualLossAdjustment = $selectedYearAdjustment ? (float) $selectedYearAdjustment->loss_adjustment_amount : 0.00;
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
        $years = \App\Models\Dividend::selectRaw('year as yr')
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
