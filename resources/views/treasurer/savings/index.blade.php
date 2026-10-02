@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-piggy-bank"></i> Record Monthly Savings</h4>@endsection
@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Quick Record</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.batch-upload.index', ['type' => 'savings']) }}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-cloud-arrow-up"></i> Batch Upload Savings
                    </a>
                    <a href="{{ route('treasurer.savings.history') }}" class="btn btn-info btn-sm">
                        <i class="bi bi-clock-history"></i> View Full History
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="member-info-summary"></div>
                <form method="POST" action="{{ route('treasurer.savings.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Member <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select Member</option>
                            @foreach($members as $member)
                            <option value="{{ $member->id }}"{{ !$member->is_active ? ' disabled' : '' }}{{ (string)old('user_id', $selectedMemberId ?? '') === (string)$member->id ? ' selected' : '' }}>{{ $member->name }} ({{ $member->member_code }}){{ !$member->is_active ? ' - (Inactive)' : '' }}</option>
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
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-save"></i> Record Savings</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Members Overview</h6></div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Member</th><th>Code</th><th>Slots</th><th>Expected</th><th class="text-center">Action</th></tr></thead>
                    <tbody>
                    @foreach($members as $m)
                    <tr class="{{ !$m->is_active ? 'table-secondary text-muted' : '' }}">
                        <td>
                            {{ $m->name }}
                            @if(!$m->is_active)
                                <span class="badge bg-secondary ms-1">Inactive</span>
                            @endif
                        </td>
                        <td><span class="badge bg-primary">{{ $m->member_code }}</span></td>
                        <td>{{ $m->savingsSlots->where('is_active', true)->count() }}</td>
                        <td>₦{{ number_format($m->savingsSlots->where('is_active', true)->count() * 2000,2) }}</td>
                        <td class="text-center">
                            @if(!$m->is_active)
                                <button class="btn btn-sm btn-secondary disabled" disabled title="Member is Inactive">
                                    <i class="bi bi-slash-circle"></i> Record
                                </button>
                            @else
                                <a href="{{ route('treasurer.savings.index', ['user_id' => $m->id]) }}" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-plus-circle"></i> Record
                                </a>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.querySelector('[name="user_id"]');
    const container = document.getElementById('slots-container');
    const totalAmount = document.getElementById('total-amount');
    const infoSummary = document.getElementById('member-info-summary');

    function loadActiveSlotsAndSummary() {
        const memberId = userSelect.value;
        
        if (!memberId) {
            container.innerHTML = '<p class="text-muted small">Select a member to see their slots</p>';
            totalAmount.textContent = '₦0.00';
            if (infoSummary) infoSummary.innerHTML = '';
            return;
        }
        
        container.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...';
        totalAmount.textContent = '₦0.00';

        // 1. Fetch active slots
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

        // 2. Fetch savings summary
        if (infoSummary) {
            fetch(`/members/${memberId}/savings-info`)
                .then(res => res.json())
                .then(info => {
                    let lastRecordStr = info.latest_month 
                        ? `<strong>${info.latest_month}</strong> (${info.latest_amount}${info.latest_payment_date ? ' on ' + info.latest_payment_date : ''})` 
                        : '<span class="text-muted">No previous savings recorded</span>';

                    infoSummary.innerHTML = `
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block fw-bold">Total Savings</small>
                                        <strong class="fs-6 text-primary">${info.formatted_total_savings}</strong>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block fw-bold">Last Recorded</small>
                                        <span class="small text-dark">${lastRecordStr}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                })
                .catch(err => {
                    console.error('Error fetching savings info:', err);
                    infoSummary.innerHTML = '';
                });
        }
    }

    if (userSelect) {
        userSelect.addEventListener('change', loadActiveSlotsAndSummary);
        
        // Auto-trigger on page load if a member is already selected
        if (userSelect.value) {
            loadActiveSlotsAndSummary();
        }
    }
});
</script>
@endpush
@endsection