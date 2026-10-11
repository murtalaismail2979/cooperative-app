<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CooperativeController extends Controller
{
    public function index()
    {
        $cooperatives = Cooperative::withCount('users')->get();
        return view('admin.cooperatives.index', compact('cooperatives'));
    }

    public function create()
    {
        return view('admin.cooperatives.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:cooperatives,slug'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'base_slot_amount' => ['required', 'numeric', 'min:0'],
            'enable_savings' => ['boolean'],
            'enable_shares' => ['boolean'],
            'share_unit_price' => ['nullable', 'numeric', 'min:0'],
            'min_share_units' => ['nullable', 'integer', 'min:0'],
            'dividend_allocation_basis' => ['required', 'in:savings_only,shares_only,hybrid'],
            'hybrid_shares_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);

        Cooperative::create([
            'name' => $request->name,
            'slug' => strtoupper($slug),
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'primary_color' => $request->primary_color ?? '#1e40af',
            'secondary_color' => $request->secondary_color ?? '#0f172a',
            'is_active' => true,
            'enable_savings' => $request->has('enable_savings'),
            'enable_shares' => $request->has('enable_shares'),
            'base_slot_amount' => $request->base_slot_amount ?? 2000.00,
            'share_unit_price' => $request->share_unit_price ?? 1000.00,
            'min_share_units' => $request->min_share_units ?? 10,
            'dividend_allocation_basis' => $request->dividend_allocation_basis ?? 'savings_only',
            'hybrid_shares_percentage' => $request->hybrid_shares_percentage ?? 50.00,
        ]);

        return redirect()->route('admin.cooperatives.index')->with('success', 'Cooperative created successfully.');
    }

    public function edit(Cooperative $cooperative)
    {
        return view('admin.cooperatives.edit', compact('cooperative'));
    }

    public function update(Request $request, Cooperative $cooperative)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:50', 'unique:cooperatives,slug,' . $cooperative->id],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'base_slot_amount' => ['required', 'numeric', 'min:0'],
            'share_unit_price' => ['nullable', 'numeric', 'min:0'],
            'min_share_units' => ['nullable', 'integer', 'min:0'],
            'dividend_allocation_basis' => ['required', 'in:savings_only,shares_only,hybrid'],
            'hybrid_shares_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $cooperative->update([
            'name' => $request->name,
            'slug' => strtoupper(Str::slug($request->slug)),
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'primary_color' => $request->primary_color ?? '#1e40af',
            'secondary_color' => $request->secondary_color ?? '#0f172a',
            'is_active' => $request->has('is_active'),
            'enable_savings' => $request->has('enable_savings'),
            'enable_shares' => $request->has('enable_shares'),
            'base_slot_amount' => $request->base_slot_amount,
            'share_unit_price' => $request->share_unit_price ?? 1000.00,
            'min_share_units' => $request->min_share_units ?? 10,
            'dividend_allocation_basis' => $request->dividend_allocation_basis,
            'hybrid_shares_percentage' => $request->hybrid_shares_percentage ?? 50.00,
        ]);

        return redirect()->route('admin.cooperatives.index')->with('success', 'Cooperative updated successfully.');
    }

    public function toggleStatus(Cooperative $cooperative)
    {
        $cooperative->is_active = !$cooperative->is_active;
        $cooperative->save();

        $statusStr = $cooperative->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Cooperative '{$cooperative->name}' has been {$statusStr}.");
    }
}
