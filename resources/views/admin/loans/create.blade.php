@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-plus-circle"></i> Grant New Financing</h4>
@endsection

@section('content')
<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.loans.store') }}">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Member <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                        <option value="">Select Member</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ old('user_id') == $member->id ? 'selected' : '' }}>
                                {{ $member->name }} ({{ $member->member_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Principal Amount (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="principal_amount" class="form-control @error('principal_amount') is-invalid @enderror" value="{{ old('principal_amount') }}" required>
                    @error('principal_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Profit Rate (%) <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" name="profit_rate" class="form-control @error('profit_rate') is-invalid @enderror" value="{{ old('profit_rate', 20) }}" required>
                    @error('profit_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Repayment Installments (Months) <span class="text-danger">*</span></label>
                    <input type="number" name="duration_months" class="form-control @error('duration_months') is-invalid @enderror" value="{{ old('duration_months', 12) }}" required>
                    @error('duration_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Date Granted <span class="text-danger">*</span></label>
                    <input type="date" name="date_granted" class="form-control @error('date_granted') is-invalid @enderror" value="{{ old('date_granted', date('Y-m-d')) }}" required>
                    @error('date_granted')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Grant Financing</button>
            </div>
        </form>
    </div>
</div>
@endsection