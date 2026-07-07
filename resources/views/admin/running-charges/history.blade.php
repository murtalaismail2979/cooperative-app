@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-clock-history"></i> Running Charges History</h4>@endsection
@section('content')
<div class="row mb-4 g-4">
    <div class="col-md-4">
        <div class="card bg-success text-white shadow h-100">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold">Total Collected (Filtered)</div>
                        <div class="fs-3 fw-bold">₦{{ number_format($totalCharges, 2) }}</div>
                    </div>
                    <i class="bi bi-wallet2 fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow h-100">
            <div class="card-header bg-light py-2 d-flex align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-check"></i> Monthly Summary of Total Charges</h6>
            </div>
            <div class="card-body py-3" style="max-height: 150px; overflow-y: auto;">
                <div class="row g-2">
                    @forelse($monthlyCharges as $mc)
                    <div class="col-sm-6 col-md-4">
                        <div class="p-2 border rounded bg-light d-flex flex-column justify-content-between">
                            <span class="text-muted small fw-bold">{{ date('F Y', mktime(0, 0, 0, (int)$mc->month_num, 1, (int)$mc->year)) }}</span>
                            <span class="fs-6 fw-bold text-primary">₦{{ number_format($mc->total, 2) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 py-2 text-center text-muted">No monthly summaries found</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header"><span>All Running Charges</span></div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.running-charges.history') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-4 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="member" id="adminChargesSearch" class="form-control border-start-0 auto-search" placeholder="Search member name or code..." value="{{ $member ?? '' }}">
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
            @if(!empty($member) || !empty($month) || !empty($year))
            <div class="col-auto">
                <a href="{{ route('admin.running-charges.history') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light text-primary">
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th class="text-center">Month</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Payment Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($charges as $i => $c)
                <tr>
                    <td>{{ $charges->firstItem() + $i }}</td>
                    <td>
                        <div class="fw-bold">{{ $c->user->name ?? 'N/A' }}</div>
                        @if(!empty($c->user->member_code))
                            <span class="badge bg-light text-primary border">{{ $c->user->member_code }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($c->month)->format('F Y') }}</td>
                    <td class="text-end fw-semibold">₦{{ number_format($c->amount, 2) }}</td>
                    <td class="text-center text-muted">{{ $c->payment_date ? \Carbon\Carbon::parse($c->payment_date)->format('d/m/Y') : 'N/A' }}</td>
                    <td class="text-center">
                        <a href="{{ route('admin.running-charges.edit', $c) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('admin.running-charges.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this running charge?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No records found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $charges->links() }}
        </div>
    </div>
</div>
@endsection