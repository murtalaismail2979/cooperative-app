@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-plus-circle"></i> New Investment</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('admin.investments.store') }}">
    @csrf
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Investment Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                <option value="">Select Type</option>
                @foreach($investmentTypes as $type)
                    <option value="{{ $type->slug }}" {{ old('type') == $type->slug ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Capital Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="capital_amount" class="form-control @error('capital_amount') is-invalid @enderror" value="{{ old('capital_amount') }}" required>
            @error('capital_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" id="quantity_wrapper" style="display: none;">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.01" name="quantity" id="quantity_input" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" placeholder="e.g. 100">
            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}" required>
            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="d-flex justify-content-between">
        <a href="{{ route('admin.investments.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Create Investment</button>
    </div>
</form>
</div></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const quantityWrapper = document.getElementById('quantity_wrapper');

    function toggleQuantityField() {
        if (!typeSelect || !quantityWrapper) return;
        const val = (typeSelect.value || '').toLowerCase();
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const text = selectedOption ? selectedOption.text.toLowerCase() : '';

        const isTargetType = val === 'buying_selling_goods' || val === 'agriculture' ||
                             text.includes('buying') || text.includes('agriculture') || text.includes('goods');

        if (isTargetType) {
            quantityWrapper.style.display = 'block';
        } else {
            quantityWrapper.style.display = 'none';
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleQuantityField);
        toggleQuantityField();
    }
});
</script>
@endpush
@endsection