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
            'cooperative_id' => ['required', 'exists:cooperatives,id'],
            'slots' => ['nullable', 'integer', 'min:1', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['required', 'string', 'max:20', new \App\Rules\PhoneNumber],
            'address' => ['nullable', 'string', 'max:500'],
            'nok_name' => ['required', 'string', 'max:255'],
            'nok_phone' => ['required', 'string', 'max:20', new \App\Rules\PhoneNumber],
            'nok_relationship' => ['required', 'string', 'max:255'],
            'nok_address' => ['nullable', 'string', 'max:500'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'You must agree to the cooperative constitution and membership terms to register.',
            'cooperative_id.required' => 'Please select the cooperative you wish to join.',
            'slots.min' => 'You must select at least 1 monthly savings slot.',
            'slots.max' => 'You can select a maximum of 10 monthly savings slots.',
            'phone.required' => 'Your phone number is required for account communication and SMS alerts.',
            'nok_name.required' => 'Next of kin full name is required.',
            'nok_phone.required' => 'Next of kin phone number is required.',
            'nok_relationship.required' => 'Please specify your relationship with your next of kin.',
        ]);

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $slotsCount = (int) ($request->input('slots') ?: 1);

            $createdUser = User::create([
                'cooperative_id' => $request->cooperative_id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'member_code' => null, // Member ID assigned only upon management approval
                'registration_year' => (int) date('Y'),
                'registration_month' => (int) date('n'),
                'role' => 'member',
                'is_active' => false,
                'membership_status' => 'pending',
            ]);

            // Create requested savings slots for self-registered member (inactive until admin approves)
            for ($i = 1; $i <= $slotsCount; $i++) {
                $createdUser->savingsSlots()->create([
                    'slot_number' => $i,
                    'is_active' => false,
                ]);
            }

            // Record initial slot creation history
            $createdUser->slotHistories()->create([
                'previous_slots' => 0,
                'current_slots' => $slotsCount,
                'changed_by' => null,
                'reason' => "Initial self-registration commitment: {$slotsCount} slot(s) (pending approval)",
            ]);

            // Create Next of Kin
            $createdUser->nextOfKin()->create([
                'name' => $request->nok_name,
                'phone' => $request->nok_phone,
                'relationship' => $request->nok_relationship,
                'address' => $request->nok_address,
            ]);

            // Create Registration Fee Obligation
            app(\App\Services\RegistrationFeeService::class)->createObligationForMember($createdUser);

            return $createdUser;
        });

        event(new Registered($user));

        // Prospective members must wait for administrative approval before accessing their account
        return redirect()->route('login')->with('status', 'Your membership application has been submitted successfully! Your account is currently awaiting administrative review and approval before login access is activated.');
    }
}
