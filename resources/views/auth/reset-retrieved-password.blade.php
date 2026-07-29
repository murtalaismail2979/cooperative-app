@if(auth()->check())
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="m-0 fw-bold"><i class="bi bi-key-fill"></i> Set New Password</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">
                        Verification successful! Enter your new password below.
                    </p>

                    @if (session('success'))
                        <div class="alert alert-success py-2 small mb-3" role="alert">
                            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.recovery.update') }}">
                        @csrf

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label text-muted small fw-bold text-uppercase">New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       required 
                                       autofocus 
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label text-muted small fw-bold text-uppercase">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="password_confirmation" 
                                       id="password_confirmation" 
                                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       required 
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_confirmation" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('password_confirmation')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success py-2 fw-bold">
                                <i class="bi bi-check2-circle me-1"></i> Save New Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    });
</script>
@endpush
@endsection
@else
<x-guest-layout>
    <div class="mb-4 text-sm text-gray-400">
        <h3 style="color:#fff; font-size:1.2rem; font-weight:700; margin-bottom:0.5rem;"><i class="bi bi-key-fill"></i> Set New Password</h3>
        <p style="font-size:0.85rem; color:#94a3b8;">
            Verification successful! Please enter your new password below.
        </p>
    </div>

    @if (session('success'))
        <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.25); padding: 0.75rem 1rem; border-radius: 12px; color: #4ade80; font-size:0.85rem; margin-bottom: 1.25rem;">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.recovery.update') }}">
        @csrf

        <!-- New Password -->
        <div class="mb-4">
            <x-input-label for="password" :value="__('New Password')" />
            <div style="position: relative;">
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <div style="position: relative;">
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-check2-circle"></i> {{ __('Save New Password') }}
            </button>
        </div>
    </form>
</x-guest-layout>
@endif
