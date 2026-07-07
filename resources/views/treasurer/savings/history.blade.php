@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-clock-history"></i> Savings History</h4>@endsection
@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold">Total Savings (Filtered)</div>
                        <div class="fs-4 fw-bold">₦{{ number_format($totalSavings, 2) }}</div>
                    </div>
                    <i class="bi bi-piggy-bank fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Savings Records</span>
        <a href="{{ route('treasurer.savings.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Record Savings</a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('treasurer.savings.history') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-4 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="treasurerSavingsSearch" class="form-control border-start-0 auto-search" placeholder="Search by name or code..." value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="month" class="form-select" onchange="this.form.submit()">
                    <option value="">All Months</option>
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            @if(!empty($search) || !empty($month) || !empty($year))
            <div class="col-auto">
                <a href="{{ route('treasurer.savings.history') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light"><tr><th>#</th><th>Member</th><th>Slots Paid</th><th>Month</th><th>Total Amount</th><th>Date Paid</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                @forelse($savings as $i => $s)
                <tr>
                    <td>{{ $savings->firstItem() + $i }}</td>
                    <td>
                        <div class="fw-bold">{{ $s->user->name ?? 'N/A' }}</div>
                        @if(!empty($s->user->member_code))
                            <span class="badge bg-light text-primary border">{{ $s->user->member_code }}</span>
                        @endif
                    </td>
                    <td>{{ $s->slots_count }} {{ Str::plural('Slot', $s->slots_count) }}</td>
                    <td>{{ \Carbon\Carbon::parse($s->month)->format('M Y') }}</td>
                    <td>₦{{ number_format($s->total_amount,2) }}</td>
                    <td>{{ $s->latest_payment_date ? \Carbon\Carbon::parse($s->latest_payment_date)->format('d/m/Y') : 'N/A' }}</td>
                    <td class="text-center">
                        <a href="{{ route('treasurer.savings.edit', ['user_id' => $s->user_id, 'month' => \Carbon\Carbon::parse($s->month)->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('treasurer.savings.destroy') }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this savings record? This will also delete the corresponding running charge.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="user_id" value="{{ $s->user_id }}">
                            <input type="hidden" name="month" value="{{ \Carbon\Carbon::parse($s->month)->format('Y-m-d') }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No savings records found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $savings->links() }}
        </div>
    </div>
</div>
@endsection
