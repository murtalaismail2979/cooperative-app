@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="bi bi-cash-stack"></i> My Financing</h4>
        <a href="{{ route('member.loans.request') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-send-plus me-1"></i> Apply for Financing
        </a>
    </div>
@endsection

@section('content')
<!-- Financing Capacity & Pre-qualification Banner -->
@if(isset($eligibility))
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-4 border-end">
                <small class="text-muted text-uppercase fw-semibold">Total Verified Savings</small>
                <h4 class="mb-0 text-primary fw-bold">₦{{ number_format($eligibility['total_savings'], 2) }}</h4>
                <small class="text-muted">Current savings balance</small>
            </div>
            <div class="col-md-4 border-end">
                <small class="text-muted text-uppercase fw-semibold">Financing Capacity (3x Savings)</small>
                <h4 class="mb-0 text-success fw-bold">₦{{ number_format($eligibility['max_eligible_amount'], 2) }}</h4>
                <small class="text-muted">Maximum allowable facility</small>
            </div>
            <div class="col-md-4">
                <small class="text-muted text-uppercase fw-semibold">Eligibility Status</small>
                @if($eligibility['is_eligible'])
                    <div class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Pre-Qualified</div>
                    <small class="text-muted">Rates: <strong>5%</strong> (1–2m) &bull; <strong>10%</strong> (3–6m) &bull; <strong>20%</strong> (7–12m)</small>
                @elseif($eligibility['has_active_loan'])
                    <div class="text-warning fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Active Facility Outstanding</div>
                    <small class="text-muted">Must be fully repaid first</small>
                @elseif($eligibility['has_pending_request'])
                    <div class="text-info fw-bold"><i class="bi bi-hourglass-split me-1"></i> Request Under Review</div>
                    <small class="text-muted">Awaiting executive committee decision</small>
                @else
                    <div class="text-secondary fw-bold"><i class="bi bi-dash-circle me-1"></i> Ineligible</div>
                    <small class="text-muted">Check prerequisites</small>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- My Financing Applications History -->
@if(isset($loanRequests) && $loanRequests->count() > 0)
<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-send-check me-1"></i> My Financing Requests</h6>
        <span class="badge bg-light text-dark border">{{ $loanRequests->count() }} {{ Str::plural('Request', $loanRequests->count()) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Req #</th>
                        <th>Date</th>
                        <th>Amount Requested</th>
                        <th>Duration</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loanRequests as $req)
                        <tr>
                            <td><strong>#{{ $req->id }}</strong></td>
                            <td>{{ $req->created_at->format('d/m/Y') }}</td>
                            <td class="fw-bold">₦{{ number_format($req->amount_requested, 2) }}</td>
                            <td>{{ $req->duration_months }} Months</td>
                            <td><span class="text-truncate d-inline-block" style="max-width: 250px;">{{ $req->purpose ?? 'N/A' }}</span></td>
                            <td>
                                @if($req->isPending())
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending Review</span>
                                @elseif($req->isApproved())
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                @elseif($req->isRejected())
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Declined</span>
                                @elseif($req->isCancelled())
                                    <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i> Cancelled</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($req->isPending())
                                    <form method="POST" action="{{ route('member.loans.request.cancel', $req->id) }}" class="d-inline" onsubmit="return confirm('Cancel this financing request?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-x"></i> Cancel
                                        </button>
                                    </form>
                                @elseif($req->isRejected() && $req->rejection_reason)
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#loanReasonModal{{ $req->id }}">
                                        View Note
                                    </button>

                                    <!-- Rejection Reason Modal -->
                                    <div class="modal fade" id="loanReasonModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold text-danger"><i class="bi bi-info-circle me-1"></i> Application Feedback #{{ $req->id }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="mb-2 text-muted small">Executive Decision Note:</div>
                                                    <div class="p-3 bg-light rounded text-dark border">
                                                        {{ $req->rejection_reason }}
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card shadow mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('member.loans') }}" class="row g-3 align-items-center">
            <div class="col-md-4 col-sm-6">
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Years --</option>
                    @foreach($years ?? [] as $y)
                        <option value="{{ $y }}" {{ ($year ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <select name="month" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Months --</option>
                    @foreach($months ?? [] as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ ($month ?? '') == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            @if(!empty($year) || !empty($month))
            <div class="col-auto">
                <a href="{{ route('member.loans') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear Filter</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        @forelse($loans as $loan)
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Financing #{{ $loan->id }}
                        <span class="badge bg-{{ $loan->status === 'active' ? 'warning' : ($loan->status === 'fully_paid' ? 'success' : 'secondary') }} ms-2">
                            {{ str_replace('_', ' ', ucfirst($loan->status)) }}
                        </span>
                    </h6>
                    <small class="text-muted">Granted: {{ $loan->date_granted?->format('d/m/Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted">Principal Amount</small>
                                <div class="fw-bold">₦{{ number_format($loan->principal_amount, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Profit Rate</small>
                                <div class="fw-bold">{{ $loan->profit_rate }}%</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Total Amount</small>
                                <div class="fw-bold">₦{{ number_format($loan->total_amount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted">Monthly Payment</small>
                                <div class="fw-bold">₦{{ number_format($loan->monthly_payment, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Remaining Balance</small>
                                <div class="fw-bold text-{{ $loan->outstanding_balance > 0 ? 'danger' : 'success' }}">
                                    ₦{{ number_format($loan->outstanding_balance, 2) }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Progress</small>
                                <div class="progress" style="height: 20px;">
                                    @php
                                        $paidMonths = $loan->duration_months - $loan->remaining_months;
                                        $percent = $loan->duration_months > 0 ? round(($paidMonths / $loan->duration_months) * 100) : 0;
                                    @endphp
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%">
                                        {{ $paidMonths }}/{{ $loan->duration_months }} months
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($loan->repayments->count() > 0)
                        <hr>
                        <h6>Repayment History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($loan->repayments as $repayment)
                                        <tr>
                                            <td>{{ $repayment->month_number }}</td>
                                            <td>₦{{ number_format($repayment->amount, 2) }}</td>
                                            <td>{{ $repayment->payment_date?->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="bi bi-cash-stack fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">You have no financing records.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection