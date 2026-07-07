@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-pencil"></i> Edit Investment Return</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('treasurer.investments.returns.update', [$investment, $return]) }}">
    @csrf
    @method('PUT')

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Investment</label>
            <input type="text" class="form-control" value="{{ $investment->name }}" disabled>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $return->amount) }}" required>
            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Return Date <span class="text-danger">*</span></label>
            <input type="date" name="return_date" class="form-control @error('return_date') is-invalid @enderror" value="{{ old('return_date', $return->return_date?->format('Y-m-d')) }}" required>
            @error('return_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold">Description</label>
        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $return->description) }}">
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('treasurer.investments.show', $investment) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
    </div>
</form>
</div></div>
@endsection
