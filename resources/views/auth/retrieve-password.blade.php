@if(auth()->check())
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="m-0 fw-bold"><i class="bi bi-shield-lock"></i> Retrieve Password via Secret Questions</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">
                        Please verify your identity by entering your <strong>Email or Member Code</strong> along with your secret questions: <strong>Registration Year</strong> and <strong>Date of Birth</strong>.
                    </p>

                    @if ($errors->has('secret_verification'))
                        <div class="alert alert-danger py-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first('secret_verification') }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success py-2 small" role="alert">
                            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.recovery.verify') }}">
                        @csrf

                        <!-- Account Identifier -->
                        <div class="mb-3">
                            <label for="account_identifier" class="form-label text-muted small fw-bold text-uppercase">Email Address or Member Code</label>
                            <input type="text" 
                                   name="account_identifier" 
                                   id="account_identifier" 
                                   class="form-control @error('account_identifier') is-invalid @enderror" 
                                   value="{{ old('account_identifier', auth()->user()->email ?? auth()->user()->member_code ?? '') }}" 
                                   placeholder="e.g. user@example.com or MEM-2026-001" 
                                   required 
                                   autofocus>
                            @error('account_identifier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Secret Question 1: Registration Number -->
                        <div class="mb-3">
                            <label for="registration_number" class="form-label text-muted small fw-bold text-uppercase">
                                <i class="bi bi-card-text text-primary me-1"></i> Secret Question 1: Registration Number
                            </label>
                            <input type="text" 
                                   name="registration_number" 
                                   id="registration_number" 
                                   class="form-control @error('registration_number') is-invalid @enderror" 
                                   value="{{ old('registration_number', auth()->user()->member_code ?? '') }}" 
                                   placeholder="e.g. MEM-2026-001" 
                                   required>
                            @error('registration_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Secret Question 2: Date of Birth -->
                        <div class="mb-4">
                            <label for="date_of_birth" class="form-label text-muted small fw-bold text-uppercase">
                                <i class="bi bi-cake2 text-primary me-1"></i> Secret Question 2: Date of Birth
                            </label>
                            <input type="date" 
                                   name="date_of_birth" 
                                   id="date_of_birth" 
                                   class="form-control @error('date_of_birth') is-invalid @enderror" 
                                   value="{{ old('date_of_birth', auth()->user() && auth()->user()->date_of_birth ? auth()->user()->date_of_birth->format('Y-m-d') : '') }}" 
                                   required>
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                <i class="bi bi-shield-check me-1"></i> Verify Details & Retrieve Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@else
<x-guest-layout>
    <div class="mb-4 text-sm text-gray-400">
        <h3 style="color:#fff; font-size:1.2rem; font-weight:700; margin-bottom:0.5rem;"><i class="bi bi-shield-lock"></i> Retrieve Password</h3>
        <p style="font-size:0.85rem; color:#94a3b8;">
            Please verify your identity by entering your <strong>Email or Member Code</strong> along with your secret questions: <strong>Registration Number</strong> and <strong>Date of Birth</strong>.
        </p>
    </div>

    @if ($errors->has('secret_verification'))
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); padding: 0.75rem 1rem; border-radius: 12px; color: #f87171; font-size:0.85rem; margin-bottom: 1.25rem;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first('secret_verification') }}
        </div>
    @endif

    @if (session('success'))
        <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.25); padding: 0.75rem 1rem; border-radius: 12px; color: #4ade80; font-size:0.85rem; margin-bottom: 1.25rem;">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.recovery.verify') }}">
        @csrf

        <!-- Account Identifier -->
        <div class="mb-4">
            <x-input-label for="account_identifier" :value="__('Email Address or Member Code')" />
            <x-text-input id="account_identifier" class="block mt-1 w-full" type="text" name="account_identifier" :value="old('account_identifier')" placeholder="e.g. user@example.com or MEM-2026-001" required autofocus />
            <x-input-error :messages="$errors->get('account_identifier')" class="mt-2" />
        </div>

        <!-- Secret Question 1: Registration Number -->
        <div class="mb-4">
            <x-input-label for="registration_number" :value="__('Secret Question 1: Registration Number')" />
            <x-text-input id="registration_number" class="block mt-1 w-full" type="text" name="registration_number" :value="old('registration_number')" placeholder="e.g. MEM-2026-001" required />
            <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
        </div>

        <!-- Secret Question 2: Date of Birth -->
        <div class="mb-4">
            <x-input-label for="date_of_birth" :value="__('Secret Question 2: Date of Birth')" />
            <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth" :value="old('date_of_birth')" required />
            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-400 hover:text-gray-200 rounded-md" href="{{ route('login') }}">
                <i class="bi bi-arrow-left"></i> {{ __('Back to Login') }}
            </a>

            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-shield-check"></i> {{ __('Verify & Retrieve') }}
            </button>
        </div>
    </form>
</x-guest-layout>
@endif
