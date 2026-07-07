@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-pencil"></i> Edit Repayment</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('admin.loans.repayments.update', [$loan, $repayment]) }}">
    @csrf
    @method('PUT')

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Member</label>
            <input type="text" class="form-control" value="{{ $loan->user->name ?? 'N/A' }} ({{ $loan->user->member_code ?? '' }})" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Repayment Month Number</label>
            <input type="text" class="form-control" value="Repayment #{{ $repayment->month_number }}" disabled>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $repayment->amount) }}" required>
            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', $repayment->payment_date?->format('Y-m-d')) }}" required>
            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('admin.loans.show', $loan) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
    </div>
</form>
</div></div>
@endsection
