@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="bi bi-inbox"></i> Financing Requests</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.loans.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-cash-stack"></i> All Active Financing
            </a>
            <a href="{{ route('admin.loans.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Grant Financing Directly
            </a>
        </div>
    </div>
@endsection

@section('content')
<!-- Summary Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-warning text-white shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-hourglass-split icon"></i>
                <div class="stat-label">Pending Requests</div>
                <div class="stat-value">{{ $stats['pendingCount'] }}</div>
                <small>₦{{ number_format($stats['pendingTotalAmount'], 2) }} Requested</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-success text-white shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-check2-circle icon"></i>
                <div class="stat-label">Approved & Disbursed</div>
                <div class="stat-value">{{ $stats['approvedCount'] }}</div>
                <small>₦{{ number_format($stats['approvedTotalAmount'], 2) }} Granted</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-danger text-white shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-x-circle icon"></i>
                <div class="stat-label">Declined Requests</div>
                <div class="stat-value">{{ $stats['rejectedCount'] }}</div>
                <small>Applications rejected</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-primary text-white shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-shield-check icon"></i>
                <div class="stat-label">Borrowing Cap Rule</div>
                <div class="stat-value">3x Savings</div>
                <small>Strict cooperative policy enforced</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar & Search -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.loan-requests.index') }}" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by member name or member code..." value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? '') === 'all' ? 'selected' : '' }}>-- All Statuses --</option>
                    <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending Review ({{ $stats['pendingCount'] }})</option>
                    <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>Approved ({{ $stats['approvedCount'] }})</option>
                    <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Declined ({{ $stats['rejectedCount'] }})</option>
                    <option value="cancelled" {{ ($status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            @if(!empty($search) || (!empty($status) && $status !== 'all'))
                <div class="col-auto">
                    <a href="{{ route('admin.loan-requests.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-list-ul me-1"></i> Applications Log</h6>
        <span class="badge bg-light text-dark border">{{ $requests->total() }} Total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ref #</th>
                        <th>Date</th>
                        <th>Member</th>
                        <th>Savings Balance</th>
                        <th>3x Max Cap</th>
                        <th>Requested</th>
                        <th>Duration</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td><strong>#{{ $req->id }}</strong></td>
                            <td>{{ $req->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if($req->user)
                                    <div class="fw-bold">{{ $req->user->name }}</div>
                                    <small class="badge bg-light text-secondary border font-monospace">{{ $req->user->member_code ?? 'NO-CODE' }}</small>
                                @else
                                    <span class="text-muted">Deleted Member</span>
                                @endif
                            </td>
                            <td>
                                ₦{{ number_format($req->member_total_savings_at_request, 2) }}
                            </td>
                            <td>
                                <span class="text-success fw-semibold">₦{{ number_format($req->max_eligible_amount_at_request, 2) }}</span>
                            </td>
                            <td>
                                <strong class="text-primary fs-6">₦{{ number_format($req->amount_requested, 2) }}</strong>
                            </td>
                            <td>{{ $req->duration_months }} Mos</td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 180px;" title="{{ $req->purpose }}">
                                    {{ $req->purpose ?? 'N/A' }}
                                </span>
                            </td>
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
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.loan-requests.show', $req->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i> Review
                                </a>
                                @if($req->isPending())
                                    <a href="{{ route('admin.loan-requests.edit', $req->id) }}" class="btn btn-outline-secondary btn-sm" title="Edit Request Terms">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No financing requests found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
