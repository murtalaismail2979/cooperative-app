<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterDividendsRequest;
use App\Http\Requests\StoreDividendRequest;
use App\Http\Resources\DividendResource;
use App\Models\Dividend;
use App\Models\Investment;
use App\Models\User;
use App\Services\DividendService;

class DividendController extends Controller
{
    protected $dividendService;

    public function __construct(DividendService $dividendService)
    {
        $this->dividendService = $dividendService;
    }

    public function index(FilterDividendsRequest $request)
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize('viewAny', Dividend::class);
        }

        $sortBy = $request->input('sort_by', 'distributed_at');
        if (!in_array($sortBy, ['id', 'distributed_at', 'created_at', 'total_dividend_amount', 'year'])) {
            $sortBy = 'distributed_at';
        }
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $limit = (int) $request->input('limit', 15);

        $baseQuery = Dividend::query()
            ->with(['distributor', 'investment', 'payouts.user'])
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->year))
            ->when($request->filled('investment_id'), fn($q) => $q->where('investment_id', $request->investment_id))
            ->when($request->filled('investment_name'), fn($q) => 
                $q->whereHas('investment', fn($q2) => $q2->where('name', 'like', "%{$request->investment_name}%")))
            ->when($request->filled('member_name'), fn($q) => 
                $q->whereHas('payouts.user', function ($q2) use ($request) {
                    $q2->where(function ($sub) use ($request) {
                        $sub->where('name', 'like', "%{$request->member_name}%")
                           ->orWhere('member_code', 'like', "%{$request->member_name}%");
                    });
                }))
            ->when($request->filled('status') && $request->status !== 'all', function($q) use ($request) {
                $status = $request->status;
                if ($status === 'paid') {
                    $q->whereDoesntHave('payouts', fn($q2) => $q2->where('paid', false));
                } elseif (in_array($status, ['unpaid', 'pending'])) {
                    $q->whereHas('payouts', fn($q2) => $q2->where('paid', false));
                }
            })
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('distributed_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('distributed_at', '<=', $request->end_date));

        $summaryCount = (clone $baseQuery)->count();
        $summaryTotalAmount = (float) (clone $baseQuery)->sum('total_dividend_amount');
        $summaryInvestmentsInvolved = (clone $baseQuery)->whereNotNull('investment_id')->distinct('investment_id')->count('investment_id');

        $dividends = (clone $baseQuery)
            ->orderBy($sortBy, $sortDir)
            ->paginate($limit, ['*'], 'dividends_page')
            ->withQueryString();

        if ($request->wantsJson() && !$request->header('X-Requested-With')) {
            return DividendResource::collection($dividends);
        }

        $availableInvestments = Investment::orderBy('name')->get();
        $availableYears = Dividend::select('year')->distinct()->orderBy('year', 'desc')->pluck('year')->toArray();

        $search = $request->input('search') ?? $request->input('member_name');
        $selectedYear = $request->input('year');

        $memberQuery = User::where('role', 'member');

        if ($search) {
            $memberQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $applyDividendFilters = function ($q) use ($request) {
            $q->when($request->filled('year'), fn($sub) => $sub->where('year', $request->year))
              ->when($request->filled('investment_id'), fn($sub) => $sub->where('investment_id', $request->investment_id))
              ->when($request->filled('investment_name'), fn($sub) => 
                  $sub->whereHas('investment', fn($invQ) => $invQ->where('name', 'like', "%{$request->investment_name}%")))
              ->when($request->filled('start_date'), fn($sub) => $sub->whereDate('distributed_at', '>=', $request->start_date))
              ->when($request->filled('end_date'), fn($sub) => $sub->whereDate('distributed_at', '<=', $request->end_date));
        };

        $memberSummaries = $memberQuery
            ->withSum(['dividendPayouts as total_earned' => function($query) use ($applyDividendFilters) {
                $query->whereHas('dividend', $applyDividendFilters);
            }], 'amount')
            ->withSum(['dividendPayouts as total_paid' => function($query) use ($applyDividendFilters) {
                $query->where('paid', true);
                $query->whereHas('dividend', $applyDividendFilters);
            }], 'amount')
            ->withSum(['dividendPayouts as total_pending' => function($query) use ($applyDividendFilters) {
                $query->where('paid', false);
                $query->whereHas('dividend', $applyDividendFilters);
            }], 'amount')
            ->orderByRaw('CASE WHEN member_code IS NULL OR member_code = "" THEN 1 ELSE 0 END, member_code ASC, name ASC')
            ->paginate(15, ['*'], 'members_page')
            ->withQueryString();

        $payoutQuery = \App\Models\DividendPayout::whereHas('dividend', $applyDividendFilters);
        if ($request->filled('member_name')) {
            $payoutQuery->whereHas('user', function ($q2) use ($request) {
                $q2->where(function ($sub) use ($request) {
                    $sub->where('name', 'like', "%{$request->member_name}%")
                       ->orWhere('member_code', 'like', "%{$request->member_name}%");
                });
            });
        }

        $totalAllShared = (float) (clone $payoutQuery)->sum('amount');
        $totalAllPaid = (float) (clone $payoutQuery)->where('paid', true)->sum('amount');
        $totalAllPending = (float) (clone $payoutQuery)->where('paid', false)->sum('amount');

        return view('admin.dividends.index', compact(
            'dividends',
            'summaryCount',
            'summaryTotalAmount',
            'summaryInvestmentsInvolved',
            'availableInvestments',
            'availableYears',
            'selectedYear',
            'memberSummaries',
            'totalAllShared',
            'totalAllPaid',
            'totalAllPending',
            'search'
        ));
    }

    public function create()
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize('create', Dividend::class);
        }

        $investments = $this->dividendService->getAvailableInvestments();
        return view('admin.dividends.create', compact('investments'));
    }

    public function store(StoreDividendRequest $request)
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize('create', Dividend::class);
        }

        try {
            $dividend = $this->dividendService->distributeDividends(
                (int) $request->investment_id,
                (float) $request->total_dividend_amount
            );

            return redirect()->route('admin.dividends.show', $dividend)
                ->with('success', 'Dividends distributed successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function show(Dividend $dividend)
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize('view', $dividend);
        }

        $dividend->load(['payouts.user', 'distributor', 'investment']);
        return view('admin.dividends.show', compact('dividend'));
    }

    public function markAsPaid(Dividend $dividend)
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize('create', Dividend::class);
        }

        if (app(\App\Services\DividendReconciliationService::class)->isYearClosed($dividend->year)) {
            return back()->withErrors(["Financial year {$dividend->year} is closed. Cannot modify payouts for a closed financial year."]);
        }

        $dividend->payouts()->where('paid', false)->update([
            'paid' => true,
            'paid_date' => now(),
        ]);

        return back()->with('success', 'All dividends marked as paid.');
    }

    public function destroy(Dividend $dividend)
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize('delete', $dividend);
        }

        if (app(\App\Services\DividendReconciliationService::class)->isYearClosed($dividend->year)) {
            return back()->withErrors(["Financial year {$dividend->year} is closed. Cannot delete dividend records for a closed financial year."]);
        }

        $dividend->delete();

        return redirect()->route('admin.dividends.index')
            ->with('success', 'Dividend distribution deleted successfully.');
    }
}
