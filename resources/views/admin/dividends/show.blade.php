@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-eye"></i> Dividend Distribution: {{ $dividend->investment->name ?? 'Year ' . $dividend->year }}</h4>@endsection
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Distribution Details</h6></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Business:</strong> {{ $dividend->investment->name ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Start Date:</strong> {{ $dividend->investment?->start_date?->format('d/m/Y') ?? 'N/A' }}</div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Original Sharable Profit:</strong> ₦{{ number_format($dividend->original_sharable_profit, 2) }}</div>
                    <div class="col-md-4"><strong>Cooperative Amount (5%):</strong> ₦{{ number_format($dividend->cooperative_amount, 2) }}</div>
                    <div class="col-md-4"><strong>Management Amount (5%):</strong> ₦{{ number_format($dividend->management_amount, 2) }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Total Deduction (10%):</strong> ₦{{ number_format((float) $dividend->cooperative_amount + (float) $dividend->management_amount, 2) }}</div>
                    <div class="col-md-4"><strong>Net Member Pool (90%):</strong> ₦{{ number_format($dividend->member_distribution_pool, 2) }}</div>
                    <div class="col-md-4"><strong>Unit Value:</strong> ₦{{ number_format($dividend->unit_value, 2) }} (on {{ number_format($dividend->total_units) }} units)</div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Distributed:</strong> {{ $dividend->distributed_at?->format('d/m/Y H:i') }}</div>
                    <div class="col-md-6"><strong>By:</strong> {{ $dividend->distributor->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Actions</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.dividends.pay', $dividend) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Mark all payouts as paid?')">
                        <i class="bi bi-check-all"></i> Mark All as Paid
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.dividends.destroy', $dividend) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete this dividend distribution? This will delete all payout records and allow you to re-distribute dividends for this business.')">
                        <i class="bi bi-trash"></i> Delete Distribution
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 fw-bold text-primary">Member Payouts</h6></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Member</th><th>Units</th><th>Amount</th><th>Status</th><th>Paid Date</th></tr></thead>
            <tbody>
            @forelse($dividend->payouts as $p)
            <tr>
                <td>{{ $p->user->name ?? 'N/A' }} ({{ $p->user->member_code ?? '' }})</td>
                <td>{{ $p->units }}</td>
                <td>₦{{ number_format($p->amount,2) }}</td>
                <td>{!! $p->paid ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning text-dark">Pending</span>' !!}</td>
                <td>{{ $p->paid_date ? \Carbon\Carbon::parse($p->paid_date)->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">No payouts</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection