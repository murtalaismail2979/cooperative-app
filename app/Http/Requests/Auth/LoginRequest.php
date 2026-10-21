<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            if ($user->isPendingMembership()) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => 'Your membership application is currently pending approval by the cooperative admin. You will be able to log in once your application has been accepted.',
                ]);
            }
            if ($user->isRejectedMembership()) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => 'Your membership application was not approved by the cooperative administration. Please contact support.',
                ]);
            }
            if (!$user->is_active) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => 'Your account has been deactivated by cooperative administration. Please contact support.',
                ]);
            }
            if ($user->cooperative && !$user->cooperative->is_active) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => 'Your cooperative is currently inactive or suspended. Please contact the platform administrator.',
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
