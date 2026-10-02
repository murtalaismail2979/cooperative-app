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
                            <option value="{{ $member->id }}" {{ old('user_id', $selectedUserId ?? '') == $member->id ? 'selected' : '' }}>
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

            <!-- Auto Profit Amount Summary Box -->
            <div class="card bg-light border-info mb-4">
                <div class="card-body py-3">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Allocated Profit Amount:</span>
                            <strong class="fs-5 text-success" id="calc-profit-amount">₦0.00</strong>
                        </div>
                        <div class="col-md-4 border-start border-end">
                            <span class="text-muted small d-block">Total Amount Payable:</span>
                            <strong class="fs-5 text-primary" id="calc-total-amount">₦0.00</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Monthly Installment:</span>
                            <strong class="fs-5 text-dark" id="calc-monthly-payment">₦0.00</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Grant Financing</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const principalInput = document.querySelector('input[name="principal_amount"]');
    const profitRateInput = document.querySelector('input[name="profit_rate"]');
    const durationInput = document.querySelector('input[name="duration_months"]');

    const profitEl = document.getElementById('calc-profit-amount');
    const totalEl = document.getElementById('calc-total-amount');
    const monthlyEl = document.getElementById('calc-monthly-payment');

    function calculateProfit() {
        const principal = parseFloat(principalInput.value) || 0;
        const profitRate = parseFloat(profitRateInput.value) || 0;
        const duration = parseInt(durationInput.value) || 1;

        const profitAmount = principal * (profitRate / 100);
        const totalAmount = principal + profitAmount;
        const monthlyPayment = duration > 0 ? (totalAmount / duration) : 0;

        profitEl.textContent = '₦' + profitAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        totalEl.textContent = '₦' + totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        monthlyEl.textContent = '₦' + monthlyPayment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    if (principalInput && profitRateInput && durationInput) {
        principalInput.addEventListener('input', calculateProfit);
        profitRateInput.addEventListener('input', calculateProfit);
        durationInput.addEventListener('input', calculateProfit);
        calculateProfit();
    }
});
</script>
@endpush
@endsection