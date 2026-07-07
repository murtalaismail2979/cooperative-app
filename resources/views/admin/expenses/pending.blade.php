@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-clock"></i> Pending Expense Approvals</h4>@endsection
@section('content')
<div class="card shadow mb-4">
    <div class="card-header"><span>Awaiting Approval</span></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Category</th><th>Description</th><th>Investment</th><th>Amount</th><th>Date</th><th>Requested By</th><th>Action</th></tr></thead>
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
                <td class="fw-bold">₦{{ number_format($e->amount,2) }}</td>
                <td>{{ $e->expense_date?->format('d/m/Y') }}</td>
                <td>{{ $e->requester->name ?? 'N/A' }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.expenses.approve', $e) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-success" onclick="return confirm('Approve this expense?')"><i class="bi bi-check"></i></button>
                    </form>
                    <form method="POST" action="{{ route('admin.expenses.reject', $e) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Reject this expense?')"><i class="bi bi-x"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center">No pending expenses</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $expenses->links() }}
    </div>
</div>
@endsection