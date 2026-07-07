@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-graph-up-arrow"></i> Investments</h4>@endsection
@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Investments</span>
        <div>
            <a href="{{ route('admin.investment-types.index') }}" class="btn btn-outline-primary btn-sm me-2"><i class="bi bi-gear"></i> Investment Types</a>
            <a href="{{ route('admin.investments.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Investment</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.investments.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="investmentSearch" class="form-control border-start-0 auto-search" placeholder="Search by name, capital, status, type..." value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Search</button>
            </div>
            @if(!empty($search))
            <div class="col-auto">
                <a href="{{ route('admin.investments.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>#</th><th>Name</th><th>Capital</th><th>Returns</th><th>Sharable Profit</th><th>ROI</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($investments as $inv)
                <tr>
                    <td>{{ $inv->id }}</td>
                    <td>{{ $inv->name }}</td>
                    <td>₦{{ number_format($inv->capital_amount,2) }}</td>
                    <td>₦{{ number_format($inv->total_returns,2) }}</td>
                    <td>₦{{ number_format($inv->sharable_profit,2) }}</td>
                    <td><span class="badge bg-{{ $inv->roi >= 0 ? 'success' : 'danger' }}">{{ $inv->roi }}%</span></td>
                    <td>{{ $inv->start_date?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td>{{ $inv->end_date?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td><span class="badge bg-{{ $inv->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($inv->status) }}</span></td>
                    <td>
                        <a href="{{ route('admin.investments.show', $inv) }}" class="btn btn-sm btn-info text-white" title="View Details"><i class="bi bi-eye"></i></a>
                        <button type="button" class="btn btn-sm btn-success record-profit-btn" 
                                data-name="{{ $inv->name }}" 
                                data-url="{{ route('admin.investments.return', $inv) }}" 
                                title="Record Profit/Return">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                        <a href="{{ route('admin.investments.edit', $inv) }}" class="btn btn-sm btn-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.investments.destroy', $inv) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this investment? All returns and expenses for this investment will be deleted.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center">No investments</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $investments->links() }}
    </div>
</div>

<!-- Record Profit Modal -->
<div class="modal fade" id="recordProfitModal" tabindex="-1" aria-labelledby="recordProfitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="recordProfitForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="recordProfitModalLabel"><i class="bi bi-plus-circle"></i> Record Profit/Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Investment / Business</label>
                        <input type="text" id="modal_investment_name" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Profit generated / Return">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Record Profit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const recordProfitButtons = document.querySelectorAll('.record-profit-btn');
    const modalElement = document.getElementById('recordProfitModal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('recordProfitForm');
    const nameInput = document.getElementById('modal_investment_name');

    recordProfitButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const name = this.getAttribute('data-name');
            
            form.setAttribute('action', url);
            nameInput.value = name;
            
            modal.show();
        });
    });
});
</script>
@endpush
@endsection