@extends('layouts.app')

@section('title', 'Registration Fee Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-gear-fill me-2 text-primary"></i>Registration Fee Settings</h2>
            <p class="text-muted mb-0">Configure system-wide member registration fee amount and review update history.</p>
        </div>
        <a href="{{ route('admin.registration-fees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Registration Fees
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Current Fee Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.registration-fees.settings.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-medium">Registration Fee Amount (₦) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text fw-bold">₦</span>
                                <input type="number" step="0.01" min="0" name="amount" class="form-control fw-bold" value="{{ old('amount', number_format($currentAmount, 2, '.', '')) }}" required>
                            </div>
                            <small class="text-muted">Applied to all newly registered members.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium">Reason for Change / Notes</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Approved AGM fee revision 2026"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-save me-1"></i> Update Registration Fee
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Fee Revision History & Audit Trail</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Old Amount</th>
                                    <th>New Amount</th>
                                    <th>Changed By</th>
                                    <th>Reason / Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($histories as $history)
                                    <tr>
                                        <td>{{ $history->created_at->format('d/m/Y H:i A') }}</td>
                                        <td class="text-muted">₦{{ number_format($history->old_amount, 2) }}</td>
                                        <td class="fw-bold text-success">₦{{ number_format($history->new_amount, 2) }}</td>
                                        <td>{{ $history->changedBy->name ?? 'System' }}</td>
                                        <td>{{ $history->reason ?? 'No notes' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No historical fee changes recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($histories->hasPages())
                        <div class="p-3 border-top">
                            {{ $histories->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
