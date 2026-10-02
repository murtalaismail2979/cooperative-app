@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-cart"></i> My Expense Requests</h4>@endsection
@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All My Expense Requests</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.batch-upload.index', ['type' => 'expenses']) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-cloud-arrow-up me-1"></i> Batch Upload Expenses
            </a>
            <a href="{{ route('treasurer.expenses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Request</a>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Category</th><th>Description</th><th>Investment</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($expenses as $e)
            <tr>
                <td>{{ $e->id }}</td>
                <td><span class="badge bg-info">{{ ucfirst($e->category) }}</span></td>
                <td>{{ $e->description }}</td>
                <td>
                    @if($e->investment)
                        <span class="fw-bold text-primary">{{ $e->investment->name }}</span>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
                <td>₦{{ number_format($e->amount,2) }}</td>
                <td>{{ $e->expense_date?->format('d/m/Y') }}</td>
                <td>
                    @if($e->status === 'pending') <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($e->status === 'approved') <span class="badge bg-success">Approved</span>
                    @else <span class="badge bg-danger">Rejected</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">No expense requests</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $expenses->links() }}
    </div>
</div>
@endsection