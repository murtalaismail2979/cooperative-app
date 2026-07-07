@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-plus-circle"></i> Distribute Dividends</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('admin.dividends.store') }}">
    @csrf
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Investment / Business <span class="text-danger">*</span></label>
            <select name="investment_id" id="investment_id" class="form-select @error('investment_id') is-invalid @enderror" required>
                <option value="">Select Business/Investment</option>
                @foreach($investments as $inv)
                    <option value="{{ $inv->id }}" data-profit="{{ $inv->sharable_profit }}" {{ old('investment_id') == $inv->id ? 'selected' : '' }}>
                        {{ $inv->name }} (₦{{ number_format($inv->sharable_profit, 2) }} profit, Start: {{ $inv->start_date->format('d/m/Y') }})
                    </option>
                @endforeach
            </select>
            @error('investment_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Total Dividend Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="total_dividend_amount" id="total_dividend_amount" class="form-control @error('total_dividend_amount') is-invalid @enderror" value="{{ old('total_dividend_amount') }}" required>
            @error('total_dividend_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Dividends will be distributed based on members' cumulative savings units (₦2,000 per unit) at the start month of the selected business.
    </div>
    <div class="d-flex justify-content-between">
        <a href="{{ route('admin.dividends.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Distribute</button>
    </div>
</form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const investmentSelect = document.getElementById('investment_id');
    const amountInput = document.getElementById('total_dividend_amount');

    function updateAmount() {
        const selectedOption = investmentSelect.options[investmentSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const profit = selectedOption.getAttribute('data-profit');
            amountInput.value = parseFloat(profit || 0).toFixed(2);
        } else {
            amountInput.value = '';
        }
    }

    investmentSelect.addEventListener('change', updateAmount);
    if (investmentSelect.value) {
        updateAmount();
    }
});
</script>
@endsection