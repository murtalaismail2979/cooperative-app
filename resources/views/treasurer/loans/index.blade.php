@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-cash-stack"></i> Active Financing - Repayments</h4>@endsection
@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Active Financing</span>
        <a href="{{ route('admin.batch-upload.index', ['type' => 'loans']) }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-cloud-arrow-up"></i> Batch Upload Loans
        </a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('treasurer.loans.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-4 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="treasurerLoansSearch" class="form-control border-start-0 auto-search" placeholder="Search member name or code..." value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <select name="member_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Members --</option>
                    @foreach($members ?? [] as $m)
                        <option value="{{ $m->id }}" {{ ($memberId ?? '') == $m->id ? 'selected' : '' }}>
                            {{ $m->name }} ({{ $m->member_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            @if(!empty($search) || !empty($memberId))
            <div class="col-auto">
                <a href="{{ route('treasurer.loans.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear Filter</a>
            </div>
            @endif
        </form>

        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Member</th><th>Principal</th><th>Balance</th><th>Monthly</th><th>Progress</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($loans as $loan)
            <tr>
                <td>{{ $loan->id }}</td>
                <td>{{ $loan->user->name ?? 'N/A' }}</td>
                <td>₦{{ number_format($loan->principal_amount,2) }}</td>
                <td class="fw-bold text-danger">₦{{ number_format($loan->outstanding_balance,2) }}</td>
                <td>₦{{ number_format($loan->monthly_payment,2) }}</td>
                <td>
                    @php $pct = $loan->duration_months > 0 ? round((($loan->duration_months - $loan->remaining_months) / $loan->duration_months) * 100) : 0; @endphp
                    <div class="progress"><div class="progress-bar bg-success" style="width:{{ $pct }}%">{{ $pct }}%</div></div>
                </td>
                <td>
                    <a href="{{ route('treasurer.loans.show', $loan) }}" class="btn btn-sm btn-info" title="View Details">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('treasurer.loans.edit', $loan) }}" class="btn btn-sm btn-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="{{ route('treasurer.loans.destroy', $loan) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this financing record? This will also delete all associated repayments.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">No active financing records</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $loans->links() }}
    </div>
</div>
@endsection