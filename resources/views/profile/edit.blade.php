@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-person-gear"></i> Profile Settings</h4>
@endsection

@section('content')
<div class="row g-4">
    <!-- Profile Info Card -->
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-person-card-text"></i> Profile Information</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">Update your account's profile information and email address.</p>
                
                @if (session('status') === 'profile-updated')
                    <div class="alert alert-success py-2 small" role="alert">
                        <i class="bi bi-check-circle-fill"></i> Profile updated successfully.
                    </div>
                @endif

                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')

                    <!-- Account Summary -->
                    <div class="p-3 mb-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Member Code / Username:</span>
                            @if($user->member_code)
                                <span class="badge bg-primary text-white font-monospace px-2 py-1">{{ $user->member_code }}</span>
                            @else
                                <span class="badge bg-secondary text-white small">No code assigned</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Cooperative:</span>
                            <span class="fw-semibold text-dark small">{{ $user->cooperative?->name ?? 'Platform Wide' }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label text-muted small fw-bold text-uppercase">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small fw-bold text-uppercase">Email Address (Login Email) <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="email">
                        <small class="text-muted">This email address is used to sign into your account.</small>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label text-muted small fw-bold text-uppercase">Phone Number</label>
                        <input type="tel" name="phone" id="phone" inputmode="tel" pattern="[\+]?[0-9\s\-\(\)]{7,20}" maxlength="20" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 08012345678 or +234 800 000 0000">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label text-muted small fw-bold text-uppercase">Contact Address</label>
                        <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Enter residential / contact address">{{ old('address', $user->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Card -->
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-shield-lock"></i> Update Password</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">Ensure your account is using a long, random password to stay secure.</p>

                @if (session('status') === 'password-updated')
                    <div class="alert alert-success py-2 small" role="alert">
                        <i class="bi bi-check-circle-fill"></i> Password updated successfully.
                    </div>
                @endif

                <form method="post" action="{{ route('password.update') }}" autocomplete="off">
                    @csrf
                    @method('put')

                    {{-- Username field for browser password managers to identify the account and prevent "Select which login to update" dialog --}}
                    <input type="text" name="username" value="{{ auth()->user()->email }}" autocomplete="username" readonly style="display: none;" aria-hidden="true" tabindex="-1">

                    <div class="mb-3">
                        <label for="current_password" class="form-label text-muted small fw-bold text-uppercase">Current Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="current_password" 
                                   id="current_password" 
                                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                                   required 
                                   autocomplete="off" 
                                   readonly 
                                   onfocus="this.removeAttribute('readonly');" 
                                   onclick="this.removeAttribute('readonly');" 
                                   placeholder="Enter your current password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label text-muted small fw-bold text-uppercase">New Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label text-muted small fw-bold text-uppercase">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_confirmation" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password_confirmation', 'updatePassword')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Ensure current password field starts completely blank and is never pre-filled by browser
        const currentPasswordInput = document.getElementById('current_password');
        if (currentPasswordInput) {
            currentPasswordInput.value = '';
            setTimeout(() => { if (document.activeElement !== currentPasswordInput) currentPasswordInput.value = ''; }, 100);
            setTimeout(() => { if (document.activeElement !== currentPasswordInput) currentPasswordInput.value = ''; }, 300);
        }

        window.addEventListener('pageshow', function () {
            if (currentPasswordInput && document.activeElement !== currentPasswordInput) {
                currentPasswordInput.value = '';
            }
        });

        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.hasAttribute('readonly')) {
                    input.removeAttribute('readonly');
                }

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
