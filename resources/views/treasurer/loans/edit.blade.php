@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-pencil"></i> Edit Financing</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('treasurer.loans.update', $loan) }}">
    @csrf
    @method('PUT')

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Member</label>
            <input type="text" class="form-control" value="{{ $loan->user->name }} ({{ $loan->user->member_code }})" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Date Granted <span class="text-danger">*</span></label>
            <input type="date" name="date_granted" class="form-control @error('date_granted') is-invalid @enderror" value="{{ old('date_granted', $loan->date_granted?->format('Y-m-d')) }}" required>
            @error('date_granted')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label fw-bold">Principal Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="principal_amount" class="form-control @error('principal_amount') is-invalid @enderror" value="{{ old('principal_amount', $loan->principal_amount) }}" required>
            @error('principal_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Profit Rate (%) <span class="text-danger">*</span></label>
            <input type="number" step="0.1" name="profit_rate" class="form-control @error('profit_rate') is-invalid @enderror" value="{{ old('profit_rate', $loan->profit_rate) }}" required>
            @error('profit_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Repayment Term (Months) <span class="text-danger">*</span></label>
            <input type="number" name="duration_months" class="form-control @error('duration_months') is-invalid @enderror" value="{{ old('duration_months', $loan->duration_months) }}" required>
            @error('duration_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                <option value="active" {{ old('status', $loan->status) === 'active' ? 'selected' : '' }}>Active</option>
                <option value="fully_paid" {{ old('status', $loan->status) === 'fully_paid' ? 'selected' : '' }}>Fully Paid</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('treasurer.loans.show', $loan) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
    </div>
</form>
</div></div>
@endsection
