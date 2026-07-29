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

                    <div class="mb-3">
                        <label for="name" class="form-label text-muted small fw-bold text-uppercase">Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label text-muted small fw-bold text-uppercase">Date of Birth (Secret Question 2)</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}">
                        @error('date_of_birth')
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

                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="mb-3">
                        <label for="current_password" class="form-label text-muted small fw-bold text-uppercase">Current Password</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" required autocomplete="current-password">
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

<div class="row mt-4">
    <!-- Delete Account Card -->
    <div class="col-12">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger bg-opacity-10 py-3 text-danger border-danger">
                <h6 class="m-0 fw-bold"><i class="bi bi-exclamation-triangle"></i> Danger Zone: Delete Account</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Once your account is deleted, all of its resources and data will be permanently deleted. Please proceed with extreme caution.</p>
                
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
                    <i class="bi bi-trash"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-octagon"></i> Confirm Account Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold">Are you sure you want to delete your account?</p>
                    <p class="text-muted small mb-3">Once your account is deleted, all data will be permanently lost. Please enter your password to confirm deletion.</p>
                    
                    <div class="mb-3">
                        <label for="delete_password" class="form-label text-muted small fw-bold">Password</label>
                        <input type="password" name="password" id="delete_password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Enter your password" required>
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Permanently Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->userDeletion->isNotEmpty())
            const modal = new bootstrap.Modal(document.getElementById('confirmUserDeletionModal'));
            modal.show();
        @endif

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
