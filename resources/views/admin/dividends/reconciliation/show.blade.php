@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-file-earmark-bar-graph"></i> Financial Year {{ $year }} Dividend & Loss Reconciliation Report</h1>
            <p class="text-muted small mb-0">Official Audit & Reconciliation Record for Financial Year {{ $year }}</p>
        </div>
        <div>
            <a href="{{ route('admin.dividends.reconciliation.index', ['year' => $year]) }}" class="btn btn-secondary btn-sm me-2">
                <i class="bi bi-arrow-left"></i> Back to Selector
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Report Summary Card -->
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold"><i class="bi bi-bank"></i> Financial Year {{ $year }} Summary</h5>
            <span class="badge bg-success">Status: {{ ucfirst($reconciliation->status) }}</span>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Total Business Profit:</span>
                            <span class="fw-bold">₦{{ number_format($reconciliation->total_business_profit, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Total Financing Profit:</span>
                            <span class="fw-bold">₦{{ number_format($reconciliation->total_financing_profit, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Total Recognized Loss:</span>
                            <span class="fw-bold text-danger">₦{{ number_format($reconciliation->total_recognized_loss, 2) }}</span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Net Sharable Profit:</span>
                            <span class="fw-bold">₦{{ number_format($reconciliation->net_sharable_profit, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Cooperative Allocation (5%):</span>
                            <span class="fw-bold">₦{{ number_format($reconciliation->cooperative_amount, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Management Allocation (5%):</span>
                            <span class="fw-bold">₦{{ number_format($reconciliation->management_amount, 2) }}</span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Adjusted Member Pool (90%):</span>
                            <span class="fw-bold text-success">₦{{ number_format($reconciliation->adjusted_member_pool, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Total Dividends Distributed:</span>
                            <span class="fw-bold">₦{{ number_format($reconciliation->total_distributed, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Total Loss Adjustment:</span>
                            <span class="fw-bold text-danger">₦{{ number_format($reconciliation->total_loss_adjustment, 2) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Level Breakdown Table -->
    <div class="card shadow mb-4">
        <div class="card-header bg-light py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-people"></i> Member Dividend Adjustments & Recovery Audit Table</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Member</th>
                            <th class="text-end">Original Entitlement</th>
                            <th class="text-end">Loss Adjustment</th>
                            <th class="text-end">Final Entitlement</th>
                            <th class="text-end">Amount Paid</th>
                            <th class="text-end">Overpayment</th>
                            <th class="text-end">Recovered</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliation->adjustments as $adj)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $adj->user->name ?? 'User #' . $adj->user_id }}</span><br>
                                    <small class="text-muted">{{ $adj->user->member_code ?? '' }}</small>
                                </td>
                                <td class="text-end">₦{{ number_format($adj->original_dividend_amount, 2) }}</td>
                                <td class="text-end text-danger">-₦{{ number_format($adj->loss_adjustment_amount, 2) }}</td>
                                <td class="text-end text-success fw-bold">₦{{ number_format($adj->final_entitlement_amount, 2) }}</td>
                                <td class="text-end">₦{{ number_format($adj->amount_already_paid, 2) }}</td>
                                <td class="text-end text-danger fw-bold">
                                    @if($adj->overpayment_amount > 0)
                                        ₦{{ number_format($adj->overpayment_amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end text-success">₦{{ number_format($adj->amount_recovered, 2) }}</td>
                                <td class="text-center">
                                    @if($adj->status === 'fully_recovered' || $adj->status === 'settled')
                                        <span class="badge bg-success">{{ strtoupper($adj->status) }}</span>
                                    @elseif($adj->status === 'partially_recovered')
                                        <span class="badge bg-warning text-dark">PARTIAL</span>
                                    @else
                                        <span class="badge bg-danger">PENDING</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($adj->overpayment_amount > 0 && $adj->outstanding_recovery > 0 && !$isClosed)
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recoveryModal{{ $adj->id }}">
                                            <i class="bi bi-cash"></i> Record Recovery
                                        </button>

                                        <!-- Modal -->
                                        <div class="modal fade" id="recoveryModal{{ $adj->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-start">
                                                    <form action="{{ route('admin.dividends.reconciliation.recovery', $adj->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-header-title text-white mb-0">Record Recovery Payment</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-3">Record recovery payment from <strong>{{ $adj->user->name }}</strong> for over-distributed dividends.</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Outstanding Recovery Amount</label>
                                                                <input type="text" class="form-control" value="₦{{ number_format($adj->outstanding_recovery, 2) }}" readonly disabled>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Payment Amount (₦) <span class="text-danger">*</span></label>
                                                                <input type="number" step="0.01" max="{{ $adj->outstanding_recovery }}" name="amount" class="form-control" value="{{ $adj->outstanding_recovery }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                                                <select name="payment_method" class="form-select" required>
                                                                    <option value="Cash">Cash</option>
                                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                                    <option value="Cheque">Cheque</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reference Number</label>
                                                                <input type="text" name="reference_number" class="form-control" placeholder="e.g. REC-LOSS-891">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Payment</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No member adjustments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
