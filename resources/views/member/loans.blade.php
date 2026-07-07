@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-cash-stack"></i> My Financing</h4>
@endsection

@section('content')
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