@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-plus-circle"></i> New Expense Request</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('treasurer.expenses.store') }}">
    @csrf
    <div class="row mb-3">
        <div class="col-md-6">
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
        <div class="col-md-6">
            <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Expense Date <span class="text-danger">*</span></label>
            <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', date('Y-m-d')) }}" required>
            @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
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
    </div>
    <div class="mb-3">
        <label class="form-label">Description <span class="text-danger">*</span></label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" required>{{ old('description') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="alert alert-warning">
        <i class="bi bi-info-circle"></i> This expense will require admin approval.
    </div>
    <div class="d-flex justify-content-between">
        <a href="{{ route('treasurer.expenses.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit for Approval</button>
    </div>
</form>
</div></div>
@endsection