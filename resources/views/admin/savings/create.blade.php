@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-plus-circle"></i> Record Savings</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('admin.savings.store') }}">
    @csrf
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Member <span class="text-danger">*</span></label>
            <select name="member_id" class="form-select @error('member_id') is-invalid @enderror" required>
                <option value="">Select Member</option>
                @foreach($members as $member)
                <option value="{{ $member->id }}" {{ old('member_id') == $member->id ? 'selected' : '' }}>{{ $member->name }} ({{ $member->member_code }})</option>
                @endforeach
            </select>
            @error('member_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Month <span class="text-danger">*</span></label>
            <input type="month" name="month" class="form-control @error('month') is-invalid @enderror" value="{{ old('month', date('Y-m')) }}" required>
            @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Contribution Date <span class="text-danger">*</span></label>
            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Active Savings Slots <span class="text-danger">*</span></label>
        <div id="slots-container" class="mb-2">
            <p class="text-muted">Select a member first to see their slots.</p>
        </div>
        <div class="mt-2 text-dark">
            Total Amount to Pay: <strong class="fs-5 text-success" id="total-amount">₦0.00</strong>
        </div>
        @error('slots')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="d-flex justify-content-between">
        <a href="{{ route('admin.savings.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Record Savings</button>
    </div>
</form>
</div></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const memberSelect = document.querySelector('[name="member_id"]');
    const container = document.getElementById('slots-container');
    const totalAmount = document.getElementById('total-amount');

    function loadActiveSlots() {
        const memberId = memberSelect.value;
        
        if (!memberId) { 
            container.innerHTML = '<p class="text-muted">Select a member first to see their slots.</p>'; 
            totalAmount.textContent = '₦0.00';
            return; 
        }
        
        container.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading slots...';
        totalAmount.textContent = '₦0.00';

        fetch(`/members/${memberId}/active-slots`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(slots => {
                if (slots.length === 0) {
                    container.innerHTML = '<p class="text-muted">This member has no active savings slots.</p>';
                    totalAmount.textContent = '₦0.00';
                    return;
                }
                
                container.innerHTML = `
                    <div class="alert alert-info py-2 px-3 mb-2">
                        <i class="bi bi-info-circle"></i> This member has <strong>${slots.length}</strong> active savings slots.
                    </div>
                    ${slots.map(s => `<input type="hidden" name="slots[]" value="${s.id}">`).join('')}
                `;
                
                const total = slots.length * 2000;
                totalAmount.textContent = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            })
            .catch(err => {
                console.error('Error fetching active slots:', err);
                container.innerHTML = '<p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load slots. Please try again.</p>';
                totalAmount.textContent = '₦0.00';
            });
    }

    memberSelect.addEventListener('change', loadActiveSlots);

    // Auto-trigger on page load if a member is already selected
    if (memberSelect.value) {
        loadActiveSlots();
    }
});
</script>
@endpush
@endsection