@extends('layouts.app')

@section('title', 'My Registration Fee')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-card-checklist me-2 text-primary"></i>Member Registration Fee</h2>
            <p class="text-muted mb-0">Overview of your mandatory cooperative member registration fee status and payment history.</p>
        </div>
    </div>

    <!-- Overview Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <i class="bi bi-cash-stack icon"></i>
                    <div class="stat-label">Registration Fee</div>
                    <div class="stat-value">₦{{ number_format($fee->fee_amount, 2) }}</div>
                    <small class="opacity-75">Required Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <i class="bi bi-check-circle icon"></i>
                    <div class="stat-label">Amount Paid</div>
                    <div class="stat-value">₦{{ number_format($fee->total_paid, 2) }}</div>
                    <small class="opacity-75">Total Received</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <i class="bi bi-exclamation-circle icon"></i>
                    <div class="stat-label">Outstanding Balance</div>
                    <div class="stat-value">₦{{ number_format($fee->outstanding_balance, 2) }}</div>
                    <small class="opacity-75">Balance Due</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <i class="bi bi-shield-check icon"></i>
                    <div class="stat-label">Payment Status</div>
                    <div class="stat-value text-capitalize">
                        @if($fee->status === 'fully_paid')
                            Fully Paid
                        @elseif($fee->status === 'partially_paid')
                            Partially Paid
                        @elseif($fee->status === 'requires_verification')
                            Verifying
                        @else
                            Unpaid
                        @endif
                    </div>
                    <small class="opacity-75">Status Breakdown</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Banner -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    @if($fee->status === 'fully_paid')
                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                    @elseif($fee->status === 'partially_paid')
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                    @elseif($fee->status === 'requires_verification')
                        <i class="bi bi-info-circle-fill text-info fs-1"></i>
                    @else
                        <i class="bi bi-x-circle-fill text-danger fs-1"></i>
                    @endif
                </div>
                <div>
                    <h5 class="fw-bold mb-1">
                        Status: 
                        @if($fee->status === 'fully_paid')
                            <span class="badge bg-success">Fully Paid</span>
                        @elseif($fee->status === 'partially_paid')
                            <span class="badge bg-warning text-dark">Partially Paid</span>
                        @elseif($fee->status === 'requires_verification')
                            <span class="badge bg-info text-dark">Requires Verification</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </h5>
                    <p class="mb-0 text-muted">
                        @if($fee->status === 'fully_paid')
                            Thank you! Your registration fee of ₦{{ number_format($fee->fee_amount, 2) }} is fully settled.
                        @elseif($fee->status === 'partially_paid')
                            You have paid ₦{{ number_format($fee->total_paid, 2) }} of ₦{{ number_format($fee->fee_amount, 2) }}. Outstanding balance: ₦{{ number_format($fee->outstanding_balance, 2) }}.
                        @elseif($fee->status === 'requires_verification')
                            Your registration fee record is currently pending administrative verification.
                        @else
                            Your registration fee of ₦{{ number_format($fee->fee_amount, 2) }} is currently unpaid. Please kindly make payment through cooperative administration.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="card">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Payment History & Receipts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt No.</th>
                            <th>Date</th>
                            <th>Amount Paid</th>
                            <th>Payment Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fee->payments as $payment)
                            <tr>
                                <td class="fw-bold">{{ $payment->receipt_number }}</td>
                                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                <td class="fw-bold text-success">₦{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>{{ $payment->reference_number ?? '-' }}</td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="badge bg-success">Successful</span>
                                    @else
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($payment->status === 'completed')
                                        <a href="{{ route('member.registration-fee.receipt', $payment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-printer me-1"></i> Print Receipt
                                        </a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No registration fee payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
