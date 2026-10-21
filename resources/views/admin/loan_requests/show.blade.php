@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="bi bi-file-earmark-text"></i> Financing Application #{{ $loanRequest->id }}</h4>
        <div class="d-flex gap-2">
            @if($loanRequest->isPending())
                <a href="{{ route('admin.loan-requests.edit', $loanRequest->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit Request Terms
                </a>
            @endif
            <a href="{{ route('admin.loan-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Requests
            </a>
        </div>
    </div>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->has('general'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first('general') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Member Financial Snapshot Dossier (Left Column) -->
    <div class="col-lg-5 mb-4">
        <!-- Member Profile Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-person-badge me-1"></i> Applicant Profile</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="avatar rounded-circle p-3 fs-3" style="background-color: #eef2ff; color: #4f46e5;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">{{ $member->name }}</h5>
                        <span class="badge bg-light text-secondary border font-monospace">{{ $member->member_code ?? 'NO-CODE' }}</span>
                        <span class="badge bg-{{ $member->is_active ? 'success' : 'danger' }} ms-1">
                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Email:</span>
                        <span class="fw-semibold">{{ $member->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Phone:</span>
                        <span class="fw-semibold">{{ $member->phone ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Registration:</span>
                        <span class="fw-semibold">{{ $member->registration_month_year }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Active Savings Slots:</span>
                        <span class="badge bg-primary rounded-pill">{{ $member->savingsSlots->where('is_active', true)->count() }} Slots</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Cooperative Pre-Qualification Metrics Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-shield-check me-1"></i> Pre-Qualification & Limits</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Current Verified Savings Balance</small>
                    <h4 class="fw-bold text-primary mb-0">₦{{ number_format($currentSavings, 2) }}</h4>
                    <small class="text-muted">Savings at submission: ₦{{ number_format($loanRequest->member_total_savings_at_request, 2) }}</small>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Maximum Borrowing Cap (3x Savings)</small>
                    <h4 class="fw-bold text-success mb-0">₦{{ number_format($maxEligibleNow, 2) }}</h4>
                    <small class="text-muted">Cap at submission: ₦{{ number_format($loanRequest->max_eligible_amount_at_request, 2) }}</small>
                </div>

                <hr>

                <!-- Borrowing Multiple Evaluation -->
                @php
                    $multiple = $loanRequest->member_total_savings_at_request > 0
                        ? round($loanRequest->amount_requested / $loanRequest->member_total_savings_at_request, 2)
                        : 0;
                    $isWithinCap = $loanRequest->amount_requested <= $loanRequest->max_eligible_amount_at_request;
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold">Borrowing Ratio:</span>
                        <span class="badge bg-{{ $isWithinCap ? 'success' : 'danger' }}">{{ $multiple }}x Savings</span>
                    </div>
                    <div class="small text-muted">
                        @if($isWithinCap)
                            <i class="bi bi-check-circle text-success me-1"></i> Within the cooperative's maximum 3x savings policy.
                        @else
                            <i class="bi bi-x-circle text-danger me-1"></i> Exceeds maximum 3x savings policy!
                        @endif
                    </div>
                </div>

                <hr>

                <!-- Active Loan Status -->
                <div>
                    <span class="small fw-semibold d-block mb-2">Current Active Financing:</span>
                    @if($activeLoan)
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark">
                                    <i class="bi bi-credit-card-2-front text-primary me-1"></i> Active Facility #{{ $activeLoan->id }}
                                </span>
                                <span class="badge bg-secondary">In Repayment</span>
                            </div>
                            <div class="small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Outstanding Balance:</span>
                                    <strong class="text-danger">₦{{ number_format($activeLoan->outstanding_balance, 2) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Remaining Months:</span>
                                    <strong class="text-dark">{{ $activeLoan->remaining_months }}/{{ $activeLoan->duration_months }}</strong>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-light rounded border text-muted small d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <span>No active financing facilities outstanding.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Past Financing Track Record -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-1"></i> Past Financing Track Record</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Principal</th>
                                <th>Granted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pastLoans as $past)
                                <tr>
                                    <td><strong>#{{ $past->id }}</strong></td>
                                    <td>₦{{ number_format($past->principal_amount, 2) }}</td>
                                    <td>{{ $past->date_granted?->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $past->status === 'active' ? 'warning' : ($past->status === 'fully_paid' ? 'success' : 'secondary') }}">
                                            {{ str_replace('_', ' ', ucfirst($past->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted small">No past financing history.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details & Decision Center (Right Column) -->
    <div class="col-lg-7 mb-4">
        <!-- Application Details Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-info-circle me-1"></i> Request Details</h6>
                @if($loanRequest->isPending())
                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending Review</span>
                @elseif($loanRequest->isApproved())
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved & Disbursed</span>
                @elseif($loanRequest->isRejected())
                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Declined</span>
                @elseif($loanRequest->isCancelled())
                    <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i> Cancelled by Member</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border">
                            <small class="text-muted text-uppercase fw-semibold">Requested Principal</small>
                            <h3 class="mb-0 text-primary fw-bold">₦{{ number_format($loanRequest->amount_requested, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border">
                            <small class="text-muted text-uppercase fw-semibold">Repayment Duration</small>
                            <h3 class="mb-0 text-dark fw-bold">{{ $loanRequest->duration_months }} Months</h3>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Tiered Profit Rate</small>
                        <strong class="text-dark fs-6">{{ $loanRequest->estimated_profit_rate }}%</strong>
                        <small class="text-muted d-block">for {{ $loanRequest->duration_months }} Months</small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Estimated Total Profit</small>
                        <strong class="text-primary fs-6">₦{{ number_format($loanRequest->estimated_profit, 2) }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Estimated Monthly Payment</small>
                        <strong class="text-success fs-6">₦{{ number_format($loanRequest->estimated_monthly_payment, 2) }} / mo</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block fw-semibold text-uppercase">Purpose / Justification</small>
                    <div class="p-3 bg-light rounded border mt-1 text-dark">
                        {{ $loanRequest->purpose ?? 'No specific purpose provided.' }}
                    </div>
                </div>

                <div class="small text-muted">
                    <i class="bi bi-calendar3 me-1"></i> Application submitted on {{ $loanRequest->created_at->format('d F, Y \a\t h:i A') }}
                </div>
            </div>
        </div>

        <!-- Decision Center / Audit Record -->
        @if($loanRequest->isPending())
            <div class="card shadow-sm border-0 border-top border-primary border-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-sliders me-1"></i> Executive Decision Center</h6>
                </div>
                <div class="card-body p-4">
                    @if($activeLoan)
                        <div class="alert alert-warning mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong>Policy Notice:</strong> Member currently has an active loan. Cooperative rules require all existing financing to be fully settled before approving a new facility.
                        </div>
                    @endif

                    <p class="text-muted">You can review and approve this financing facility, adjust the approved amount or terms as needed, or decline the application with an explanatory note.</p>

                    <div class="d-flex gap-3 flex-wrap">
                        <!-- Approve Button (Triggers Modal) -->
                        <button type="button" class="btn btn-success btn-lg px-4" data-bs-toggle="modal" data-bs-target="#approveModal" {{ $activeLoan ? 'disabled' : '' }}>
                            <i class="bi bi-check-circle me-1"></i> Approve & Disburse Financing
                        </button>

                        <!-- Edit Request Terms Button -->
                        <a href="{{ route('admin.loan-requests.edit', $loanRequest->id) }}" class="btn btn-outline-primary btn-lg px-4">
                            <i class="bi bi-pencil-square me-1"></i> Edit Request Terms
                        </a>

                        <!-- Decline Button (Triggers Modal) -->
                        <button type="button" class="btn btn-outline-danger btn-lg px-4" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i> Decline Application
                        </button>
                    </div>
                </div>
            </div>

            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.loan-requests.approve', $loanRequest->id) }}">
                            @csrf
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title fw-bold"><i class="bi bi-check-circle me-1"></i> Approve Financing Facility</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Approved Principal Amount (₦) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           step="1000" 
                                           min="1000" 
                                           name="approved_amount" 
                                           class="form-control" 
                                           value="{{ old('approved_amount', $loanRequest->amount_requested) }}" 
                                           required>
                                    <small class="text-muted">Requested: ₦{{ number_format($loanRequest->amount_requested, 2) }} | 3x Cap: ₦{{ number_format($maxEligibleNow, 2) }}</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Repayment Duration & Tiered Profit <span class="text-danger">*</span></label>
                                    <select name="approved_duration_months" id="approved_duration_months" class="form-select" required onchange="updateApprovedProfitRate(this.value)">
                                        <optgroup label="5% Tier (1 – 2 Months)">
                                            <option value="1" {{ old('approved_duration_months', $loanRequest->duration_months) == 1 ? 'selected' : '' }}>1 Month (5% Profit)</option>
                                            <option value="2" {{ old('approved_duration_months', $loanRequest->duration_months) == 2 ? 'selected' : '' }}>2 Months (5% Profit)</option>
                                        </optgroup>
                                        <optgroup label="10% Tier (3 – 6 Months)">
                                            <option value="3" {{ old('approved_duration_months', $loanRequest->duration_months) == 3 ? 'selected' : '' }}>3 Months (10% Profit)</option>
                                            <option value="4" {{ old('approved_duration_months', $loanRequest->duration_months) == 4 ? 'selected' : '' }}>4 Months (10% Profit)</option>
                                            <option value="5" {{ old('approved_duration_months', $loanRequest->duration_months) == 5 ? 'selected' : '' }}>5 Months (10% Profit)</option>
                                            <option value="6" {{ old('approved_duration_months', $loanRequest->duration_months) == 6 ? 'selected' : '' }}>6 Months (10% Profit)</option>
                                        </optgroup>
                                        <optgroup label="20% Tier (7 – 12 Months)">
                                            <option value="7" {{ old('approved_duration_months', $loanRequest->duration_months) == 7 ? 'selected' : '' }}>7 Months (20% Profit)</option>
                                            <option value="8" {{ old('approved_duration_months', $loanRequest->duration_months) == 8 ? 'selected' : '' }}>8 Months (20% Profit)</option>
                                            <option value="9" {{ old('approved_duration_months', $loanRequest->duration_months) == 9 ? 'selected' : '' }}>9 Months (20% Profit)</option>
                                            <option value="10" {{ old('approved_duration_months', $loanRequest->duration_months) == 10 ? 'selected' : '' }}>10 Months (20% Profit)</option>
                                            <option value="11" {{ old('approved_duration_months', $loanRequest->duration_months) == 11 ? 'selected' : '' }}>11 Months (20% Profit)</option>
                                            <option value="12" {{ old('approved_duration_months', $loanRequest->duration_months) == 12 ? 'selected' : '' }}>12 Months (20% Profit)</option>
                                        </optgroup>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Profit Rate (%) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           max="100" 
                                           name="approved_profit_rate" 
                                           id="approved_profit_rate" 
                                           class="form-control" 
                                           value="{{ old('approved_profit_rate', $loanRequest->estimated_profit_rate) }}" 
                                           required>
                                    <small class="text-muted">Auto-updated based on duration tier (5%, 10%, 20%). Can be manually adjusted if required.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Disbursement / Grant Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           name="date_granted" 
                                           class="form-control" 
                                           value="{{ old('date_granted', date('Y-m-d')) }}" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Executive / Committee Notes (Optional)</label>
                                    <textarea name="admin_notes" rows="2" class="form-control" placeholder="Optional notes for internal cooperative record...">{{ old('admin_notes') }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success fw-bold">
                                    <i class="bi bi-check-circle me-1"></i> Confirm & Disburse
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.loan-requests.reject', $loanRequest->id) }}">
                            @csrf
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-1"></i> Decline Financing Application</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                                    <textarea name="rejection_reason" 
                                              rows="3" 
                                              class="form-control" 
                                              placeholder="Provide a constructive reason explaining why this application was declined (this will be visible to the member)..." 
                                              required>{{ old('rejection_reason') }}</textarea>
                                    <small class="text-muted">This feedback will be presented to the member on their dashboard.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Internal Executive Notes (Optional)</label>
                                    <textarea name="admin_notes" rows="2" class="form-control" placeholder="Optional private internal notes...">{{ old('admin_notes') }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger fw-bold">
                                    <i class="bi bi-x-circle me-1"></i> Confirm Decline
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        @elseif($loanRequest->isApproved())
            <!-- Approved Audit Record -->
            <div class="card shadow-sm border-0 border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-2 rounded-circle" style="background-color: #ecfdf5; color: #059669;">
                            <i class="bi bi-check-circle-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-success mb-0">Application Approved & Disbursed</h5>
                            <small class="text-muted">
                                Reviewed by {{ $loanRequest->reviewer->name ?? 'Executive Admin' }} on {{ $loanRequest->reviewed_at?->format('d M, Y \a\t h:i A') }}
                            </small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Approved Principal</small>
                            <strong class="text-dark fs-5">₦{{ number_format($loanRequest->approved_amount, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Approved Duration</small>
                            <strong class="text-dark fs-5">{{ $loanRequest->approved_duration_months }} Months</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Approved Profit Rate</small>
                            <strong class="text-dark fs-5">{{ $loanRequest->approved_profit_rate }}%</strong>
                        </div>
                    </div>

                    @if($loanRequest->admin_notes)
                        <div class="mb-3">
                            <small class="text-muted d-block">Executive Notes</small>
                            <div class="p-2 bg-light rounded text-dark small border">{{ $loanRequest->admin_notes }}</div>
                        </div>
                    @endif

                    @if($loanRequest->loan_id)
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                            <span>Active Facility: <strong>Financing #{{ $loanRequest->loan_id }}</strong></span>
                            <a href="{{ route('admin.loans.show', $loanRequest->loan_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i> View Active Financing Facility
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        @elseif($loanRequest->isRejected())
            <!-- Rejected Audit Record -->
            <div class="card shadow-sm border-0 border-start border-danger border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-2 rounded-circle" style="background-color: #fef2f2; color: #dc2626;">
                            <i class="bi bi-x-circle-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-danger mb-0">Application Declined</h5>
                            <small class="text-muted">
                                Reviewed by {{ $loanRequest->reviewer->name ?? 'Executive Admin' }} on {{ $loanRequest->reviewed_at?->format('d M, Y \a\t h:i A') }}
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block fw-semibold">Stated Rejection Reason (Visible to Member):</small>
                        <div class="p-3 bg-light rounded border text-danger mt-1">
                            {{ $loanRequest->rejection_reason }}
                        </div>
                    </div>

                    @if($loanRequest->admin_notes)
                        <div class="mb-3">
                            <small class="text-muted d-block fw-semibold">Internal Executive Notes:</small>
                            <div class="p-2 bg-light rounded text-dark small border">{{ $loanRequest->admin_notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

        @elseif($loanRequest->isCancelled())
            <!-- Cancelled Record -->
            <div class="card shadow-sm border-0 border-start border-secondary border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 rounded-circle" style="background-color: #f1f5f9; color: #64748b;">
                            <i class="bi bi-slash-circle fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-secondary mb-0">Application Cancelled</h5>
                            <small class="text-muted">
                                Cancelled by member prior to executive committee review.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateApprovedProfitRate(months) {
    months = parseInt(months) || 12;
    let rate = 20;
    if (months <= 2) rate = 5;
    else if (months <= 6) rate = 10;
    else rate = 20;
    const input = document.getElementById('approved_profit_rate');
    if (input) input.value = rate;
}
</script>
@endpush
