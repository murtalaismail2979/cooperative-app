@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-pencil"></i> Edit Running Charge</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('admin.running-charges.update', $runningCharge) }}">
    @csrf
    @method('PUT')

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Member</label>
            <input type="text" class="form-control" value="{{ $runningCharge->user->name }} ({{ $runningCharge->user->member_code }})" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Month <span class="text-danger">*</span></label>
            <input type="month" name="month" class="form-control @error('month') is-invalid @enderror" value="{{ old('month', $runningCharge->month?->format('Y-m')) }}" required>
            @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $runningCharge->amount) }}" required>
            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', $runningCharge->payment_date?->format('Y-m-d')) }}" required>
            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.running-charges.history') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <div>
            <button type="button" class="btn btn-outline-danger me-2" onclick="if(confirm('Are you sure you want to delete this running charge record?')) document.getElementById('deleteChargeForm').submit();">
                <i class="bi bi-trash"></i> Delete Charge
            </button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
        </div>
    </div>
</form>

<form id="deleteChargeForm" method="POST" action="{{ route('admin.running-charges.destroy', $runningCharge) }}" class="d-none">
    @csrf
    @method('DELETE')
</form>
</div></div>
@endsection
