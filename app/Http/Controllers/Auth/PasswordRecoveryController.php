<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    /**
     * Display the Password Retrieval / Secret Questions form.
     */
    public function showForm(): View
    {
        return view('auth.retrieve-password');
    }

    /**
     * Verify secret questions (Registration Number).
     */
    public function verifySecretQuestions(Request $request): RedirectResponse
    {
        $request->validate([
            'account_identifier' => ['required', 'string'],
            'registration_number' => ['required', 'string'],
        ], [
            'account_identifier.required' => 'Please provide your Email Address or Member Code.',
            'registration_number.required' => 'Registration Number is required for verification.',
        ]);

        $identifier = trim($request->input('account_identifier'));

        $user = User::where(function ($query) use ($identifier) {
            $query->where('email', $identifier)
                  ->orWhere('member_code', $identifier);
        })->first();

        if (!$user) {
            return back()->withInput()->withErrors([
                'account_identifier' => 'No account found matching the provided Email or Member Code.',
            ]);
        }

        $inputRegNum = strtolower(trim($request->input('registration_number')));
        $userRegNum = strtolower(trim($user->member_code ?? ''));

        // Verify Secret Verification Question (Registration Number)
        // If user is admin/treasurer without member_code, allow email or user id as reg number fallback
        $regNumMatches = !empty($userRegNum) 
            ? ($userRegNum === $inputRegNum)
            : ($inputRegNum === strtolower(trim($user->email)) || $inputRegNum === (string) $user->id);

        if (!$regNumMatches) {
            return back()->withInput()->withErrors([
                'secret_verification' => 'The secret verification detail (Registration Number) provided does not match our records.',
            ]);
        }

        // Verification successful -> Store session verification token
        session([
            'recovery_user_id' => $user->id,
            'recovery_verified_at' => now()->timestamp,
        ]);

        return redirect()->route('password.recovery.reset')
            ->with('success', 'Secret verification successful! Please enter your new password.');
    }

    /**
     * Display the Set New Password form for verified users.
     */
    public function showResetForm()
    {
        if (!session()->has('recovery_user_id')) {
            return redirect()->route('password.recovery')
                ->withErrors(['secret_verification' => 'Please verify your secret details before setting a new password.']);
        }

        return view('auth.reset-retrieved-password');
    }

    /**
     * Update the verified user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        if (!session()->has('recovery_user_id')) {
            return redirect()->route('password.recovery')
                ->withErrors(['secret_verification' => 'Session expired. Please verify your secret details again.']);
        }

        $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $userId = session('recovery_user_id');
        $user = User::findOrFail($userId);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        session()->forget(['recovery_user_id', 'recovery_verified_at']);

        if (auth()->check()) {
            return redirect()->route('dashboard')
                ->with('success', 'Your password has been successfully retrieved and updated!');
        }

        return redirect()->route('login')
            ->with('success', 'Your password has been successfully retrieved and updated! Please log in with your new password.');
    }
}
