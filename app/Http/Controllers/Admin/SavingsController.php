<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsRequest;
use App\Models\User;
use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\Slot;
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
        $selectedYear = (int) ($request->input('year') ?? date('Y'));

        $driver = \DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' ? "CAST(strftime('%m', month) AS INTEGER)" : "MONTH(month)";
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";

        $savingsData = MonthlySaving::selectRaw("user_id, {$monthExpr} as month_num, SUM(amount) as total_amount")
            ->whereRaw("{$yearExpr} = ?", [(string) $selectedYear])
            ->groupBy('user_id', \DB::raw($monthExpr))
            ->get()
            ->groupBy('user_id');

        $availableYears = MonthlySaving::selectRaw("DISTINCT {$yearExpr} as yr")
            ->whereNotNull('month')
            ->orderBy('yr', 'desc')
            ->pluck('yr')
            ->map(fn($y) => (int) $y)
            ->filter()
            ->values()
            ->toArray();

        $currentYearNum = (int) date('Y');
        if (!in_array($currentYearNum, $availableYears)) {
            array_unshift($availableYears, $currentYearNum);
            $availableYears = array_unique($availableYears);
            rsort($availableYears);
        }

        return view('admin.savings.index', compact('members', 'selectedYear', 'savingsData', 'availableYears'));
    }

    public function create(Request $request)
    {
        $members = $this->savingsService->getActiveMembersWithSlots();
        $selectedMemberId = $request->query('member_id');
        return view('admin.savings.create', compact('members', 'selectedMemberId'));
    }

    public function store(StoreSavingsRequest $request)
    {
        $member = User::findOrFail($request->member_id);
        if (!$member->is_active) {
            return redirect()->back()->withErrors(['member_id' => 'Cannot record savings for an inactive member.'])->withInput();
        }

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
            ->selectRaw('user_id, month, CASE WHEN SUM(amount) > 0 THEN CAST(ROUND(SUM(amount) / 2000) AS INTEGER) ELSE COUNT(id) END as slots_count, SUM(amount) as total_amount, MAX(payment_date) as latest_payment_date')
            ->with('user')
            ->groupBy('user_id', 'month')
            ->latest('month')
            ->paginate(20)
            ->withQueryString();

        $driver = \DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', month)" : "YEAR(month)";
        $years = MonthlySaving::selectRaw("DISTINCT {$yearExpr} as yr")
            ->whereNotNull('month')
            ->orderBy('yr', 'desc')
            ->pluck('yr')
            ->map(fn($y) => (int) $y)
            ->filter()
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

        $slotAmounts = MonthlySaving::where('user_id', $member->id)
            ->where('month', $month)
            ->pluck('amount', 'savings_slot_id')
            ->toArray();

        $displaySlots = $this->savingsService->getRegisteredSlotsForMonth($member, $month);

        return view('admin.savings.edit', compact('member', 'month', 'paidSlotIds', 'slotAmounts', 'displaySlots'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:users,id',
            'old_month' => 'required|date',
            'month' => 'required|date',
            'slots' => 'required|array',
            'slots.*' => 'exists:savings_slots,id',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        $member = User::findOrFail($request->member_id);
        $amounts = $request->input('amounts', []);

        // Strong validation: amounts must match expected slot amount corresponding to slot_number (or 0 for zero savings)
        $slotsToValidate = SavingsSlot::whereIn('id', $request->slots)->get();
        $validationErrors = [];

        foreach ($slotsToValidate as $slot) {
            $configuredAmount = \App\Models\Slot::where('slot_number', $slot->slot_number)->value('amount');
            $expectedAmount = $configuredAmount !== null ? (float) $configuredAmount : (float) ($slot->slot_number * 2000);

            if (isset($amounts[$slot->id])) {
                $submittedAmount = (float) $amounts[$slot->id];
                if (abs($submittedAmount - $expectedAmount) > 0.001 && abs($submittedAmount - 0.0) > 0.001) {
                    $validationErrors["amounts.{$slot->id}"] = "The amount for Slot #{$slot->slot_number} must be ₦" . number_format($expectedAmount, 2) . " (or ₦0.00 for zero savings).";
                }
            }
        }

        if (!empty($validationErrors)) {
            return redirect()->back()->withErrors($validationErrors)->withInput();
        }

        $hasZeroAmountEdit = collect($amounts)->contains(function ($amount) {
            return is_numeric($amount) && (float) $amount === 0.0;
        });

        $oldMonthDate = \Carbon\Carbon::parse($request->old_month)->startOfMonth()->format('Y-m-d');
        $hasExistingZeroRecord = MonthlySaving::where('user_id', $request->member_id)
            ->whereDate('month', $oldMonthDate)
            ->where('amount', 0)
            ->exists();

        if (($hasZeroAmountEdit || $hasExistingZeroRecord) && !auth()->user()?->isAdmin()) {
            abort(403, 'Only admins can edit zero-amount savings entries.');
        }

        $this->savingsService->updateSavings(
            $member,
            $request->old_month,
            $request->month,
            $request->slots,
            $request->payment_date,
            $amounts
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

