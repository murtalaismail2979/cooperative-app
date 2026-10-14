<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use Illuminate\Http\Request;

class PlatformChargeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
                abort(403, 'Unauthorized access. Only super administrators can manage platform charges.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $cooperatives = Cooperative::withCount([
            'users as members_count' => function ($query) {
                $query->where('role', 'member');
            },
            'users as total_users_count'
        ])->orderBy('name')->get();

        $totalCooperatives = $cooperatives->count();
        $activeCooperatives = $cooperatives->where('is_active', true)->count();
        $totalBillableMembers = $cooperatives->sum('members_count');

        $totalMonthlyRevenue = $cooperatives->sum(function ($coop) {
            return (float) ($coop->members_count * ($coop->monthly_charge_per_member ?? 0));
        });

        $totalAnnualRevenue = $totalMonthlyRevenue * 12;

        return view('admin.platform-charges.index', compact(
            'cooperatives',
            'totalCooperatives',
            'activeCooperatives',
            'totalBillableMembers',
            'totalMonthlyRevenue',
            'totalAnnualRevenue'
        ));
    }

    public function agreement(Cooperative $cooperative)
    {
        $membersCount = $cooperative->members()->count();
        $rate = (float) ($cooperative->monthly_charge_per_member ?? 0);
        $monthlyTotal = $membersCount * $rate;
        $annualTotal = $monthlyTotal * 12;

        return view('admin.platform-charges.agreement', compact(
            'cooperative',
            'membersCount',
            'rate',
            'monthlyTotal',
            'annualTotal'
        ));
    }

    public function update(Request $request, Cooperative $cooperative)
    {
        $validated = $request->validate([
            'monthly_charge_per_member' => 'required|numeric|min:0',
            'billing_notes' => 'nullable|string|max:1000',
        ]);

        $cooperative->update([
            'monthly_charge_per_member' => $validated['monthly_charge_per_member'],
            'billing_notes' => $validated['billing_notes'] ?? null,
        ]);

        $monthlyTotal = $cooperative->members()->count() * (float) $cooperative->monthly_charge_per_member;

        return redirect()->route('admin.platform-charges.index')
            ->with('success', "Charge for '{$cooperative->name}' updated to ₦" . number_format($cooperative->monthly_charge_per_member, 2) . "/member/mo (Total Monthly: ₦" . number_format($monthlyTotal, 2) . ").");
    }

    public function updateBatch(Request $request)
    {
        $validated = $request->validate([
            'default_rate' => 'required|numeric|min:0',
            'cooperative_ids' => 'nullable|array',
            'cooperative_ids.*' => 'exists:cooperatives,id',
        ]);

        $query = Cooperative::query();
        if (!empty($validated['cooperative_ids'])) {
            $query->whereIn('id', $validated['cooperative_ids']);
        }

        $count = $query->update([
            'monthly_charge_per_member' => $validated['default_rate'],
        ]);

        return redirect()->route('admin.platform-charges.index')
            ->with('success', "Updated monthly charge to ₦" . number_format($validated['default_rate'], 2) . " per member for {$count} cooperative(s).");
    }
}
