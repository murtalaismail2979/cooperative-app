<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MonthlySaving;
use App\Models\RunningCharge;
use App\Services\SavingsService;
use App\Services\DividendService;

class DashboardController extends Controller
{
    protected $savingsService;
    protected $dividendService;

    public function __construct(SavingsService $savingsService, DividendService $dividendService)
    {
        $this->savingsService = $savingsService;
        $this->dividendService = $dividendService;
    }

    public function index()
    {
        $user = auth()->user();
        $user->load(['savingsSlots', 'nextOfKin']);

        $history = $this->getSavingsHistory($user);

        $data = [
            'totalSlots' => $user->savingsSlots->where('is_active', true)->count(),
            'currentMonthSavings' => $this->savingsService->getCurrentMonthSavings($user),
            'totalSavings' => $this->savingsService->getTotalSavings($user),
            'activeLoan' => $user->loans()->where('status', 'active')->first(),
            'runningChargesPaid' => RunningCharge::where('user_id', $user->id)
                ->where('status', 'paid')
                ->count(),
            'recentSavings' => $history->take(6),
        ];

        return view('member.dashboard', compact('data'));
    }

    public function savings()
    {
        $user = auth()->user();
        $history = $this->getSavingsHistory($user);

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

        return view('member.savings', compact('savings', 'totalSavings'));
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
            ->selectRaw('month, COUNT(id) as slots_count, SUM(amount) as total_amount, MAX(payment_date) as latest_payment_date')
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

    public function dividends()
    {
        $user = auth()->user();
        $payouts = $user->dividendPayouts()->with('dividend.investment')->latest()->paginate(15);
        $summary = $this->dividendService->getMemberDividendSummary($user);
        $totalDividends = $summary['total_amount'];
        return view('member.dividends', compact('payouts', 'totalDividends'));
    }
}
