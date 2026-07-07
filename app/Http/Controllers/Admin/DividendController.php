<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDividendRequest;
use App\Models\Dividend;
use App\Services\DividendService;

class DividendController extends Controller
{
    protected $dividendService;

    public function __construct(DividendService $dividendService)
    {
        $this->dividendService = $dividendService;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $dividends = Dividend::with(['distributor', 'investment'])->latest()->paginate(10, ['*'], 'dividends_page');
        
        $search = $request->input('search');
        
        $memberQuery = \App\Models\User::where('role', 'member');
        
        if ($search) {
            $memberQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $memberSummaries = $memberQuery
            ->withSum('dividendPayouts as total_earned', 'amount')
            ->withSum(['dividendPayouts as total_paid' => function($query) {
                $query->where('paid', true);
            }], 'amount')
            ->withSum(['dividendPayouts as total_pending' => function($query) {
                $query->where('paid', false);
            }], 'amount')
            ->orderBy('name')
            ->paginate(15, ['*'], 'members_page')
            ->withQueryString();

        $totalAllShared = \App\Models\DividendPayout::sum('amount');
        $totalAllPaid = \App\Models\DividendPayout::where('paid', true)->sum('amount');
        $totalAllPending = \App\Models\DividendPayout::where('paid', false)->sum('amount');

        return view('admin.dividends.index', compact('dividends', 'memberSummaries', 'totalAllShared', 'totalAllPaid', 'totalAllPending', 'search'));
    }

    public function create()
    {
        $investments = $this->dividendService->getAvailableInvestments();
        return view('admin.dividends.create', compact('investments'));
    }

    public function store(StoreDividendRequest $request)
    {
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
        $dividend->load(['payouts.user', 'distributor', 'investment']);
        return view('admin.dividends.show', compact('dividend'));
    }

    public function markAsPaid(Dividend $dividend)
    {
        $dividend->payouts()->where('paid', false)->update([
            'paid' => true,
            'paid_date' => now(),
        ]);

        return back()->with('success', 'All dividends marked as paid.');
    }

    public function destroy(Dividend $dividend)
    {
        $dividend->delete();

        return redirect()->route('admin.dividends.index')
            ->with('success', 'Dividend distribution deleted successfully.');
    }
}
