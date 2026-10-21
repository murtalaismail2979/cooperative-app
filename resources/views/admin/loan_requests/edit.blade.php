@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Financing Request #{{ $loanRequest->id }}</h4>
        <a href="{{ route('admin.loan-requests.show', $loanRequest->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Review
        </a>
    </div>
@endsection

@section('content')
@if($errors->has('general'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first('general') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Applicant Financial Standing Header -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-3 border-end">
                <small class="text-muted text-uppercase fw-semibold">Applicant Member</small>
                <h5 class="mb-0 fw-bold text-dark">{{ $member->name }}</h5>
                <span class="badge bg-light text-secondary border font-monospace">{{ $member->member_code ?? 'NO-CODE' }}</span>
            </div>
            <div class="col-md-3 border-end">
                <small class="text-muted text-uppercase fw-semibold">Verified Total Savings</small>
                <h4 class="mb-0 text-primary fw-bold">₦{{ number_format($currentSavings, 2) }}</h4>
                <small class="text-muted">Current member balance</small>
            </div>
            <div class="col-md-3 border-end">
                <small class="text-muted text-uppercase fw-semibold">Maximum Borrowing Cap (3x)</small>
                <h4 class="mb-0 text-success fw-bold">₦{{ number_format($maxEligibleNow, 2) }}</h4>
                <small class="text-muted">Strict cooperative policy limit</small>
            </div>
            <div class="col-md-3">
                <small class="text-muted text-uppercase fw-semibold">Profit Rate Tiers</small>
                <div class="small mt-1">
                    <span class="badge bg-light text-dark border">1–2m: 5%</span>
                    <span class="badge bg-light text-dark border">3–6m: 10%</span>
                    <span class="badge bg-light text-dark border">7–12m: 20%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Edit Form Column -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-sliders me-1"></i> Edit Request Parameters</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.loan-requests.update', $loanRequest->id) }}" id="editLoanRequestForm">
                    @csrf
                    @method('PUT')

                    <!-- Requested Amount -->
                    <div class="mb-3">
                        <label for="amount_requested" class="form-label fw-bold">
                            Requested Principal Amount (₦) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold">₦</span>
                            <input type="number" 
                                   step="1000" 
                                   min="1000" 
                                   max="{{ $maxEligibleNow }}" 
                                   name="amount_requested" 
                                   id="amount_requested" 
                                   class="form-control @error('amount_requested') is-invalid @enderror" 
                                   value="{{ old('amount_requested', $loanRequest->amount_requested) }}" 
                                   required>
                            @error('amount_requested')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted d-block mt-1">
                            Maximum allowed based on member savings: <strong class="text-success">₦{{ number_format($maxEligibleNow, 2) }}</strong>
                        </small>

                        <!-- Quick Percentage Buttons -->
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-pct" data-amount="{{ round($maxEligibleNow * 0.25) }}">25% (₦{{ number_format(round($maxEligibleNow * 0.25)) }})</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-pct" data-amount="{{ round($maxEligibleNow * 0.50) }}">50% (₦{{ number_format(round($maxEligibleNow * 0.50)) }})</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-pct" data-amount="{{ round($maxEligibleNow * 0.75) }}">75% (₦{{ number_format(round($maxEligibleNow * 0.75)) }})</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-pct" data-amount="{{ round($maxEligibleNow) }}">100% Max (₦{{ number_format(round($maxEligibleNow)) }})</button>
                        </div>
                    </div>

                    <!-- Repayment Duration -->
                    <div class="mb-3">
                        <label for="duration_months" class="form-label fw-bold">
                            Repayment Duration & Tiered Profit Rate <span class="text-danger">*</span>
                        </label>
                        <select name="duration_months" id="duration_months" class="form-select form-select-lg @error('duration_months') is-invalid @enderror" required>
                            <optgroup label="5% Profit Rate Tier (1 – 2 Months)">
                                <option value="1" {{ old('duration_months', $loanRequest->duration_months) == 1 ? 'selected' : '' }}>1 Month — 5% Profit</option>
                                <option value="2" {{ old('duration_months', $loanRequest->duration_months) == 2 ? 'selected' : '' }}>2 Months — 5% Profit</option>
                            </optgroup>
                            <optgroup label="10% Profit Rate Tier (3 – 6 Months)">
                                <option value="3" {{ old('duration_months', $loanRequest->duration_months) == 3 ? 'selected' : '' }}>3 Months — 10% Profit</option>
                                <option value="4" {{ old('duration_months', $loanRequest->duration_months) == 4 ? 'selected' : '' }}>4 Months — 10% Profit</option>
                                <option value="5" {{ old('duration_months', $loanRequest->duration_months) == 5 ? 'selected' : '' }}>5 Months — 10% Profit</option>
                                <option value="6" {{ old('duration_months', $loanRequest->duration_months) == 6 ? 'selected' : '' }}>6 Months (Half-Year) — 10% Profit</option>
                            </optgroup>
                            <optgroup label="20% Profit Rate Tier (7 – 12 Months)">
                                <option value="7" {{ old('duration_months', $loanRequest->duration_months) == 7 ? 'selected' : '' }}>7 Months — 20% Profit</option>
                                <option value="8" {{ old('duration_months', $loanRequest->duration_months) == 8 ? 'selected' : '' }}>8 Months — 20% Profit</option>
                                <option value="9" {{ old('duration_months', $loanRequest->duration_months) == 9 ? 'selected' : '' }}>9 Months — 20% Profit</option>
                                <option value="10" {{ old('duration_months', $loanRequest->duration_months) == 10 ? 'selected' : '' }}>10 Months — 20% Profit</option>
                                <option value="11" {{ old('duration_months', $loanRequest->duration_months) == 11 ? 'selected' : '' }}>11 Months — 20% Profit</option>
                                <option value="12" {{ old('duration_months', $loanRequest->duration_months) == 12 ? 'selected' : '' }}>12 Months (1 Year - Standard) — 20% Profit</option>
                            </optgroup>
                        </select>
                        @error('duration_months')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Purpose -->
                    <div class="mb-3">
                        <label for="purpose" class="form-label fw-bold">Purpose of Financing</label>
                        <textarea name="purpose" id="purpose" rows="2" class="form-control @error('purpose') is-invalid @enderror">{{ old('purpose', $loanRequest->purpose) }}</textarea>
                        @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Admin Notes -->
                    <div class="mb-4">
                        <label for="admin_notes" class="form-label fw-bold">Admin / Adjustment Notes</label>
                        <textarea name="admin_notes" id="admin_notes" rows="2" class="form-control @error('admin_notes') is-invalid @enderror" placeholder="State reason for adjusting requested amount or duration (e.g. Adjusted upon member's request)...">{{ old('admin_notes', $loanRequest->admin_notes) }}</textarea>
                        @error('admin_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-check-circle me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.loan-requests.show', $loanRequest->id) }}" class="btn btn-outline-secondary btn-lg">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Real-time Calculator Column -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 1rem;">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="m-0 fw-bold"><i class="bi bi-calculator me-1"></i> Updated Repayment Summary</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4 text-center p-3 bg-light rounded border">
                    <small class="text-muted text-uppercase fw-semibold">Estimated Monthly Installment</small>
                    <div class="display-6 fw-bold text-success mt-1" id="dispMonthly">₦0.00</div>
                    <small class="text-muted">over <span id="dispDurationText">12</span> months</small>
                </div>

                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-secondary">Updated Principal:</span>
                        <strong class="text-dark" id="dispPrincipal">₦0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-secondary">Applicable Profit Rate:</span>
                        <strong class="text-dark" id="dispProfitRate">20%</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-secondary">Total Financing Profit:</span>
                        <strong class="text-primary" id="dispProfit">₦0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-light px-3 rounded mt-2">
                        <span class="fw-bold text-dark">Total Repayable Amount:</span>
                        <strong class="fw-bold text-dark fs-5" id="dispTotal">₦0.00</strong>
                    </li>
                </ul>

                <!-- Capacity Utilization Bar -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Borrowing Capacity Used</span>
                        <span id="dispCapacityPercent">0%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" id="capacityProgressBar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.getElementById('amount_requested');
    const durationSelect = document.getElementById('duration_months');
    const dispPrincipal = document.getElementById('dispPrincipal');
    const dispProfitRate = document.getElementById('dispProfitRate');
    const dispProfit = document.getElementById('dispProfit');
    const dispTotal = document.getElementById('dispTotal');
    const dispMonthly = document.getElementById('dispMonthly');
    const dispDurationText = document.getElementById('dispDurationText');
    const dispCapacityPercent = document.getElementById('dispCapacityPercent');
    const capacityProgressBar = document.getElementById('capacityProgressBar');
    const quickButtons = document.querySelectorAll('.quick-pct');

    const maxEligible = {{ (float) $maxEligibleNow }};

    function getProfitRate(months) {
        if (months <= 2) return 5;
        if (months <= 6) return 10;
        return 20;
    }

    function formatNaira(num) {
        return '₦' + Number(num).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calculate() {
        if (!amountInput) return;
        let amount = parseFloat(amountInput.value) || 0;
        let duration = parseInt(durationSelect.value) || 12;

        if (amount < 0) amount = 0;

        const rate = getProfitRate(duration);
        const profit = amount * (rate / 100);
        const total = amount + profit;
        const monthly = duration > 0 ? (total / duration) : 0;

        dispPrincipal.textContent = formatNaira(amount);
        if (dispProfitRate) dispProfitRate.textContent = rate + '%';
        dispProfit.textContent = formatNaira(profit);
        dispTotal.textContent = formatNaira(total);
        dispMonthly.textContent = formatNaira(monthly);
        if (dispDurationText) dispDurationText.textContent = duration;

        if (maxEligible > 0) {
            let pct = Math.min(100, Math.round((amount / maxEligible) * 100));
            dispCapacityPercent.textContent = pct + '%';
            capacityProgressBar.style.width = pct + '%';

            if (pct > 90) {
                capacityProgressBar.className = 'progress-bar bg-warning';
            } else {
                capacityProgressBar.className = 'progress-bar bg-success';
            }
        }
    }

    if (amountInput) {
        amountInput.addEventListener('input', calculate);
    }
    if (durationSelect) {
        durationSelect.addEventListener('change', calculate);
    }

    quickButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const val = this.getAttribute('data-amount');
            if (amountInput) {
                amountInput.value = val;
                calculate();
            }
        });
    });

    calculate();
});
</script>
@endpush
