@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h4><i class="bi bi-eye"></i> Financing Details #{{ $loan->id }}</h4>
        <a href="{{ route('treasurer.loans.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Financing</a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Financing Information</h6></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Member:</strong> {{ $loan->user->name ?? 'N/A' }} ({{ $loan->user->member_code ?? '' }})</div>
                    <div class="col-md-6"><strong>Status:</strong> <span class="badge bg-{{ $loan->status === 'active' ? 'warning' : ($loan->status === 'fully_paid' ? 'success' : 'secondary') }}">{{ str_replace('_', ' ', ucfirst($loan->status)) }}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Principal:</strong> ₦{{ number_format($loan->principal_amount, 2) }}</div>
                    <div class="col-md-4"><strong>Profit Rate:</strong> {{ $loan->profit_rate }}%</div>
                    <div class="col-md-4"><strong>Total:</strong> ₦{{ number_format($loan->total_amount, 2) }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Monthly Payment:</strong> ₦{{ number_format($loan->monthly_payment, 2) }}</div>
                    <div class="col-md-4"><strong>Outstanding:</strong> <span class="text-danger">₦{{ number_format($loan->outstanding_balance, 2) }}</span></div>
                    <div class="col-md-4"><strong>Date Granted:</strong> {{ $loan->date_granted?->format('d/m/Y') }}</div>
                </div>
                <div class="mb-3">
                    <strong>Progress:</strong>
                    @php $paid = $loan->duration_months - $loan->remaining_months; $pct = $loan->duration_months > 0 ? round(($paid / $loan->duration_months) * 100) : 0; @endphp
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" style="width: {{ $pct }}%">{{ $paid }}/{{ $loan->duration_months }} months ({{ $pct }}%)</div>
                    </div>
                </div>
                @if($loan->approver)
                    <small class="text-muted">Approved by: {{ $loan->approver->name }} on {{ $loan->created_at->format('d/m/Y') }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Record Repayment</h6></div>
            <div class="card-body">
                @if($loan->status === 'active')
                <form method="POST" action="{{ route('treasurer.loans.repayment', $loan) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $loan->monthly_payment) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle"></i> Record Repayment</button>
                </form>
                @else
                    <p class="text-muted">Financing is already {{ str_replace('_', ' ', $loan->status) }}.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Repayment History</h6></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Amount</th><th>Date</th><th>Recorded By</th><th class="text-center">Actions</th></tr></thead>
            <tbody>
                @forelse($loan->repayments as $repayment)
                <tr>
                    <td>{{ $repayment->month_number }}</td>
                    <td>₦{{ number_format($repayment->amount, 2) }}</td>
                    <td>{{ $repayment->payment_date?->format('d/m/Y') }}</td>
                    <td>{{ $repayment->recorded_by ? \App\Models\User::find($repayment->recorded_by)?->name : 'System' }}</td>
                    <td class="text-center">
                        <a href="{{ route('treasurer.loans.repayments.edit', [$loan, $repayment]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('treasurer.loans.repayments.destroy', [$loan, $repayment]) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this repayment?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No repayments yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
