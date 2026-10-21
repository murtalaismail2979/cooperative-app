@extends('layouts.app')

@section('title', 'User Login Details')

@section('page-title')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h2 class="h4 mb-0 fw-bold text-dark">
            <i class="bi bi-shield-lock-fill text-primary me-2"></i> User Login Details & Credentials
        </h2>
        <span class="badge bg-danger text-white px-3 py-2 fs-6 shadow-sm">
            <i class="bi bi-lock-fill me-1"></i> Super Admin Only
        </span>
    </div>
@endsection

@section('content')
<div class="container-fluid px-0">

    <!-- Top Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-primary text-white shadow">
                <div class="card-body">
                    <i class="bi bi-people icon"></i>
                    <div class="stat-label">Total Accounts</div>
                    <div class="stat-value">{{ number_format($stats['total_users']) }}</div>
                    <small class="opacity-75">All users across system</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-info text-white shadow">
                <div class="card-body">
                    <i class="bi bi-person-badge icon"></i>
                    <div class="stat-label">Cooperative Members</div>
                    <div class="stat-value">{{ number_format($stats['total_members']) }}</div>
                    <small class="opacity-75">Registered members</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-success text-white shadow">
                <div class="card-body">
                    <i class="bi bi-shield-shaded icon"></i>
                    <div class="stat-label">Admins & Executives</div>
                    <div class="stat-value">{{ number_format($stats['total_admins']) }}</div>
                    <small class="opacity-75">Admins, Chairmen, Treasurers</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-warning text-white shadow">
                <div class="card-body">
                    <i class="bi bi-shield-lock-fill icon"></i>
                    <div class="stat-label">Super Administrators</div>
                    <div class="stat-value">{{ number_format($stats['total_super_admins']) }}</div>
                    <small class="opacity-75">Platform Super Admins</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Security & Password Notice -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; border-radius: 12px;">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="p-3 bg-primary bg-opacity-25 rounded-3 text-primary flex-shrink-0">
                    <i class="bi bi-shield-check fs-2 text-info"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1 text-white">Centralized Authentication & Login Directory</h5>
                    <p class="mb-2 text-white-50 small">
                        This view is strictly restricted to <strong>Super Administrators</strong>. In accordance with industry cryptographic standards, user passwords are stored as irreversible salted hashes (bcrypt) and cannot be displayed in plaintext.
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-3 small text-white-50">
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i> View primary Login Email & Member Codes / Usernames</span>
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i> One-click credential copying for user onboarding</span>
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i> Super Admin direct password reset / recovery for locked accounts</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.user-logins.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4 col-sm-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" 
                               name="search" 
                               id="userSearchInput" 
                               class="form-control border-start-0 auto-search" 
                               placeholder="Search by name, email, member code, phone..." 
                               value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <select name="cooperative_id" class="form-select">
                        <option value="">All Cooperatives</option>
                        <option value="platform" {{ $cooperativeId === 'platform' ? 'selected' : '' }}>Platform Staff (No Cooperative)</option>
                        @foreach($cooperatives as $coop)
                            <option value="{{ $coop->id }}" {{ (string)$cooperativeId === (string)$coop->id ? 'selected' : '' }}>
                                {{ $coop->name }} ({{ $coop->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="super_admin" {{ $role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="chairman" {{ $role === 'chairman' ? 'selected' : '' }}>Chairman</option>
                        <option value="secretary" {{ $role === 'secretary' ? 'selected' : '' }}>Secretary</option>
                        <option value="treasurer" {{ $role === 'treasurer' ? 'selected' : '' }}>Treasurer</option>
                        <option value="member" {{ $role === 'member' ? 'selected' : '' }}>Member</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                    </select>
                </div>
                <div class="col-md-1 col-sm-6 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100" title="Filter Users">
                        <i class="bi bi-funnel"></i>
                    </button>
                    @if($search || $cooperativeId || $role || $status)
                        <a href="{{ route('admin.user-logins.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 text-gray-800 fw-bold">
                <i class="bi bi-people-fill text-primary me-2"></i> User Login Credentials Directory
            </h5>
            <span class="badge bg-light text-dark border px-3 py-2">
                Showing {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 py-3" style="width: 5%;">#</th>
                            <th class="py-3" style="width: 24%;">User Identity</th>
                            <th class="py-3" style="width: 15%;">Cooperative</th>
                            <th class="py-3" style="width: 10%;">Role</th>
                            <th class="py-3" style="width: 22%;">Login Email</th>
                            <th class="py-3" style="width: 16%;">Member Code / Username</th>
                            <th class="pe-3 py-3 text-end" style="width: 8%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="ps-3 text-muted fw-bold">{{ $u->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center me-3 flex-shrink-0 shadow-sm"
                                             style="width: 40px; height: 40px; background: {{ $u->isSuperAdmin() ? '#7c3aed' : ($u->role === 'admin' ? '#2563eb' : ($u->role === 'treasurer' ? '#f59e0b' : '#059669')) }};">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div class="text-truncate" style="max-width: 220px;">
                                            <div class="fw-bold text-dark text-truncate" title="{{ $u->name }}">{{ $u->name }}</div>
                                            <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $u->phone ?? 'No phone set' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($u->cooperative)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" title="{{ $u->cooperative->name }}">
                                            <i class="bi bi-building me-1"></i> {{ $u->cooperative->slug ?? $u->cooperative->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                            <i class="bi bi-globe me-1"></i> Platform Wide
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($u->role === 'super_admin')
                                        <span class="badge bg-danger text-white"><i class="bi bi-shield-lock-fill me-1"></i> Super Admin</span>
                                    @elseif($u->role === 'admin')
                                        <span class="badge bg-primary text-white"><i class="bi bi-person-gear me-1"></i> Admin</span>
                                    @elseif($u->role === 'chairman')
                                        <span class="badge bg-indigo text-white" style="background-color: #6366f1;"><i class="bi bi-award me-1"></i> Chairman</span>
                                    @elseif($u->role === 'secretary')
                                        <span class="badge bg-teal text-white" style="background-color: #0d9488;"><i class="bi bi-journal-text me-1"></i> Secretary</span>
                                    @elseif($u->role === 'treasurer')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-wallet2 me-1"></i> Treasurer</span>
                                    @else
                                        <span class="badge bg-info text-white"><i class="bi bi-person me-1"></i> Member</span>
                                    @endif
                                    
                                    @if(!$u->is_active)
                                        <span class="badge bg-danger mt-1 d-block"><i class="bi bi-slash-circle me-1"></i> Inactive</span>
                                    @elseif($u->isPendingMembership())
                                        <span class="badge bg-warning text-dark mt-1 d-block"><i class="bi bi-clock me-1"></i> Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fw-semibold text-dark text-truncate font-monospace" style="max-width: 190px;" title="{{ $u->email }}">
                                            {{ $u->email }}
                                        </span>
                                        <button type="button" 
                                                class="btn btn-sm btn-link text-primary p-0 copy-btn" 
                                                data-clipboard="{{ $u->email }}" 
                                                title="Copy Login Email">
                                            <i class="bi bi-clipboard fs-6"></i>
                                        </button>
                                    </div>
                                    @if($u->email_verified_at)
                                        <small class="text-success"><i class="bi bi-check2-circle me-1"></i>Verified</small>
                                    @else
                                        <small class="text-muted"><i class="bi bi-envelope me-1"></i>Unverified</small>
                                    @endif
                                </td>
                                <td>
                                    @if($u->member_code)
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-primary text-white font-monospace px-2 py-1 shadow-sm" style="font-size: 0.88rem; letter-spacing: 0.5px; color: #ffffff !important; background-color: #0d6efd !important;">
                                                <i class="bi bi-person-badge me-1"></i>{{ $u->member_code }}
                                            </span>
                                            <button type="button" 
                                                    class="btn btn-sm btn-link text-primary p-0 copy-btn" 
                                                    data-clipboard="{{ $u->member_code }}" 
                                                    title="Copy Member Code">
                                                <i class="bi bi-clipboard fs-6"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1">Username / Secret ID</small>
                                    @else
                                        <span class="badge bg-light text-secondary border px-2 py-1 small" style="color: #64748b !important;">
                                            <i class="bi bi-dash-circle me-1"></i>No code assigned
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#resetPasswordModal"
                                            data-user-id="{{ $u->id }}"
                                            data-user-name="{{ $u->name }}"
                                            data-user-email="{{ $u->email }}"
                                            data-user-code="{{ $u->member_code ?? 'None' }}"
                                            title="Set or reset password for {{ $u->name }}">
                                        <i class="bi bi-key-fill"></i> Reset
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                                    No users found matching your search and filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white py-3 d-flex justify-content-center">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Password Reset Modal for Super Admin -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="resetPasswordForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">
                        <i class="bi bi-key-fill me-2"></i> Reset User Password
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-warning"></i>
                        <div>
                            You are setting a new password as <strong>Super Administrator</strong> for this user account.
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">User Name:</span>
                            <strong id="modalUserName" class="text-dark"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Login Email:</span>
                            <span id="modalUserEmail" class="font-monospace text-primary fw-semibold"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Member Code:</span>
                            <span id="modalUserCode" class="badge bg-primary text-white font-monospace px-2 py-1" style="color: #ffffff !important; background-color: #0d6efd !important;"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="new_password" class="form-label mb-0 fw-semibold">New Password</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="generatePasswordBtn">
                                <i class="bi bi-magic me-1"></i> Generate Secure Password
                            </button>
                        </div>
                        <div class="input-group">
                            <input type="password" 
                                   name="password" 
                                   id="new_password" 
                                   class="form-control" 
                                   required 
                                   minlength="8" 
                                   placeholder="Minimum 8 characters">
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordVisibility">
                                <i class="bi bi-eye" id="toggleEyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" 
                               name="password_confirmation" 
                               id="password_confirmation" 
                               class="form-control" 
                               required 
                               minlength="8" 
                               placeholder="Re-type new password">
                    </div>

                    <div id="generatedPasswordNotice" class="alert alert-info py-2 px-3 small d-none">
                        <strong>Generated Password:</strong> 
                        <code id="generatedPasswordText" class="user-select-all fs-6 ms-1 text-danger fw-bold"></code>
                        <div class="mt-1 text-muted">Make sure to note down or copy this password to give to the user.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Floating Copy Feedback Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11000">
    <div id="copyToast" class="toast align-items-center text-white bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check2-circle text-success fs-5"></i>
                <span id="copyToastMessage">Copied to clipboard!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Password Reset Modal Setup
    const resetModalEl = document.getElementById('resetPasswordModal');
    const resetForm = document.getElementById('resetPasswordForm');
    const modalUserName = document.getElementById('modalUserName');
    const modalUserEmail = document.getElementById('modalUserEmail');
    const modalUserCode = document.getElementById('modalUserCode');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    const generateBtn = document.getElementById('generatePasswordBtn');
    const generatedNotice = document.getElementById('generatedPasswordNotice');
    const generatedText = document.getElementById('generatedPasswordText');
    const toggleVisibilityBtn = document.getElementById('togglePasswordVisibility');
    const toggleEyeIcon = document.getElementById('toggleEyeIcon');

    if (resetModalEl) {
        resetModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            const userEmail = button.getAttribute('data-user-email');
            const userCode = button.getAttribute('data-user-code');

            resetForm.action = `{{ url('/admin/user-logins') }}/${userId}/reset-password`;
            modalUserName.textContent = userName;
            modalUserEmail.textContent = userEmail;
            modalUserCode.textContent = userCode;

            newPasswordInput.value = '';
            confirmPasswordInput.value = '';
            generatedNotice.classList.add('d-none');
            newPasswordInput.type = 'password';
            confirmPasswordInput.type = 'password';
            toggleEyeIcon.className = 'bi bi-eye';
        });
    }

    // 2. Generate Random Strong Password
    if (generateBtn) {
        generateBtn.addEventListener('click', function () {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
            let password = '';
            // Ensure at least one upper, lower, number, and special character
            const uppers = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const lowers = 'abcdefghjkmnpqrstuvwxyz';
            const numbers = '23456789';
            const specials = '!@#$%&*';

            password += uppers.charAt(Math.floor(Math.random() * uppers.length));
            password += lowers.charAt(Math.floor(Math.random() * lowers.length));
            password += numbers.charAt(Math.floor(Math.random() * numbers.length));
            password += specials.charAt(Math.floor(Math.random() * specials.length));

            for (let i = 0; i < 6; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            // Shuffle
            password = password.split('').sort(() => 0.5 - Math.random()).join('');

            newPasswordInput.value = password;
            confirmPasswordInput.value = password;
            newPasswordInput.type = 'text';
            confirmPasswordInput.type = 'text';
            toggleEyeIcon.className = 'bi bi-eye-slash';

            generatedText.textContent = password;
            generatedNotice.classList.remove('d-none');
        });
    }

    // 3. Toggle Password Visibility
    if (toggleVisibilityBtn) {
        toggleVisibilityBtn.addEventListener('click', function () {
            const isPassword = newPasswordInput.type === 'password';
            newPasswordInput.type = isPassword ? 'text' : 'password';
            confirmPasswordInput.type = isPassword ? 'text' : 'password';
            toggleEyeIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    // 4. Click to Copy helper
    const copyBtns = document.querySelectorAll('.copy-btn');
    const toastEl = document.getElementById('copyToast');
    const toastMsg = document.getElementById('copyToastMessage');

    copyBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const textToCopy = this.getAttribute('data-clipboard');
            if (!textToCopy) return;

            navigator.clipboard.writeText(textToCopy).then(() => {
                toastMsg.textContent = `Copied "${textToCopy}" to clipboard!`;
                if (window.bootstrap && bootstrap.Toast) {
                    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
                    toast.show();
                } else {
                    alert(`Copied "${textToCopy}" to clipboard!`);
                }
            }).catch(err => {
                console.error('Copy failed:', err);
            });
        });
    });
});
</script>
@endpush
@endsection
