<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'The new password must be different from your current password.',
            'current_password.current_password' => 'The provided current password is incorrect.',
        ]);

        $user = $request->user();
        $email = $user->email;

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Security best practice: invalidate current session and force re-authentication
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withInput(['email' => $email])
            ->with('status', 'Your password has been changed successfully. Please log in with your new password.');
    }
}
