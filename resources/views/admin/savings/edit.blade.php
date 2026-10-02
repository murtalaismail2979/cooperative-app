@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-pencil"></i> Edit Savings</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('admin.savings.update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="member_id" value="{{ $member->id }}">
    <input type="hidden" name="old_month" value="{{ $month }}">

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Member</label>
            <input type="text" class="form-control" value="{{ $member->name }} ({{ $member->member_code }})" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Month <span class="text-danger">*</span></label>
            <input type="month" name="month" class="form-control @error('month') is-invalid @enderror" value="{{ old('month', \Carbon\Carbon::parse($month)->format('Y-m')) }}" required>
            @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
            @php
                $latestSaving = \App\Models\MonthlySaving::where('user_id', $member->id)->where('month', $month)->first();
                $paymentDate = $latestSaving ? \Carbon\Carbon::parse($latestSaving->payment_date)->format('Y-m-d') : date('Y-m-d');
            @endphp
            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', $paymentDate) }}" required>
            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold">Savings Slots <span class="text-danger">*</span></label>
        <div id="slots-container" class="mb-2">
            @forelse($displaySlots as $slot)
                @php
                    $configuredSlotAmount = \App\Models\Slot::where('slot_number', $slot->slot_number)->value('amount');
                    $expectedAmount = $configuredSlotAmount !== null ? (float)$configuredSlotAmount : ($slot->slot_number * 2000);
                    $slotValue = old('amounts.' . $slot->id, $slotAmounts[$slot->id] ?? $expectedAmount);
                @endphp
                <div class="form-check mb-3 p-3 border rounded bg-light">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <input class="form-check-input slot-checkbox" type="checkbox" name="slots[]" value="{{ $slot->id }}" id="slot_{{ $slot->id }}" {{ in_array($slot->id, $paidSlotIds) ? 'checked' : '' }} data-default-amount="{{ $expectedAmount }}">
                            <label class="form-check-label fw-bold ms-1" for="slot_{{ $slot->id }}">
                                Slot #{{ $slot->slot_number }} - {{ $slot->is_active ? 'Active' : 'Inactive' }} ({{ $slot->slot_number }} {{ Str::plural('Slot', $slot->slot_number) }})
                            </label>
                        </div>
                        <span class="badge bg-primary">Allowed Amount: ₦{{ number_format($expectedAmount, 2) }}</span>
                    </div>
                    <div>
                        <label class="form-label text-muted small mb-1" for="amount_{{ $slot->id }}">Slot Amount (₦):</label>
                        <input type="number" id="amount_{{ $slot->id }}" name="amounts[{{ $slot->id }}]" class="form-control form-control-sm slot-amount @error('amounts.'.$slot->id) is-invalid @enderror" min="0" step="0.01" value="{{ number_format((float)$slotValue, 2, '.', '') }}" aria-label="Amount for slot {{ $slot->slot_number }}">
                        <small class="text-muted d-block mt-1">Must correspond to Slot #{{ $slot->slot_number }} (₦{{ number_format($expectedAmount, 2) }}) or ₦0.00 for zero savings.</small>
                        @error('amounts.'.$slot->id)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            @empty
                <div class="alert alert-warning mb-0">This member has no registered savings slots.</div>
            @endforelse
        </div>
        <div class="mt-2 text-dark">
            Total Amount to Pay: <strong class="fs-5 text-success" id="total-amount">₦0.00</strong>
        </div>
        @error('slots')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('admin.savings.history') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
    </div>
</form>
</div></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.slot-checkbox');
    const amountInputs = document.querySelectorAll('.slot-amount');
    const totalAmount = document.getElementById('total-amount');

    function calculateTotal() {
        let total = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                const amountInput = document.querySelector(`input[name="amounts[${cb.value}]"]`);
                total += parseFloat(amountInput?.value || 0);
            }
        });
        totalAmount.textContent = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                // When another slot is checked, automatically uncheck current/other checked slots
                checkboxes.forEach(otherCb => {
                    if (otherCb !== this) {
                        otherCb.checked = false;
                    }
                });
            }
            calculateTotal();
        });
    });

    amountInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    calculateTotal();
});
</script>
@endpush
@endsection
