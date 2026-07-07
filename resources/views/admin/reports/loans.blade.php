@extends('layouts.app')
@section('page-title')
<div class="d-flex justify-content-between align-items-center w-100">
    <h4 class="mb-0"><i class="bi bi-file-earmark-bar-graph"></i> Financing Report</h4>
    <a href="{{ route('admin.reports.all.export') }}" class="btn btn-sm btn-success shadow-sm"><i class="bi bi-file-earmark-zip"></i> Download All Reports (ZIP/Excel)</a>
</div>
@endsection
@section('content')
<div class="row mb-4">
    <div class="col-md-4"><div class="card bg-warning text-white shadow"><div class="card-body"><strong>Total Principal:</strong> ₦{{ number_format($totalPrincipal,2) }}</div></div></div>
    <div class="col-md-4"><div class="card bg-info text-white shadow"><div class="card-body"><strong>Total Profit:</strong> ₦{{ number_format($totalProfit,2) }}</div></div></div>
    <div class="col-md-4"><div class="card bg-danger text-white shadow"><div class="card-body"><strong>Outstanding:</strong> ₦{{ number_format($totalOutstanding,2) }}</div></div></div>
</div>
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary">Financing Details</h6>
        <a href="{{ route('admin.reports.loans.export') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Member</th><th>Principal</th><th>Total</th><th>Outstanding</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($loans as $l)
            <tr><td>{{ $l->id }}</td><td>{{ $l->user->name ?? 'N/A' }}</td><td>₦{{ number_format($l->principal_amount,2) }}</td><td>₦{{ number_format($l->total_amount,2) }}</td><td>₦{{ number_format($l->outstanding_balance,2) }}</td><td><span class="badge bg-{{ $l->status === 'active' ? 'warning' : ($l->status === 'fully_paid' ? 'success' : 'secondary') }}">{{ $l->status }}</span></td></tr>
            @empty
            <tr><td colspan="6" class="text-center">No financing records</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $loans->links() }}
    </div>
</div>
@endsection