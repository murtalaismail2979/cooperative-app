<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RunningChargeService;
use Illuminate\Http\Request;

class RunningChargeController extends Controller
{
    protected $runningChargeService;

    public function __construct(RunningChargeService $runningChargeService)
    {
        $this->runningChargeService = $runningChargeService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $memberQuery = User::where('role', 'member')->where('is_active', true);
        if ($cooperativeId) {
            $memberQuery->where('cooperative_id', $cooperativeId);
        }
        $members = $memberQuery->get();

        $chargeQuery = \App\Models\RunningCharge::with('user');
        if ($cooperativeId) {
            $chargeQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }
        $charges = $chargeQuery->latest()->paginate(15);

        // Calculate monthly summary of paid running charges (recent 6 months)
        $summaryQuery = \App\Models\RunningCharge::where('status', 'paid');
        if ($cooperativeId) {
            $summaryQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        if (config('database.default') === 'sqlite') {
            $monthlyCharges = (clone $summaryQuery)->selectRaw('strftime("%Y", month) as year, strftime("%m", month) as month_num, SUM(amount) as total')
                ->groupBy('year', 'month_num')
                ->orderBy('year', 'desc')
                ->orderBy('month_num', 'desc')
                ->take(6)
                ->get();
        } else {
            $monthlyCharges = (clone $summaryQuery)->selectRaw('YEAR(month) as year, MONTH(month) as month_num, SUM(amount) as total')
                ->groupBy('year', 'month_num')
                ->orderBy('year', 'desc')
                ->orderBy('month_num', 'desc')
                ->take(6)
                ->get();
        }

        $overallTotal = (float) (clone $summaryQuery)->sum('amount');
        $chargeRates = \App\Models\RunningChargeRate::orderBy('start_year')->get();

        return view('admin.running-charges.index', compact('members', 'charges', 'monthlyCharges', 'overallTotal', 'chargeRates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        $member = User::findOrFail($validated['user_id']);
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $member->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized access to member of another cooperative.');
        }

        $this->runningChargeService->recordCharge(
            $validated['user_id'],
            $validated['month'],
            $validated['amount'] ?? null,
            $validated['payment_date']
        );

        return redirect()->route('admin.running-charges.index')->with('success', 'Running charge recorded.');
    }

    public function updateInterval(Request $request)
    {
        $validated = $request->validate([
            'start_year' => 'required|integer|min:2000|max:2100',
            'end_year' => 'required|integer|min:2000|max:2100|gte:start_year',
            'amount' => 'required|numeric|min:0',
        ]);

        $count = $this->runningChargeService->updateAmountForYearInterval(
            (int)$validated['start_year'],
            (int)$validated['end_year'],
            (float)$validated['amount']
        );

        return redirect()->route('admin.running-charges.index')
            ->with('success', "Updated charge amount to ₦" . number_format($validated['amount'], 2) . " for year interval {$validated['start_year']} - {$validated['end_year']} ({$count} record(s) updated).");
    }

    public function history(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $member = $request->input('member');

        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $query = \App\Models\RunningCharge::with('user');
        if ($cooperativeId) {
            $query->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        if ($month) {
            $query->whereMonth('month', $month);
        }

        if ($year) {
            $query->whereYear('month', $year);
        }

        if ($member) {
            $query->whereHas('user', function ($q) use ($member) {
                $q->where('name', 'like', "%{$member}%")
                  ->orWhere('member_code', 'like', "%{$member}%");
            });
        }

        $totalCharges = (float) (clone $query)->sum('amount');

        $charges = (clone $query)->latest('month')->paginate(20)->withQueryString();

        $years = \App\Models\RunningCharge::pluck('month')
            ->map(function ($date) {
                return $date instanceof \Carbon\Carbon ? $date->year : \Carbon\Carbon::parse($date)->year;
            })
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();
            
        if (empty($years)) {
            $years = [date('Y')];
        }

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Calculate monthly and yearly summary of paid running charges with filters
        $summaryQuery = \App\Models\RunningCharge::where('status', 'paid');
        if ($cooperativeId) {
            $summaryQuery->whereHas('user', fn($q) => $q->where('cooperative_id', $cooperativeId));
        }

        if ($month) {
            $summaryQuery->whereMonth('month', $month);
        }

        if ($year) {
            $summaryQuery->whereYear('month', $year);
        }

        if ($member) {
            $summaryQuery->whereHas('user', function ($q) use ($member) {
                $q->where('name', 'like', "%{$member}%")
                  ->orWhere('member_code', 'like', "%{$member}%");
            });
        }

        if (config('database.default') === 'sqlite') {
            $monthlyCharges = $summaryQuery->selectRaw('strftime("%Y", month) as year, strftime("%m", month) as month_num, SUM(amount) as total')
                ->groupBy('year', 'month_num')
                ->orderBy('year', 'desc')
                ->orderBy('month_num', 'desc')
                ->get();
        } else {
            $monthlyCharges = $summaryQuery->selectRaw('YEAR(month) as year, MONTH(month) as month_num, SUM(amount) as total')
                ->groupBy('year', 'month_num')
                ->orderBy('year', 'desc')
                ->orderBy('month_num', 'desc')
                ->get();
        }

        return view('admin.running-charges.history', compact(
            'charges', 'totalCharges', 'month', 'year', 'member', 'years', 'months', 'monthlyCharges'
        ));
    }

    public function edit(\App\Models\RunningCharge $runningCharge)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $runningCharge->user && $runningCharge->user->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized access to running charge of another cooperative.');
        }

        return view('admin.running-charges.edit', compact('runningCharge'));
    }

    public function update(Request $request, \App\Models\RunningCharge $runningCharge)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $runningCharge->user && $runningCharge->user->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized access to running charge of another cooperative.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'month' => 'required|date',
            'payment_date' => 'required|date',
        ]);

        $runningCharge->update($validated);

        return redirect()->route('admin.running-charges.history')->with('success', 'Running charge updated successfully.');
    }

    public function destroy(\App\Models\RunningCharge $runningCharge)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $runningCharge->user && $runningCharge->user->cooperative_id !== $user->cooperative_id) {
            abort(403, 'Unauthorized access to running charge of another cooperative.');
        }

        $runningCharge->delete();
        return redirect()->back(fallback: route('admin.running-charges.history'))->with('success', 'Running charge deleted successfully.');
    }
}

