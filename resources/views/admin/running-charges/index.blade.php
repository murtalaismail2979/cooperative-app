@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-receipt"></i> Running Charges</h4>@endsection
@section('content')
<div class="row">
    <div class="col-lg-4" style="min-width: 0;">
        <div class="card shadow mb-4" style="overflow: hidden;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Record Charge</h6>
                <a href="{{ route('admin.batch-upload.index', ['type' => 'running_charges']) }}" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Batch Upload Charges
                </a>
            </div>
            <div class="card-body" style="overflow: hidden;">
                <form method="POST" action="{{ route('admin.running-charges.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Member</label>
                        <select name="user_id" class="form-select w-100" style="max-width: 100%;" required>
                            <option value="">Select</option>
                            @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->member_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <input type="month" name="month" id="monthInput" class="form-control w-100" style="max-width: 100%;" value="{{ date('Y-m') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (₦)</label>
                        <div class="input-group" style="max-width: 100%; min-width: 0;">
                            <div id="selectAmountWrapper" class="flex-grow-1" style="min-width: 0;">
                                <select name="amount" id="amountSelect" class="form-select rounded-end-0 w-100" style="max-width: 100%;" required>
                                    @foreach($chargeRates as $rate)
                                        <option value="{{ $rate->amount }}" data-start="{{ $rate->start_year }}" data-end="{{ $rate->end_year }}" data-rate-id="{{ $rate->id }}">
                                            ₦{{ number_format($rate->amount, 0) }} ({{ $rate->start_year == $rate->end_year ? $rate->start_year : ($rate->end_year >= 2099 ? $rate->start_year . ' to date' : $rate->start_year . ' - ' . $rate->end_year) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="customAmountWrapper" class="flex-grow-1 d-none" style="min-width: 0;">
                                <input type="number" step="0.01" min="0" id="customAmountInput" class="form-control rounded-end-0 w-100" style="max-width: 100%;" placeholder="Enter custom amount...">
                            </div>
                            <button type="button" id="toggleCustomAmountBtn" class="btn btn-outline-primary text-nowrap" title="Edit amount for this entry or year interval">
                                <i class="bi bi-pencil-square"></i> <span id="toggleText">Edit Amount</span>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="form-text text-muted small">
                                Click <strong class="text-primary">Edit Amount</strong> to type any custom amount for this charge.
                            </span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-warning fw-semibold ms-2" data-bs-toggle="modal" data-bs-target="#editYearIntervalModal" id="quickEditIntervalBtn">
                                <i class="bi bi-sliders"></i> Edit Interval Rate
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Paid</label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                        @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Record Charge</button>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-calendar-check"></i> Monthly Summary (Recent)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr class="table-light text-primary">
                                <th>Month</th>
                                <th class="text-end">Total Charges</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlyCharges as $mc)
                                <tr>
                                    <td><strong>{{ date('F Y', mktime(0, 0, 0, (int)$mc->month_num, 1, (int)$mc->year)) }}</strong></td>
                                    <td class="text-end text-success fw-bold">₦{{ number_format($mc->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No summaries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8" style="min-width: 0;">
        <div class="card shadow mb-4" style="overflow: hidden;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-bold text-primary"><i class="bi bi-receipt"></i> Recent Charges</span>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-primary fs-6">Total: ₦{{ number_format($overallTotal, 2) }}</span>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editYearIntervalModal">
                        <i class="bi bi-sliders"></i> Edit Year Interval Rates
                    </button>
                    <a href="{{ route('admin.running-charges.history') }}" class="btn btn-info btn-sm"><i class="bi bi-clock-history"></i> Full History</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light text-primary">
                            <tr>
                                <th>Member</th>
                                <th>Month</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Date Paid</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($charges as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->user->name ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($c->month)->format('M Y') }}</td>
                            <td class="text-end fw-semibold">₦{{ number_format($c->amount, 2) }}</td>
                            <td class="text-center text-muted">{{ $c->payment_date ? \Carbon\Carbon::parse($c->payment_date)->format('d/m/Y') : 'N/A' }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.running-charges.edit', $c) }}" class="btn btn-sm btn-outline-primary" title="Correct running charge">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('admin.running-charges.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this running charge?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete running charge">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $charges->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Charges for Year Interval -->
<div class="modal fade" id="editYearIntervalModal" tabindex="-1" aria-labelledby="editYearIntervalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.running-charges.update-interval') }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editYearIntervalModalLabel"><i class="bi bi-sliders me-2"></i>Edit Charges for Year Interval</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Select an existing interval or specify custom years to update the charge rate for that period (e.g. change 2022 - 2023 rate from 300 to a new amount).</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Preset Year Interval</label>
                        <select id="presetIntervalSelect" class="form-select">
                            <option value="">-- Choose Interval to Pre-fill --</option>
                            @foreach($chargeRates as $rate)
                                <option value="{{ $rate->id }}" data-start="{{ $rate->start_year }}" data-end="{{ $rate->end_year >= 2099 ? date('Y') : $rate->end_year }}" data-amount="{{ $rate->amount }}">
                                    {{ $rate->start_year }} - {{ $rate->end_year >= 2099 ? 'to date (' . date('Y') . ')' : $rate->end_year }} (Current Rate: ₦{{ number_format($rate->amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Start Year <span class="text-danger">*</span></label>
                            <input type="number" name="start_year" id="modalStartYear" class="form-control" min="2000" max="2100" value="2022" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">End Year <span class="text-danger">*</span></label>
                            <input type="number" name="end_year" id="modalEndYear" class="form-control" min="2000" max="2100" value="2023" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">New Charge Amount (₦) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="amount" id="modalAmount" class="form-control" placeholder="e.g. 400.00" value="300.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Update Interval Charges</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthInput = document.getElementById('monthInput');
    const amountSelect = document.getElementById('amountSelect');
    const toggleCustomBtn = document.getElementById('toggleCustomAmountBtn');
    const toggleText = document.getElementById('toggleText');
    const selectAmountWrapper = document.getElementById('selectAmountWrapper');
    const customAmountWrapper = document.getElementById('customAmountWrapper');
    const customAmountInput = document.getElementById('customAmountInput');
    const presetIntervalSelect = document.getElementById('presetIntervalSelect');
    const modalStartYear = document.getElementById('modalStartYear');
    const modalEndYear = document.getElementById('modalEndYear');
    const modalAmount = document.getElementById('modalAmount');
    
    let isCustom = false;

    function updateAmount() {
        if (!monthInput || !monthInput.value) return;
        const year = parseInt(monthInput.value.split('-')[0]);
        
        let matchedOption = null;
        if (amountSelect) {
            Array.from(amountSelect.options).forEach(option => {
                const start = parseInt(option.getAttribute('data-start'));
                const end = parseInt(option.getAttribute('data-end'));
                if (year >= start && year <= end) {
                    matchedOption = option;
                }
            });
        }

        if (matchedOption) {
            amountSelect.value = matchedOption.value;
            if (isCustom) {
                customAmountInput.value = matchedOption.value;
            }

            // Sync modal fields with matched interval
            if (presetIntervalSelect) {
                const matchedStart = matchedOption.getAttribute('data-start');
                const matchedEnd = matchedOption.getAttribute('data-end');
                const endVal = parseInt(matchedEnd) >= 2099 ? new Date().getFullYear() : matchedEnd;
                modalStartYear.value = matchedStart;
                modalEndYear.value = endVal;
                modalAmount.value = matchedOption.value;
            }
        }
    }

    if (presetIntervalSelect) {
        presetIntervalSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (selected && selected.value) {
                modalStartYear.value = selected.getAttribute('data-start');
                modalEndYear.value = selected.getAttribute('data-end');
                modalAmount.value = selected.getAttribute('data-amount');
            }
        });
    }

    if (toggleCustomBtn) {
        toggleCustomBtn.addEventListener('click', function () {
            isCustom = !isCustom;
            if (isCustom) {
                selectAmountWrapper.classList.add('d-none');
                customAmountWrapper.classList.remove('d-none');
                amountSelect.removeAttribute('name');
                amountSelect.removeAttribute('required');
                customAmountInput.setAttribute('name', 'amount');
                customAmountInput.setAttribute('required', 'required');
                customAmountInput.value = amountSelect.value;
                customAmountInput.focus();
                toggleCustomBtn.classList.remove('btn-outline-primary');
                toggleCustomBtn.classList.add('btn-primary');
                toggleText.textContent = 'Preset Dropdown';
            } else {
                customAmountWrapper.classList.add('d-none');
                selectAmountWrapper.classList.remove('d-none');
                customAmountInput.removeAttribute('name');
                customAmountInput.removeAttribute('required');
                amountSelect.setAttribute('name', 'amount');
                amountSelect.setAttribute('required', 'required');
                toggleCustomBtn.classList.remove('btn-primary');
                toggleCustomBtn.classList.add('btn-outline-primary');
                toggleText.textContent = 'Edit Amount';
                updateAmount();
            }
        });
    }
    
    if (monthInput && amountSelect) {
        monthInput.addEventListener('change', updateAmount);
        updateAmount();
    }
});
</script>
@endsection