<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsRequest;
use App\Models\User;
use App\Models\MonthlySaving;
use App\Services\SavingsService;
use Illuminate\Http\Request;

class SavingsController extends Controller
{
    protected $savingsService;

    public function __construct(SavingsService $savingsService)
    {
        $this->savingsService = $savingsService;
    }

    public function index(Request $request)
    {
        $members = $this->savingsService->getMembersWithSlots();
        $monthInput = $request->input('month');
        $currentMonth = $monthInput 
            ? \Carbon\Carbon::parse($monthInput)->startOfMonth() 
            : now()->startOfMonth();

        return view('admin.savings.index', compact('members', 'currentMonth'));
    }

    public function create()
    {
        $members = $this->savingsService->getActiveMembersWithSlots();
        return view('admin.savings.create', compact('members'));
    }

    public function store(StoreSavingsRequest $request)
    {
        $member = User::findOrFail($request->member_id);
        $this->savingsService->recordSavings($member, $request->month, $request->slots, $request->payment_date);

        return redirect()->route('admin.running-charges.index')->with('success', 'Savings recorded successfully.');
    }

    public function history(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        $baseQuery = MonthlySaving::query();

        if ($search) {
            $baseQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        if ($month) {
            $baseQuery->whereMonth('month', $month);
        }

        if ($year) {
            $baseQuery->whereYear('month', $year);
        }

        $totalSavings = (float) (clone $baseQuery)->sum('amount');

        $savings = (clone $baseQuery)
            ->selectRaw('user_id, month, COUNT(id) as slots_count, SUM(amount) as total_amount, MAX(payment_date) as latest_payment_date')
            ->with('user')
            ->groupBy('user_id', 'month')
            ->latest('month')
            ->paginate(20)
            ->withQueryString();

        $years = MonthlySaving::pluck('month')
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

        return view('admin.savings.history', compact('savings', 'totalSavings', 'search', 'month', 'year', 'years', 'months'));
    }

    public function edit(Request $request)
    {
        $member = User::findOrFail($request->user_id);
        $month = $request->month;

        $paidSlotIds = MonthlySaving::where('user_id', $member->id)
            ->where('month', $month)
            ->pluck('savings_slot_id')
            ->toArray();

        $member->load('savingsSlots');

        return view('admin.savings.edit', compact('member', 'month', 'paidSlotIds'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:users,id',
            'old_month' => 'required|date',
            'month' => 'required|date',
            'slots' => 'required|array',
            'slots.*' => 'exists:savings_slots,id',
            'payment_date' => 'required|date',
        ]);

        $member = User::findOrFail($request->member_id);

        $this->savingsService->updateSavings(
            $member,
            $request->old_month,
            $request->month,
            $request->slots,
            $request->payment_date
        );

        return redirect()->route('admin.savings.history')->with('success', 'Savings record updated successfully.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|date',
        ]);

        $member = User::findOrFail($request->user_id);
        $this->savingsService->deleteSavings($member, $request->month);

        return redirect()->route('admin.savings.history')->with('success', 'Savings record deleted successfully.');
    }
}

