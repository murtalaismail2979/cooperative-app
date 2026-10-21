@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="bi bi-send-plus"></i> Request Financing</h4>
        <a href="{{ route('member.loans') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to My Financing
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <!-- Financial Standing & Pre-Qualification Metrics -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4 border-end">
                        <small class="text-muted text-uppercase fw-semibold">Total Verified Savings</small>
                        <h3 class="mb-0 text-primary fw-bold">₦{{ number_format($eligibility['total_savings'], 2) }}</h3>
                        <small class="text-muted">Calculated from cumulative contributions</small>
                    </div>
                    <div class="col-md-4 border-end">
                        <small class="text-muted text-uppercase fw-semibold">Maximum Financing Limit (3x Savings)</small>
                        <h3 class="mb-0 text-success fw-bold">₦{{ number_format($eligibility['max_eligible_amount'], 2) }}</h3>
                        <small class="text-muted">Cooperative policy cap at time of request</small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted text-uppercase fw-semibold">Standard Profit Rate & Status</small>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="mb-0 text-dark fw-bold">{{ $eligibility['default_profit_rate'] }}%</h3>
                            <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Pre-Qualified</span>
                        </div>
                        <small class="text-muted">Ethical cooperative financing rate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$eligibility['is_eligible'])
        <!-- Ineligibility Notice -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 rounded-circle bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-shield-exclamation fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Financing Application Unavailable</h5>
                            <p class="text-muted mb-2">You currently do not meet the prerequisites to submit a new financing application:</p>
                            <ul class="mb-3 text-secondary ps-3">
                                @foreach($eligibility['reasons'] as $reason)
                                    <li class="mb-1">{{ $reason }}</li>
                                @endforeach
                            </ul>

                            @if($eligibility['has_active_loan'])
                                <div class="p-3 bg-light rounded border">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Active Financing #{{ $eligibility['active_loan']->id }}</strong>
                                            <div class="small text-muted">
                                                Granted: {{ $eligibility['active_loan']->date_granted?->format('d M, Y') }} | 
                                                Outstanding: <strong class="text-danger">₦{{ number_format($eligibility['active_loan']->outstanding_balance, 2) }}</strong>
                                            </div>
                                        </div>
                                        <a href="{{ route('member.loans') }}" class="btn btn-sm btn-outline-primary">
                                            View Repayments
                                        </a>
                                    </div>
                                </div>
                            @elseif($eligibility['has_pending_request'])
                                <div class="p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Pending Request #{{ $eligibility['pending_request']->id }}</strong>
                                        <div class="small text-muted">
                                            Submitted on {{ $eligibility['pending_request']->created_at->format('d M, Y') }} for 
                                            <strong class="text-primary">₦{{ number_format($eligibility['pending_request']->amount_requested, 2) }}</strong>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('member.loans.request.cancel', $eligibility['pending_request']->id) }}" onsubmit="return confirm('Are you sure you want to cancel this pending financing request?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-circle"></i> Cancel Request
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Application Form & Calculator -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-square me-1"></i> Application Details</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('member.loans.request.store') }}" id="loanApplicationForm">
                        @csrf

                        <!-- Amount Requested -->
                        <div class="mb-3">
                            <label for="amount_requested" class="form-label fw-bold">
                                Requested Amount (₦) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold">₦</span>
                                <input type="number" 
                                       step="1000" 
                                       min="1000" 
                                       max="{{ $eligibility['max_eligible_amount'] }}" 
                                       name="amount_requested" 
                                       id="amount_requested" 
                                       class="form-control @error('amount_requested') is-invalid @enderror" 
                                       placeholder="e.g. 100000" 
                                       value="{{ old('amount_requested', min(100000, $eligibility['max_eligible_amount'])) }}" 
                                       required>
                                @error('amount_requested')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted d-block mt-1">
                                Maximum eligible based on your savings: <strong class="text-success">₦{{ number_format($eligibility['max_eligible_amount'], 2) }}</strong>
                            </small>

                            <!-- Quick Percentage Buttons -->
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                @php
                                    $max = $eligibility['max_eligible_amount'];
                                @endphp
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-pct" data-amount="{{ round($max * 0.25) }}">25% (₦{{ number_format(round($max * 0.25)) }})</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-pct" data-amount="{{ round($max * 0.50) }}">50% (₦{{ number_format(round($max * 0.50)) }})</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-pct" data-amount="{{ round($max * 0.75) }}">75% (₦{{ number_format(round($max * 0.75)) }})</button>
                                <button type="button" class="btn btn-sm btn-outline-primary quick-pct" data-amount="{{ round($max) }}">100% Max (₦{{ number_format(round($max)) }})</button>
                            </div>
                        </div>

                        <!-- Duration with Tiered Rates -->
                        <div class="mb-3">
                            <label for="duration_months" class="form-label fw-bold">
                                Repayment Duration & Profit Rate Tier <span class="text-danger">*</span>
                            </label>
                            <select name="duration_months" id="duration_months" class="form-select form-select-lg @error('duration_months') is-invalid @enderror" required>
                                <optgroup label="5% Profit Rate Tier (1 – 2 Months)">
                                    <option value="1" {{ old('duration_months') == 1 ? 'selected' : '' }}>1 Month — 5% Profit</option>
                                    <option value="2" {{ old('duration_months') == 2 ? 'selected' : '' }}>2 Months — 5% Profit</option>
                                </optgroup>
                                <optgroup label="10% Profit Rate Tier (3 – 6 Months)">
                                    <option value="3" {{ old('duration_months') == 3 ? 'selected' : '' }}>3 Months (Quarterly) — 10% Profit</option>
                                    <option value="4" {{ old('duration_months') == 4 ? 'selected' : '' }}>4 Months — 10% Profit</option>
                                    <option value="5" {{ old('duration_months') == 5 ? 'selected' : '' }}>5 Months — 10% Profit</option>
                                    <option value="6" {{ old('duration_months') == 6 ? 'selected' : '' }}>6 Months (Half-Year) — 10% Profit</option>
                                </optgroup>
                                <optgroup label="20% Profit Rate Tier (7 – 12 Months)">
                                    <option value="7" {{ old('duration_months') == 7 ? 'selected' : '' }}>7 Months — 20% Profit</option>
                                    <option value="8" {{ old('duration_months') == 8 ? 'selected' : '' }}>8 Months — 20% Profit</option>
                                    <option value="9" {{ old('duration_months') == 9 ? 'selected' : '' }}>9 Months — 20% Profit</option>
                                    <option value="10" {{ old('duration_months') == 10 ? 'selected' : '' }}>10 Months — 20% Profit</option>
                                    <option value="11" {{ old('duration_months') == 11 ? 'selected' : '' }}>11 Months — 20% Profit</option>
                                    <option value="12" {{ old('duration_months', 12) == 12 ? 'selected' : '' }}>12 Months (1 Year - Standard) — 20% Profit</option>
                                </optgroup>
                            </select>
                            @error('duration_months')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            <!-- Tier Badges Visual Guide -->
                            <div class="d-flex gap-2 mt-2 flex-wrap small">
                                <span class="badge bg-light text-dark border"><i class="bi bi-tag text-success me-1"></i> 1–2 Mos: <strong>5%</strong></span>
                                <span class="badge bg-light text-dark border"><i class="bi bi-tag text-info me-1"></i> 3–6 Mos: <strong>10%</strong></span>
                                <span class="badge bg-light text-dark border"><i class="bi bi-tag text-primary me-1"></i> 7–12 Mos: <strong>20%</strong></span>
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="mb-4">
                            <label for="purpose" class="form-label fw-bold">
                                Purpose of Financing <span class="text-danger">*</span>
                            </label>
                            <textarea name="purpose" 
                                      id="purpose" 
                                      rows="3" 
                                      class="form-control @error('purpose') is-invalid @enderror" 
                                      placeholder="State the purpose or reason for requesting financing (e.g., Business inventory purchase, asset acquisition, education fees)..." 
                                      required>{{ old('purpose') }}</textarea>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Agreement Checkbox -->
                        <div class="form-check mb-4 p-3 bg-light rounded border">
                            <input class="form-check-input @error('agreement') is-invalid @enderror" type="checkbox" name="agreement" value="1" id="agreementCheck" required {{ old('agreement') ? 'checked' : '' }}>
                            <label class="form-check-label small text-secondary" for="agreementCheck">
                                I confirm that all information provided is accurate. I understand that this request is subject to cooperative executive review and approval. If approved, I authorize the cooperative to establish a monthly repayment schedule for the duration specified.
                            </label>
                            @error('agreement')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm" id="submitBtn">
                            <i class="bi bi-send-check me-1"></i> Submit Financing Application
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Real-Time Estimator / Calculator Card -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 1rem;">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="m-0 fw-bold"><i class="bi bi-calculator me-1"></i> Real-Time Calculator</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4 text-center p-3 bg-light rounded border">
                        <small class="text-muted text-uppercase fw-semibold">Estimated Monthly Installment</small>
                        <div class="display-6 fw-bold text-success mt-1" id="dispMonthly">₦0.00</div>
                        <small class="text-muted">deducted monthly over <span id="dispDurationText">12</span> months</small>
                    </div>

                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary">Requested Principal:</span>
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
                    <div class="text-muted small mt-3">
                        <i class="bi bi-info-circle me-1"></i> Final terms including granted amount and disbursement date are confirmed upon executive committee review.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Recent Financing Requests History -->
    @if(isset($recentRequests) && $recentRequests->count() > 0)
        <div class="col-12 mt-2">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-1"></i> Recent Applications</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ref #</th>
                                    <th>Date</th>
                                    <th>Requested</th>
                                    <th>Duration</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRequests as $req)
                                    <tr>
                                        <td><strong>#{{ $req->id }}</strong></td>
                                        <td>{{ $req->created_at->format('d/m/Y') }}</td>
                                        <td class="fw-bold">₦{{ number_format($req->amount_requested, 2) }}</td>
                                        <td>{{ $req->duration_months }} Months</td>
                                        <td><span class="text-truncate d-inline-block" style="max-width: 250px;">{{ $req->purpose ?? 'N/A' }}</span></td>
                                        <td>
                                            @if($req->isPending())
                                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending Review</span>
                                            @elseif($req->isApproved())
                                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                            @elseif($req->isRejected())
                                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Declined</span>
                                            @elseif($req->isCancelled())
                                                <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i> Cancelled</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($req->isPending())
                                                <form method="POST" action="{{ route('member.loans.request.cancel', $req->id) }}" class="d-inline" onsubmit="return confirm('Cancel this financing request?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-x"></i> Cancel
                                                    </button>
                                                </form>
                                            @elseif($req->isApproved() && $req->loan_id)
                                                <a href="{{ route('member.loans') }}" class="btn btn-outline-primary btn-sm">
                                                    View Financing
                                                </a>
                                            @elseif($req->isRejected() && $req->rejection_reason)
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#reasonModal{{ $req->id }}">
                                                    View Note
                                                </button>

                                                <!-- Rejection Reason Modal -->
                                                <div class="modal fade" id="reasonModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-info-circle me-1"></i> Application Feedback #{{ $req->id }}</h6>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="mb-2 text-muted small">Cooperative Executive Decision:</div>
                                                                <div class="p-3 bg-light rounded text-dark border">
                                                                    {{ $req->rejection_reason }}
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
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

    const maxEligible = {{ (float) $eligibility['max_eligible_amount'] }};

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
