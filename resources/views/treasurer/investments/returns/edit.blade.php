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
            <small class="text-muted d-block mb-1">(Positive for Gain/Profit, negative for Loss e.g. -5000)</small>
            <div class="input-group">
                <input type="number" step="0.01" name="amount" id="edit_amount_input" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $return->amount) }}" required>
                <button type="button" class="btn btn-outline-danger" id="toggleEditLossBtn" title="Toggle negative amount for loss"><i class="bi bi-dash-circle me-1"></i> Loss (-)</button>
            </div>
            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleEditLossBtn');
    const input = document.getElementById('edit_amount_input');
    if (toggleBtn && input) {
        toggleBtn.addEventListener('click', function() {
            let val = input.value.trim();
            if (val.startsWith('-')) {
                input.value = val.substring(1);
            } else if (val !== '') {
                input.value = '-' + val;
            } else {
                input.value = '-';
            }
        });
    }
});
</script>
@endpush
@endsection
