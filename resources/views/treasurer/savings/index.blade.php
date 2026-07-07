@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-piggy-bank"></i> Record Monthly Savings</h4>@endsection
@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Quick Record</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('treasurer.savings.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Member <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select Member</option>
                            @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->member_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <input type="date" name="month" class="form-control" value="{{ $currentMonth }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contribution Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                        @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Active Savings Slots</label>
                        <div id="slots-container" class="mb-2">
                            <p class="text-muted small">Select a member to see their slots</p>
                        </div>
                        <div class="mt-2 text-dark">
                            Total Amount to Pay: <strong class="fw-bold text-success" id="total-amount">₦0.00</strong>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3"><i class="bi bi-save"></i> Record Savings</button>
                    <a href="{{ route('treasurer.savings.history') }}" class="btn btn-outline-info w-100"><i class="bi bi-clock-history"></i> View Savings History</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Members Overview</h6></div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Member</th><th>Code</th><th>Slots</th><th>Expected</th></tr></thead>
                    <tbody>
                    @foreach($members as $m)
                    <tr>
                        <td>{{ $m->name }}</td>
                        <td><span class="badge bg-primary">{{ $m->member_code }}</span></td>
                        <td>{{ $m->savingsSlots->where('is_active', true)->count() }}</td>
                        <td>₦{{ number_format($m->savingsSlots->where('is_active', true)->count() * 2000,2) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.querySelector('[name="user_id"]');
    const container = document.getElementById('slots-container');
    const totalAmount = document.getElementById('total-amount');

    function loadActiveSlots() {
        const memberId = userSelect.value;
        
        if (!memberId) {
            container.innerHTML = '<p class="text-muted small">Select a member to see their slots</p>';
            totalAmount.textContent = '₦0.00';
            return;
        }
        
        container.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...';
        totalAmount.textContent = '₦0.00';

        fetch(`/members/${memberId}/active-slots`)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(slots => {
                if (!slots.length) { 
                    container.innerHTML = '<p class="text-muted small">No active slots</p>'; 
                    totalAmount.textContent = '₦0.00';
                    return; 
                }
                
                container.innerHTML = `
                    <div class="alert alert-info py-2 px-3 mb-2 small">
                        <i class="bi bi-info-circle"></i> This member has <strong>${slots.length}</strong> active savings slots.
                    </div>
                    ${slots.map(s => `<input type="hidden" name="slot_ids[]" value="${s.id}">`).join('')}
                `;
                
                const total = slots.length * 2000;
                totalAmount.textContent = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            })
            .catch(err => {
                console.error('Error fetching active slots:', err);
                container.innerHTML = '<p class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Failed to load slots</p>';
                totalAmount.textContent = '₦0.00';
            });
    }

    if (userSelect) {
        userSelect.addEventListener('change', loadActiveSlots);
        
        // Auto-trigger on page load if a member is already selected
        if (userSelect.value) {
            loadActiveSlots();
        }
    }
});
</script>
@endpush
@endsection