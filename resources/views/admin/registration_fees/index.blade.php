@extends('layouts.app')

@section('title', 'Registration Fees Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-card-checklist me-2 text-primary"></i>Member Registration Fees</h2>
            <p class="text-muted mb-0">Track and manage mandatory member registration fee obligations and payments.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.batch-upload.index', ['type' => 'registration_fees']) }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-cloud-arrow-up me-1"></i> Batch Upload Registration Fees
            </a>
            <a href="{{ route('admin.registration-fees.settings') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-gear-fill me-1"></i> Fee Settings
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                <i class="bi bi-plus-lg me-1"></i> Record Payment
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <i class="bi bi-wallet2 icon"></i>
                    <div class="stat-label">Total Expected</div>
                    <div class="stat-value">₦{{ number_format($stats['totalExpected'], 2) }}</div>
                    <small class="opacity-75">Configured Fee: ₦{{ number_format($stats['currentFeeAmount'], 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <i class="bi bi-check-circle icon"></i>
                    <div class="stat-label">Total Collected</div>
                    <div class="stat-value">₦{{ number_format($stats['totalCollected'], 2) }}</div>
                    <small class="opacity-75">{{ $stats['fullyPaidCount'] }} Fully Paid Members</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle icon"></i>
                    <div class="stat-label">Total Outstanding</div>
                    <div class="stat-value">₦{{ number_format($stats['totalOutstanding'], 2) }}</div>
                    <small class="opacity-75">{{ $stats['outstandingCount'] }} Members Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <i class="bi bi-question-circle icon"></i>
                    <div class="stat-label">Requires Verification</div>
                    <div class="stat-value">{{ $stats['requiresVerificationCount'] }}</div>
                    <small class="opacity-75">Existing Unverified Records</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.registration-fees.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-medium">Search Member</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Name, Code or Email..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">Payment Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="partially_paid" {{ $status === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="fully_paid" {{ $status === 'fully_paid' ? 'selected' : '' }}>Fully Paid</option>
                        <option value="requires_verification" {{ $status === 'requires_verification' ? 'selected' : '' }}>Requires Verification</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary me-2"><i class="bi bi-filter me-1"></i> Filter</button>
                    <a href="{{ route('admin.registration-fees.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
                <div class="col-md-2 d-flex align-items-end justify-content-end">
                    <a href="{{ route('admin.reports.savings.export', ['tab' => 'registration_fees', 'search' => $search, 'status' => $status]) }}" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Members Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Registration Fee</th>
                            <th>Total Paid</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                            <th>Payments</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            @php
                                $fee = $member->registrationFee;
                                $feeAmount = $fee ? $fee->fee_amount : $stats['currentFeeAmount'];
                                $totalPaid = $fee ? $fee->total_paid : 0.00;
                                $outstanding = max(0.00, $feeAmount - $totalPaid);
                                $feeStatus = $fee ? $fee->status : 'unpaid';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $member->name }}</div>
                                    <small class="text-muted">{{ $member->member_code ?? 'No Code' }} | {{ $member->email }}</small>
                                </td>
                                <td class="fw-semibold">₦{{ number_format($feeAmount, 2) }}</td>
                                <td class="text-success fw-semibold">₦{{ number_format($totalPaid, 2) }}</td>
                                <td class="text-danger fw-semibold">₦{{ number_format($outstanding, 2) }}</td>
                                <td>
                                    @if($feeStatus === 'fully_paid')
                                        <span class="badge bg-success">Fully Paid</span>
                                    @elseif($feeStatus === 'partially_paid')
                                        <span class="badge bg-warning text-dark">Partially Paid</span>
                                    @elseif($feeStatus === 'requires_verification')
                                        <span class="badge bg-info text-dark">Requires Verification</span>
                                    @elseif($feeStatus === 'cancelled')
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @else
                                        <span class="badge bg-danger">Unpaid</span>
                                    @endif
                                </td>
                                <td>
                                    @if($fee && $fee->payments->count() > 0)
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                {{ $fee->payments->count() }} Payment(s)
                                            </button>
                                            <ul class="dropdown-menu">
                                                @foreach($fee->payments as $pmt)
                                                    <li>
                                                        <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('admin.registration-fees.receipt', $pmt->id) }}" target="_blank">
                                                            <span>₦{{ number_format($pmt->amount, 2) }} <small class="text-muted">({{ $pmt->payment_date->format('d/m/Y') }})</small></span>
                                                            <i class="bi bi-printer text-primary ms-2"></i>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <span class="text-muted small">No payments</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-primary me-1" 
                                            onclick="openPaymentModal({{ $member->id }}, '{{ addslashes($member->name) }}', {{ $outstanding }})"
                                            title="Record Payment">
                                        <i class="bi bi-plus-circle"></i> Pay
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" 
                                            onclick="openReconcileModal({{ $member->id }}, '{{ addslashes($member->name) }}', '{{ $feeStatus }}', {{ $feeAmount }}, {{ $totalPaid }}, '{{ addslashes($fee->notes ?? '') }}')"
                                            title="Reconcile Record">
                                        <i class="bi bi-pencil-square"></i> Reconcile
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No member registration fee records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($members->hasPages())
                <div class="p-3 border-top">
                    {{ $members->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.registration-fees.payment.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i>Record Registration Fee Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Select Member <span class="text-danger">*</span></label>
                        <select name="user_id" id="payment_user_id" class="form-select" required>
                            <option value="">-- Choose Member --</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->member_code ?? 'No Code' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="payment_amount" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Online">Online</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Transaction / Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. TR-9823414">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reconcile Modal -->
<div class="modal fade" id="reconcileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reconcileForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2 text-warning"></i>Reconcile Registration Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Reconciling member: <strong id="reconcile_member_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Fee Status <span class="text-danger">*</span></label>
                        <select name="status" id="reconcile_status" class="form-select" required>
                            <option value="unpaid">Unpaid</option>
                            <option value="partially_paid">Partially Paid</option>
                            <option value="fully_paid">Fully Paid</option>
                            <option value="requires_verification">Requires Verification</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Required Fee Amount (₦)</label>
                        <input type="number" step="0.01" name="fee_amount" id="reconcile_fee_amount" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Total Paid Amount (₦)</label>
                        <input type="number" step="0.01" name="total_paid" id="reconcile_total_paid" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Audit Notes / Reason</label>
                        <textarea name="notes" id="reconcile_notes" class="form-control" rows="2" placeholder="Historical verification notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-semibold"><i class="bi bi-save me-1"></i> Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openPaymentModal(userId, memberName, outstanding) {
    document.getElementById('payment_user_id').value = userId;
    document.getElementById('payment_amount').value = outstanding > 0 ? outstanding : '';
    var modal = new bootstrap.Modal(document.getElementById('recordPaymentModal'));
    modal.show();
}

function openReconcileModal(userId, memberName, status, feeAmount, totalPaid, notes) {
    document.getElementById('reconcile_member_name').innerText = memberName;
    document.getElementById('reconcile_status').value = status;
    document.getElementById('reconcile_fee_amount').value = feeAmount;
    document.getElementById('reconcile_total_paid').value = totalPaid;
    document.getElementById('reconcile_notes').value = notes;
    
    var form = document.getElementById('reconcileForm');
    form.action = "{{ url('admin/registration-fees/reconcile') }}/" + userId;

    var modal = new bootstrap.Modal(document.getElementById('reconcileModal'));
    modal.show();
}
</script>
@endsection
