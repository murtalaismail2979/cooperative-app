@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-gift"></i> Dividend Distributions & Management</h4>@endsection
@section('content')

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow border-left-primary h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Dividend Pool</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($summaryTotalAmount ?? 0, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-stack fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow border-left-success h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Distributions</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summaryCount ?? 0) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-journal-check fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow border-left-info h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Investments Involved</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summaryInvestmentsInvolved ?? 0) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-building fs-2 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Dividends Card -->
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-search me-1"></i> Filter & Search Shared Dividends</span>
        <a href="{{ route('admin.dividends.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Distribute Dividends</a>
    </div>
    <div class="card-body">
        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('admin.dividends.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Financial Year</label>
                <select name="year" class="form-select">
                    <option value="">All Financial Years</option>
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (string)request('year') === (string)$yr ? 'selected' : '' }}>
                            Year {{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Select Investment</label>
                <select name="investment_id" class="form-select">
                    <option value="">All Investments</option>
                    @foreach($availableInvestments as $inv)
                        <option value="{{ $inv->id }}" {{ request('investment_id') == $inv->id ? 'selected' : '' }}>
                            {{ $inv->name }} ({{ $inv->type }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Investment Name</label>
                <input type="text" name="investment_name" id="adminDivInvestmentSearch" class="form-control auto-search" placeholder="Search investment name..." value="{{ request('investment_name') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Member Name / Code</label>
                <input type="text" name="member_name" id="adminDivMemberSearch" class="form-control auto-search" placeholder="Search member name or code..." value="{{ request('member_name') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Payout Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Fully Paid</option>
                    <option value="pending" {{ in_array(request('status'), ['pending', 'unpaid']) ? 'selected' : '' }}>Pending / Unpaid</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Sort By</label>
                <select name="sort_by" class="form-select">
                    <option value="distributed_at" {{ request('sort_by') === 'distributed_at' ? 'selected' : '' }}>Distribution Date</option>
                    <option value="total_dividend_amount" {{ request('sort_by') === 'total_dividend_amount' ? 'selected' : '' }}>Total Amount</option>
                    <option value="year" {{ request('sort_by') === 'year' ? 'selected' : '' }}>Year</option>
                    <option value="id" {{ request('sort_by') === 'id' ? 'selected' : '' }}>ID</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Sort Order</label>
                <select name="sort_dir" class="form-select">
                    <option value="desc" {{ request('sort_dir') === 'desc' ? 'selected' : '' }}>Descending</option>
                    <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>Ascending</option>
                </select>
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply Filters</button>
                <a href="{{ route('admin.dividends.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear Filters</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light text-primary">
                    <tr>
                        <th>Investment / Business</th>
                        <th>Year</th>
                        <th>Sharable Pool (₦)</th>
                        <th>Total Units</th>
                        <th>Unit Value (₦)</th>
                        <th>Payout Status</th>
                        <th>Distributed Date</th>
                        <th>By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($dividends as $d)
                    @php
                        $hasPending = $d->payouts->contains('paid', false);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $d->investment->name ?? 'N/A' }}</strong>
                            @if($d->investment)
                                <div class="small text-muted">{{ $d->investment->type }}</div>
                            @endif
                        </td>
                        <td><span class="badge bg-secondary">{{ $d->year }}</span></td>
                        <td class="fw-bold text-success">₦{{ number_format($d->total_dividend_amount, 2) }}</td>
                        <td>{{ number_format($d->total_units) }}</td>
                        <td>₦{{ number_format($d->unit_value, 2) }}</td>
                        <td>
                            @if(!$hasPending)
                                <span class="badge bg-success">Fully Paid</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending Payouts</span>
                            @endif
                        </td>
                        <td>{{ $d->distributed_at?->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ $d->distributor->name ?? 'N/A' }}</td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('admin.dividends.show', $d) }}" class="btn btn-sm btn-info text-white" title="View Payouts Details"><i class="bi bi-eye"></i></a>
                                @if($hasPending)
                                    <form method="POST" action="{{ route('admin.dividends.pay', $d) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Mark All Paid"><i class="bi bi-check-circle"></i></button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.dividends.destroy', $d) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this dividend distribution?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No dividends found matching the selected criteria.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $dividends->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Member Dividends Cumulative Summary Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-people me-1"></i> Member Dividends Summary 
            @if(request('year'))
                <span class="badge bg-primary ms-1">Year {{ request('year') }}</span>
            @else
                <span class="badge bg-secondary ms-1">All Years Cumulative</span>
            @endif
        </h6>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success text-white px-2 py-2">
                Total Shared: ₦{{ number_format($totalAllShared, 2) }}
            </span>
            <span class="badge bg-primary text-white px-2 py-2">
                Total Paid: ₦{{ number_format($totalAllPaid, 2) }}
            </span>
            <span class="badge bg-warning text-dark px-2 py-2">
                Total Pending: ₦{{ number_format($totalAllPending, 2) }}
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle mb-0">
                <thead class="table-light text-primary">
                    <tr>
                        <th>Member Code</th>
                        <th>Name</th>
                        <th class="text-end text-success">Total Earned</th>
                        <th class="text-end text-primary">Total Paid</th>
                        <th class="text-end text-warning">Total Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($memberSummaries as $member)
                        <tr>
                            <td><span class="badge bg-primary">{{ $member->member_code }}</span></td>
                            <td><strong>{{ $member->name }}</strong></td>
                            <td class="text-end text-success fw-bold">₦{{ number_format($member->total_earned ?? 0, 2) }}</td>
                            <td class="text-end text-primary fw-bold">₦{{ number_format($member->total_paid ?? 0, 2) }}</td>
                            <td class="text-end text-warning fw-bold">₦{{ number_format($member->total_pending ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No members found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="2" class="text-end">Total:</td>
                        <td class="text-end text-success">₦{{ number_format($totalAllShared, 2) }}</td>
                        <td class="text-end text-primary">₦{{ number_format($totalAllPaid, 2) }}</td>
                        <td class="text-end text-warning">₦{{ number_format($totalAllPending, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $memberSummaries->withQueryString()->links() }}
        </div>
    </div>
</div>

@endsection