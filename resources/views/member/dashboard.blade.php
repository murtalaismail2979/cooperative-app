@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-speedometer2"></i> {{ auth()->user()->isApprovedMembership() ? 'My Dashboard' : 'Membership Application' }}</h4>
@endsection

@section('content')
@if(!auth()->user()->isApprovedMembership())
    {{-- PENDING OR REJECTED APPLICANT VIEW --}}
    @if(auth()->user()->isPendingMembership())
        <div class="card shadow-sm border-0 mb-4 text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="mb-3 mb-md-0">
                        <span class="badge bg-warning text-dark mb-2 px-3 py-1 fs-6"><i class="bi bi-clock-history"></i> Application In Review</span>
                        <h3 class="fw-bold mb-1 text-white">Welcome, {{ auth()->user()->name }}!</h3>
                        <p class="mb-0 text-white-50 fs-6">
                            Your application to join <strong>{{ auth()->user()->cooperative->name ?? 'the cooperative' }}</strong> has been submitted and is currently being processed by management.
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('profile.edit') }}" class="btn btn-light text-primary fw-semibold shadow-sm">
                            <i class="bi bi-pencil-square me-1"></i> Edit My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Progress Tracker Card -->
        <div class="card shadow-sm mb-4 border-0" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i> Application Status & Progress</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4 text-center">
                    <!-- Step 1 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 h-100">
                            <div class="mb-2">
                                <span class="badge rounded-circle bg-success p-3 text-white">
                                    <i class="bi bi-check-lg fs-4"></i>
                                </span>
                            </div>
                            <h6 class="fw-bold text-success mb-1">1. Application Submitted</h6>
                            <p class="small text-muted mb-0">Personal & Kin details submitted successfully.</p>
                            <span class="badge bg-success text-white mt-2">Completed</span>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 h-100">
                            <div class="mb-2">
                                <span class="badge rounded-circle bg-primary p-3 text-white">
                                    <i class="bi bi-search fs-4"></i>
                                </span>
                            </div>
                            <h6 class="fw-bold text-primary mb-1">2. Management Review</h6>
                            <p class="small text-muted mb-0">Cooperative admin is verifying your application.</p>
                            <span class="badge bg-primary text-white mt-2">In Progress</span>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="mb-2">
                                <span class="badge rounded-circle bg-secondary p-3 text-white">
                                    <i class="bi bi-person-badge fs-4"></i>
                                </span>
                            </div>
                            <h6 class="fw-bold text-secondary mb-1">3. Member ID Assignment</h6>
                            <p class="small text-muted mb-0">Official Member ID is generated upon approval.</p>
                            <span class="badge bg-secondary text-white mt-2">Pending Approval</span>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="mb-2">
                                <span class="badge rounded-circle bg-secondary p-3 text-white">
                                    <i class="bi bi-unlock fs-4"></i>
                                </span>
                            </div>
                            <h6 class="fw-bold text-secondary mb-1">4. Full Membership</h6>
                            <p class="small text-muted mb-0">Savings slots and cooperative benefits unlocked.</p>
                            <span class="badge bg-secondary text-white mt-2">Awaiting Approval</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-light rounded-3 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
                    <div class="small text-muted">
                        <strong>What to expect next:</strong> As soon as the cooperative management approves your application, your official <strong>Member ID</strong> will be generated, your savings slot will be activated, and full access to cooperative features will appear right here.
                    </div>
                </div>
            </div>
        </div>

    @elseif(auth()->user()->isRejectedMembership())
        <div class="card shadow-sm border-start border-4 border-danger mb-4" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle me-3">
                        <i class="bi bi-x-circle fs-2"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-danger mb-1">Membership Application Declined</h4>
                        <p class="text-secondary mb-2">
                            Your application for membership in <strong>{{ auth()->user()->cooperative->name ?? 'the cooperative' }}</strong> was not approved.
                        </p>
                        @if(auth()->user()->rejection_reason)
                            <div class="alert alert-danger mb-3 py-2 px-3">
                                <strong>Management Reason:</strong> {{ auth()->user()->rejection_reason }}
                            </div>
                        @endif
                        <p class="small text-muted mb-0">
                            Please contact the cooperative administration if you require further clarification or wish to re-apply.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Profile & Next of Kin Review Cards for Applicant -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i> My Profile</h5>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit Profile</a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th class="ps-0 text-muted" style="width: 40%;">Member ID</th>
                                    <td>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Assigned upon management approval</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0 text-muted">Full Name</th>
                                    <td class="fw-semibold text-dark">{{ auth()->user()->name }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0 text-muted">Email Address</th>
                                    <td class="text-dark">{{ auth()->user()->email }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0 text-muted">Phone Number</th>
                                    <td class="text-dark">{{ auth()->user()->phone ?? 'Not provided' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0 text-muted">Cooperative Society</th>
                                    <td><span class="badge bg-primary">{{ auth()->user()->cooperative->name ?? 'N/A' }}</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0 text-muted">Application Date</th>
                                    <td class="text-dark">{{ auth()->user()->created_at ? auth()->user()->created_at->format('M d, Y (h:i A)') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0 text-muted">Residential Address</th>
                                    <td class="text-dark">{{ auth()->user()->address ?? 'Not provided' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-people text-primary me-2"></i> Next of Kin Details</h5>
                </div>
                <div class="card-body p-4">
                    @if(auth()->user()->nextOfKin)
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th class="ps-0 text-muted" style="width: 40%;">Full Name</th>
                                        <td class="fw-semibold text-dark">{{ auth()->user()->nextOfKin->name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0 text-muted">Relationship</th>
                                        <td class="text-dark">{{ auth()->user()->nextOfKin->relationship }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0 text-muted">Phone Number</th>
                                        <td class="text-dark">{{ auth()->user()->nextOfKin->phone }}</td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0 text-muted">Contact Address</th>
                                        <td class="text-dark">{{ auth()->user()->nextOfKin->address ?? 'Not provided' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                            No next of kin details recorded.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@else
    {{-- APPROVED FULL MEMBER DASHBOARD VIEW --}}
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card stat-card bg-primary text-white shadow">
                <div class="card-body">
                    <i class="bi bi-piggy-bank icon"></i>
                    <div class="stat-label">My Savings Slots</div>
                    <div class="stat-value">{{ $data['totalSlots'] }}</div>
                    <small>Active slots</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card stat-card bg-success text-white shadow">
                <div class="card-body">
                    <i class="bi bi-wallet2 icon"></i>
                    <div class="stat-label">Month's Savings</div>
                    <div class="stat-value">₦{{ number_format($data['currentMonthSavings'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card stat-card bg-info text-white shadow">
                <div class="card-body">
                    <i class="bi bi-cash-stack icon"></i>
                    <div class="stat-label">Total Savings</div>
                    <div class="stat-value">₦{{ number_format($data['totalSavings'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Fee Card -->
    @if(isset($data['registrationFee']))
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-start border-4 border-info">
                <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 me-3">
                            <i class="bi bi-card-checklist fs-3"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold text-dark">Member Registration Fee</h6>
                            <small class="text-muted">Required: ₦{{ number_format($data['registrationFee']->fee_amount, 2) }} | Paid: <strong class="text-success">₦{{ number_format($data['registrationFee']->total_paid, 2) }}</strong> | Outstanding: <strong class="text-danger">₦{{ number_format($data['registrationFee']->outstanding_balance, 2) }}</strong></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        @if($data['registrationFee']->status === 'fully_paid')
                            <span class="badge bg-success rounded-pill px-3">Fully Paid</span>
                        @elseif($data['registrationFee']->status === 'partially_paid')
                            <span class="badge bg-warning text-dark rounded-pill px-3">Partially Paid</span>
                        @elseif($data['registrationFee']->status === 'requires_verification')
                            <span class="badge bg-info text-dark rounded-pill px-3">Requires Verification</span>
                        @else
                            <span class="badge bg-danger rounded-pill px-3">Unpaid</span>
                        @endif
                        <a href="{{ route('member.registration-fee.index') }}" class="btn btn-sm btn-outline-primary">View Details & Receipts</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Active Financing</h6>
                </div>
                <div class="card-body">
                    @if($data['activeLoan'])
                        <div class="d-flex justify-content-between mb-2">
                            <span>Principal:</span>
                            <strong>₦{{ number_format($data['activeLoan']->principal_amount, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <strong>₦{{ number_format($data['activeLoan']->total_amount, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Monthly Payment:</span>
                            <strong>₦{{ number_format($data['activeLoan']->monthly_payment, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Outstanding:</span>
                            <strong class="text-danger">₦{{ number_format($data['activeLoan']->outstanding_balance, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Remaining Months:</span>
                            <strong>{{ $data['activeLoan']->remaining_months }} / {{ $data['activeLoan']->duration_months }}</strong>
                        </div>
                    @else
                        <p class="text-muted mb-2">No active financing facility.</p>
                        @if(!empty($data['pendingLoanRequest']))
                            <div class="alert alert-info py-2 px-3 small mb-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-hourglass-split me-1"></i> Request for <strong>₦{{ number_format($data['pendingLoanRequest']->amount_requested, 2) }}</strong> is under review.
                                </div>
                                <a href="{{ route('member.loans') }}" class="btn btn-sm btn-outline-info">View</a>
                            </div>
                        @elseif(!empty($data['maxEligibleLoanAmount']) && $data['maxEligibleLoanAmount'] > 0)
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <div>
                                    <small class="text-muted d-block">Financing Capacity (3x Savings):</small>
                                    <strong class="text-success fs-6">₦{{ number_format($data['maxEligibleLoanAmount'], 2) }}</strong>
                                </div>
                                <a href="{{ route('member.loans.request') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-send-plus me-1"></i> Request Financing
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 recent-savings-header"
                     role="button"
                     id="recentSavingsHeader"
                     tabindex="0"
                     title="Click to view or hide savings history"
                     style="cursor: pointer; user-select: none;">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-clock-history me-1"></i> Recent Savings
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.75rem;">
                            {{ count($data['recentSavings']) }} {{ Str::plural('record', count($data['recentSavings'])) }}
                        </span>
                    </div>
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1 shadow-none" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#recentSavingsCollapse" 
                            data-toggle="collapse" 
                            data-target="#recentSavingsCollapse" 
                            aria-expanded="false" 
                            aria-controls="recentSavingsCollapse"
                            id="recentSavingsBtn">
                        <span class="savings-collapse-btn-text small fw-semibold">Click to View</span>
                        <i class="bi bi-chevron-down savings-chevron ms-1 transition-icon"></i>
                    </button>
                </div>
                <div class="collapse" id="recentSavingsCollapse">
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="table-light sticky-top shadow-sm" style="top: 0; z-index: 1;">
                                    <tr>
                                        <th class="ps-3 py-2">Month</th>
                                        <th class="py-2">Slots Paid</th>
                                        <th class="py-2">Total Amount</th>
                                        <th class="pe-3 py-2 text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['recentSavings'] as $saving)
                                        <tr>
                                            <td class="ps-3 fw-semibold text-dark">{{ \Carbon\Carbon::parse($saving->month)->format('M Y') }}</td>
                                            <td>{{ $saving->slots_count }} {{ Str::plural('Slot', $saving->slots_count) }}</td>
                                            <td class="fw-semibold {{ ($saving->status ?? 'paid') === 'paid' ? 'text-success' : 'text-muted' }}">
                                                ₦{{ number_format($saving->total_amount, 2) }}
                                            </td>
                                            <td class="pe-3 text-end">
                                                @if(($saving->status ?? 'paid') === 'paid')
                                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Paid</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> Unpaid</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-muted text-center py-4">No savings records yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Full savings history for your membership.</small>
                        <a href="{{ route('member.savings') }}" class="btn btn-sm btn-link text-decoration-none text-primary p-0 fw-semibold">
                            Full Savings Page <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-person"></i> Personal Profile</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Member Code:</span>
                        <strong class="text-primary">{{ auth()->user()->member_code ?? 'Pending' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Full Name:</span>
                        <strong>{{ auth()->user()->name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Email Address:</span>
                        <strong>{{ auth()->user()->email }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Phone Number:</span>
                        <strong>{{ auth()->user()->phone ?? 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Registration Year:</span>
                        <strong>{{ auth()->user()->registration_year }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Address:</span>
                        <strong>{{ auth()->user()->address ?? 'N/A' }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-people"></i> Next of Kin Details</h6>
                </div>
                <div class="card-body">
                    @if(auth()->user()->nextOfKin)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Name:</span>
                            <strong>{{ auth()->user()->nextOfKin->name }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Relationship:</span>
                            <strong>{{ auth()->user()->nextOfKin->relationship }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Phone Number:</span>
                            <strong>{{ auth()->user()->nextOfKin->phone }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Contact Address:</span>
                            <strong>{{ auth()->user()->nextOfKin->address ?? 'N/A' }}</strong>
                        </div>
                    @else
                        <p class="text-muted mb-0">No next of kin details recorded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Links</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('member.savings') }}" class="btn btn-outline-primary w-100 p-3">
                                <i class="bi bi-piggy-bank fs-3 d-block"></i>
                                My Savings
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('member.loans') }}" class="btn btn-outline-warning w-100 p-3">
                                <i class="bi bi-cash-stack fs-3 d-block"></i>
                                My Financing
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('member.dividends') }}" class="btn btn-outline-success w-100 p-3">
                                <i class="bi bi-gift fs-3 d-block"></i>
                                Dividends
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('password.recovery') }}" class="btn btn-outline-secondary w-100 p-3">
                                <i class="bi bi-shield-lock fs-3 d-block"></i>
                                Retrieve Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<style>
    .recent-savings-header {
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .recent-savings-header:hover {
        background-color: #f8fafc;
    }
    .transition-icon {
        transition: transform 0.25s ease-in-out;
    }
    .savings-chevron.rotated {
        transform: rotate(180deg);
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const collapseEl = document.getElementById('recentSavingsCollapse');
    const headerEl = document.getElementById('recentSavingsHeader');
    const btnEl = document.getElementById('recentSavingsBtn');
    const btnTexts = document.querySelectorAll('.savings-collapse-btn-text');
    const chevrons = document.querySelectorAll('.savings-chevron');

    function updateState(isOpen) {
        btnTexts.forEach(el => el.textContent = isOpen ? 'Hide History' : 'Click to View');
        chevrons.forEach(el => {
            if (isOpen) {
                el.style.transform = 'rotate(180deg)';
                el.classList.add('rotated');
            } else {
                el.style.transform = 'rotate(0deg)';
                el.classList.remove('rotated');
            }
        });
        if (headerEl) {
            headerEl.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
        if (btnEl) {
            btnEl.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    if (collapseEl) {
        // Bootstrap event listeners
        collapseEl.addEventListener('show.bs.collapse', function () {
            updateState(true);
        });
        collapseEl.addEventListener('shown.bs.collapse', function () {
            updateState(true);
        });
        collapseEl.addEventListener('hide.bs.collapse', function () {
            updateState(false);
        });
        collapseEl.addEventListener('hidden.bs.collapse', function () {
            updateState(false);
        });
    }

    if (headerEl && btnEl) {
        // Clicking anywhere on the header toggles the button if the button itself wasn't directly clicked
        headerEl.addEventListener('click', function (e) {
            if (!e.target.closest('#recentSavingsBtn')) {
                btnEl.click();
            }
        });

        // Accessibility: Keyboard support (Enter or Space) on header
        headerEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                btnEl.click();
            }
        });
    }

    // Safety fallback: in case Bootstrap collapse is not initialized on the element
    if (btnEl && collapseEl) {
        btnEl.addEventListener('click', function () {
            setTimeout(function () {
                // If neither Bootstrap 4 nor Bootstrap 5 toggled the class
                const isTransitioning = collapseEl.classList.contains('collapsing');
                const isShown = collapseEl.classList.contains('show');
                if (!isTransitioning && !isShown && btnEl.getAttribute('aria-expanded') !== 'true') {
                    // Manual toggle fallback
                    collapseEl.classList.toggle('show');
                    const manualShown = collapseEl.classList.contains('show');
                    updateState(manualShown);
                }
            }, 100);
        });
    }
});
</script>
@endpush