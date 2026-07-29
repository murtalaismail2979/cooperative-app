@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-receipt"></i> Running Charges</h4>@endsection
@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Record Charge</h6>
                <a href="{{ route('admin.batch-upload.index', ['type' => 'running_charges']) }}" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-cloud-arrow-up"></i> Batch Upload Charges
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('treasurer.running-charges.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Member</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->member_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <input type="month" name="month" id="monthInput" class="form-control" value="{{ date('Y-m') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        <select name="amount" id="amountSelect" class="form-select" required>
                            <option value="500">500 (2024 to date)</option>
                            <option value="300">300 (2022 - 2023)</option>
                            <option value="100">100 (2021)</option>
                        </select>
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
    <div class="col-lg-8">
        <div class="card shadow mb-4">
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
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($charges as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->user->name ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($c->month)->format('M Y') }}</td>
                            <td class="text-end fw-semibold">₦{{ number_format($c->amount, 2) }}</td>
                            <td class="text-center text-muted">{{ $c->payment_date ? \Carbon\Carbon::parse($c->payment_date)->format('d/m/Y') : 'N/A' }}</td>
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
    
    function updateAmount() {
        if (!monthInput || !monthInput.value) return;
        const year = parseInt(monthInput.value.split('-')[0]);
        if (year <= 2021) {
            amountSelect.value = '100';
        } else if (year <= 2023) {
            amountSelect.value = '300';
        } else {
            amountSelect.value = '500';
        }
    }
    
    if (monthInput && amountSelect) {
        monthInput.addEventListener('change', updateAmount);
        // Trigger initially
        updateAmount();
    }
});
</script>
@endsection
