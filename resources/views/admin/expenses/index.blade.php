@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-cart"></i> Expense Management</h4>@endsection
@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Record Expense</h6>
                <a href="{{ route('admin.batch-upload.index', ['type' => 'expenses']) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-cloud-arrow-up"></i> Batch Upload Expenses
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.expenses.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">Select Category</option>
                            <option value="administrative" {{ old('category') == 'administrative' ? 'selected' : '' }}>Administrative</option>
                            <option value="operational" {{ old('category') == 'operational' ? 'selected' : '' }}>Operational</option>
                            <option value="utilities" {{ old('category') == 'utilities' ? 'selected' : '' }}>Utilities</option>
                            <option value="business" {{ old('category') == 'business' ? 'selected' : '' }}>Business</option>
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                        @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Investment (Optional)</label>
                        <select name="investment_id" class="form-select @error('investment_id') is-invalid @enderror">
                            <option value="">N/A (General Expense)</option>
                            @foreach($investments as $inv)
                                <option value="{{ $inv->id }}" {{ old('investment_id') == $inv->id ? 'selected' : '' }}>
                                    {{ $inv->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('investment_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" required>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Record Expense</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Expenses</span>
                <a href="{{ route('admin.expenses.pending') }}" class="btn btn-warning btn-sm"><i class="bi bi-clock"></i> Pending Approvals</a>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead><tr><th>#</th><th>Category</th><th>Description</th><th>Investment</th><th>Amount</th><th>Date</th><th>Requested By</th><th>Status</th></tr></thead>
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
                        <td>{{ $e->requester->name ?? 'N/A' }}</td>
                        <td>
                            @if($e->status === 'pending') <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($e->status === 'approved') <span class="badge bg-success">Approved</span>
                            @else <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No expenses</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection