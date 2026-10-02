@extends('layouts.app')
@section('page-title')
<div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
    <h4><i class="bi bi-eye"></i> Investment: {{ $investment->name }}</h4>
    <a href="{{ route('treasurer.investments.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Investments</a>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Details</h6></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-{{ $investment->quantity !== null ? '4' : '6' }}"><strong>Name:</strong> {{ $investment->name }}</div>
                    <div class="col-md-{{ $investment->quantity !== null ? '4' : '6' }}"><strong>Type:</strong> {{ $investment->investmentType?->name ?? str_replace('_',' ',ucfirst($investment->type)) }}</div>
                    @if($investment->quantity !== null)
                    <div class="col-md-4"><strong>Quantity:</strong> {{ number_format($investment->quantity, 2) }}</div>
                    @endif
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Capital:</strong> ₦{{ number_format($investment->capital_amount, 2) }}</div>
                    <div class="col-md-3"><strong>Returns:</strong> ₦{{ number_format($investment->total_returns, 2) }}</div>
                    <div class="col-md-3"><strong>Expenses Incurred:</strong> ₦{{ number_format($investment->approved_expenses, 2) }}</div>
                    <div class="col-md-3"><strong>Sharable Profit:</strong> <span class="fw-bold text-{{ $investment->sharable_profit >= 0 ? 'success' : 'danger' }}">₦{{ number_format($investment->sharable_profit, 2) }}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>ROI:</strong> <span class="badge bg-{{ $investment->roi >= 0 ? 'success' : 'danger' }}">{{ $investment->roi }}%</span></div>
                    <div class="col-md-6"><strong>Start / End Date:</strong> {{ $investment->start_date?->format('d/m/Y') }} - {{ $investment->end_date?->format('d/m/Y') ?? 'N/A' }}</div>
                    <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-{{ $investment->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($investment->status) }}</span></div>
                </div>
                @if($investment->description)
                <div class="mb-3"><strong>Description:</strong><br>{{ $investment->description }}</div>
                @endif
                @if($investment->creator)<small class="text-muted">Created by: {{ $investment->creator->name }} on {{ $investment->created_at->format('d/m/Y') }}</small>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Record Return / Loss</h6></div>
            <div class="card-body">
                @if($investment->status === 'completed')
                <div class="alert alert-warning py-2 mb-3 small">
                    <i class="bi bi-exclamation-triangle"></i> This business/financing run is completed/closed, but you can still capture additional returns or losses.
                </div>
                @endif
                <form method="POST" action="{{ route('treasurer.investments.return', $investment) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (₦) <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-1">(Enter positive for Gain/Profit, negative for Loss e.g. -5000)</small>
                        <div class="input-group">
                            <input type="number" step="0.01" name="amount" id="show_amount_input" class="form-control" required placeholder="0.00 or -5000.00">
                            <button type="button" class="btn btn-outline-danger" id="toggleShowLossBtn" title="Toggle negative amount for loss"><i class="bi bi-dash-circle me-1"></i> Loss (-)</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Profit / Loss details">
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> Save Return / Loss</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 fw-bold text-primary">Return & Loss History</h6></div>
    <div class="card-body">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Recorded By</th>
                    @if($investment->status === 'active')
                    <th class="text-center">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse($investment->returns as $i => $ret)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>
                    @if($ret->amount < 0)
                        <span class="text-danger fw-bold">₦{{ number_format($ret->amount,2) }} <span class="badge bg-danger ms-1">Loss</span></span>
                    @else
                        <span class="text-success fw-bold">₦{{ number_format($ret->amount,2) }}</span>
                    @endif
                </td>
                <td>{{ $ret->return_date?->format('d/m/Y') }}</td>
                <td>{{ $ret->description ?? 'N/A' }}</td>
                <td>{{ $ret->recorder->name ?? 'N/A' }}</td>
                @if($investment->status === 'active')
                <td class="text-center">
                    <a href="{{ route('treasurer.investments.returns.edit', [$investment, $ret]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <form method="POST" action="{{ route('treasurer.investments.destroy_return', [$investment, $ret]) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this return?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                </td>
                @endif
            </tr>
            @empty
            <tr><td colspan="{{ $investment->status === 'active' ? 6 : 5 }}" class="text-center">No returns recorded</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 fw-bold text-primary">Expenses History</h6></div>
    <div class="card-body">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Requested By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($investment->expenses as $i => $exp)
            <tr>
                <td>{{ $i+1 }}</td>
                <td><span class="badge bg-info">{{ ucfirst($exp->category) }}</span></td>
                <td>{{ $exp->description }}</td>
                <td class="fw-bold">₦{{ number_format($exp->amount,2) }}</td>
                <td>{{ $exp->expense_date?->format('d/m/Y') }}</td>
                <td>{{ $exp->requester->name ?? 'N/A' }}</td>
                <td>
                    @if($exp->status === 'pending') <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($exp->status === 'approved') <span class="badge bg-success">Approved</span>
                    @else <span class="badge bg-danger">Rejected</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">No expenses recorded for this investment</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleShowLossBtn');
    const input = document.getElementById('show_amount_input');
    if (toggleBtn && input) {
        toggleBtn.addEventListener('click', function() {
            let val = input.value.trim();
            if (val.startsWith('-')) {
                input.value = val.substring(1);
            } else if (val !== '') {
                input.value = '-' + val;
            } else {
                input.value = '-';
            }
        });
    }
});
</script>
@endpush
@endsection
