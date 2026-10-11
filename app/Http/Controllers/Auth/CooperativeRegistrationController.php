<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class CooperativeRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register-cooperative');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'cooperative_name' => ['required', 'string', 'max:255'],
            'cooperative_slug' => ['nullable', 'string', 'max:50', 'unique:cooperatives,slug'],
            'cooperative_email' => ['nullable', 'email', 'max:255'],
            'cooperative_phone' => ['nullable', 'string', 'max:50'],
            'cooperative_address' => ['nullable', 'string', 'max:500'],
            'base_slot_amount' => ['required', 'numeric', 'min:0'],
            'dividend_allocation_basis' => ['required', 'in:savings_only,shares_only,hybrid'],
            
            // Admin user details
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $slugInput = $request->cooperative_slug ? $request->cooperative_slug : $request->cooperative_name;
        $slug = strtoupper(Str::slug($slugInput));

        $adminUser = DB::transaction(function () use ($request, $slug) {
            $cooperative = Cooperative::create([
                'name' => $request->cooperative_name,
                'slug' => $slug,
                'email' => $request->cooperative_email,
                'phone' => $request->cooperative_phone,
                'address' => $request->cooperative_address,
                'is_active' => true,
                'enable_savings' => true,
                'base_slot_amount' => $request->base_slot_amount ?? 2000.00,
                'dividend_allocation_basis' => $request->dividend_allocation_basis ?? 'savings_only',
            ]);

            return User::create([
                'cooperative_id' => $cooperative->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'admin',
                'is_active' => true,
            ]);
        });

        Auth::login($adminUser);

        return redirect()->route('admin.dashboard')->with('success', 'Congratulations! Your cooperative has been registered successfully.');
    }
}
