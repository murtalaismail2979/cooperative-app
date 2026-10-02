@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-receipt"></i> Running Charges</h4>@endsection
@section('content')
<div class="row">
    <div class="col-lg-4" style="min-width: 0;">
        <div class="card shadow mb-4" style="overflow: hidden;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Record Charge</h6>
                <a href="{{ route('admin.batch-upload.index', ['type' => 'running_charges']) }}" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-cloud-arrow-up"></i> Batch
                </a>
            </div>
            <div class="card-body" style="overflow: hidden;">
                <form method="POST" action="{{ route('treasurer.running-charges.store') }}">
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
                                        <option value="{{ $rate->amount }}" data-start="{{ $rate->start_year }}" data-end="{{ $rate->end_year }}">
                                            ₦{{ number_format($rate->amount, 0) }} ({{ $rate->start_year == $rate->end_year ? $rate->start_year : ($rate->end_year >= 2099 ? $rate->start_year . ' to date' : $rate->start_year . ' - ' . $rate->end_year) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="customAmountWrapper" class="flex-grow-1 d-none" style="min-width: 0;">
                                <input type="number" step="0.01" min="0" id="customAmountInput" class="form-control rounded-end-0 w-100" style="max-width: 100%;" placeholder="Enter custom amount...">
                            </div>
                            <button type="button" id="toggleCustomAmountBtn" class="btn btn-outline-primary text-nowrap" title="Edit amount for this entry">
                                <i class="bi bi-pencil-square"></i> <span id="toggleText">Edit Amount</span>
                            </button>
                        </div>
                        <span class="form-text text-muted small mt-1">
                            Click <strong class="text-primary">Edit Amount</strong> to type any custom amount for this charge.
                        </span>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Charges</span>
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary fs-6 me-2">Total: ₦{{ number_format($overallTotal, 2) }}</span>
                    <a href="{{ route('treasurer.running-charges.history') }}" class="btn btn-info btn-sm"><i class="bi bi-clock-history"></i> Full History</a>
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
                                <a href="{{ route('treasurer.running-charges.edit', $c) }}" class="btn btn-sm btn-outline-primary" title="Edit running charge">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('treasurer.running-charges.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this running charge?')">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthInput = document.getElementById('monthInput');
    const amountSelect = document.getElementById('amountSelect');
    const toggleCustomBtn = document.getElementById('toggleCustomAmountBtn');
    const toggleText = document.getElementById('toggleText');
    const selectAmountWrapper = document.getElementById('selectAmountWrapper');
    const customAmountWrapper = document.getElementById('customAmountWrapper');
    const customAmountInput = document.getElementById('customAmountInput');
    
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
        }
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
