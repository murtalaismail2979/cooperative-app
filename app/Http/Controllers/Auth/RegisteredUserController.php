<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'nok_name' => ['required', 'string', 'max:255'],
            'nok_phone' => ['required', 'string', 'max:20'],
            'nok_relationship' => ['required', 'string', 'max:255'],
            'nok_email' => ['nullable', 'email', 'max:255'],
            'nok_address' => ['nullable', 'string', 'max:500'],
        ]);

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $createdUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'member_code' => User::generateMemberCode(),
                'registration_year' => date('Y'),
                'role' => 'member',
                'is_active' => true,
            ]);

            // Create 1 default savings slot for self-registered member
            $createdUser->savingsSlots()->create([
                'slot_number' => 1,
                'is_active' => true,
            ]);

            // Record initial slot creation history
            $createdUser->slotHistories()->create([
                'previous_slots' => 0,
                'current_slots' => 1,
                'changed_by' => null,
                'reason' => 'Initial registration',
            ]);

            // Create Next of Kin
            $createdUser->nextOfKin()->create([
                'name' => $request->nok_name,
                'phone' => $request->nok_phone,
                'relationship' => $request->nok_relationship,
                'email' => $request->nok_email,
                'address' => $request->nok_address,
            ]);

            return $createdUser;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
