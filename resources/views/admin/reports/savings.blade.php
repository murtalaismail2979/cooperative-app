@extends('layouts.app')
@section('page-title')
<div class="d-flex justify-content-between align-items-center w-100">
    <h4 class="mb-0"><i class="bi bi-file-earmark-bar-graph"></i> Savings Report</h4>
    <a href="{{ route('admin.reports.all.export') }}" class="btn btn-sm btn-success shadow-sm"><i class="bi bi-file-earmark-zip"></i> Download All Reports (ZIP/Excel)</a>
</div>
@endsection
@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white shadow"><div class="card-body"><strong>Total Savings:</strong> ₦{{ number_format($totalSavings,2) }}</div></div>
    </div>
    <div class="col-md-6">
        <div class="card bg-info text-white shadow"><div class="card-body"><strong>Total Running Charges:</strong> ₦{{ number_format($totalCharges,2) }}</div></div>
    </div>
</div>
<ul class="nav nav-tabs mb-4" id="savingsReportTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'monthly' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'monthly']) }}">
            <i class="bi bi-calendar3"></i> Monthly Breakdown
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'yearly' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'yearly']) }}">
            <i class="bi bi-calendar-event"></i> Yearly Savings by Member
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'lifetime' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'lifetime']) }}">
            <i class="bi bi-person-lines-fill"></i> Lifetime Savings by Member
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'charges' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'charges']) }}">
            <i class="bi bi-receipt"></i> Running Charges by Member
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'financing' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'financing']) }}">
            <i class="bi bi-cash-coin"></i> Financing Run
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'businesses' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'businesses']) }}">
            <i class="bi bi-graph-up-arrow"></i> Businesses Run
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'dividends' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'dividends']) }}">
            <i class="bi bi-gift"></i> Dividends by Member
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'earnings' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('admin.reports.savings', ['tab' => 'earnings']) }}">
            <i class="bi bi-wallet2"></i> Cooperative & Management Earnings
        </a>
    </li>
</ul>

@if($activeTab === 'monthly')
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar3"></i> Monthly Breakdown</h6>
        <a href="{{ route('admin.reports.savings.export', ['tab' => 'monthly']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Total Savings</th>
                        <th>Member Count</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($monthlySavings as $ms)
                <tr>
                    <td class="fw-semibold">{{ $ms->year }}</td>
                    <td>{{ date('F', mktime(0,0,0,(int)$ms->month_num,1)) }}</td>
                    <td class="text-success fw-bold">₦{{ number_format($ms->total, 2) }}</td>
                    <td><span class="badge bg-secondary">{{ $ms->members }} members</span></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No data found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $monthlySavings->links() }}
        </div>
    </div>
</div>
@endif

@if($activeTab === 'yearly')
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-event"></i> Yearly Savings by Member</h6>
            </div>
            <div class="col-md-8">
                <form method="GET" action="{{ route('admin.reports.savings') }}" class="row g-2 justify-content-md-end">
                    <input type="hidden" name="tab" value="yearly">
                    <div class="col-auto">
                        <input type="text" name="yearly_search" id="yearly_search" class="form-control form-control-sm auto-search" placeholder="Search member..." value="{{ $yearlySearch }}">
                    </div>
                    <div class="col-auto">
                        <select name="yearly_year" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ $yearlyYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Search</button>
                        <a href="{{ route('admin.reports.savings.export', ['tab' => 'yearly', 'yearly_search' => $yearlySearch, 'yearly_year' => $yearlyYear]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
                        @if($yearlySearch || $yearlyYear)
                            <a href="{{ route('admin.reports.savings', ['tab' => 'yearly']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Year</th>
                        <th>Savings in Year</th>
                        <th>Cumulative Savings (End of Year)</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($yearlySavings->groupBy('user_id') as $userId => $userGroup)
                    @foreach($userGroup as $ys)
                    <tr style="break-inside: avoid; page-break-inside: avoid;">
                        @if($loop->first)
                            <td rowspan="{{ count($userGroup) }}" class="align-middle"><span class="badge bg-primary">{{ $ys->user->member_code }}</span></td>
                            <td rowspan="{{ count($userGroup) }}" class="align-middle fw-semibold">{{ $ys->user->name }}</td>
                        @endif
                        <td><span class="badge bg-info text-dark fw-bold">{{ $ys->year }}</span></td>
                        <td class="text-success fw-semibold">₦{{ number_format($ys->total_saved, 2) }}</td>
                        <td class="text-primary fw-bold">₦{{ number_format($ys->cumulative_saved, 2) }}</td>
                    </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No yearly savings found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $yearlySavings->links() }}
        </div>
    </div>
</div>
@endif

@if($activeTab === 'lifetime')
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-person-lines-fill"></i> Lifetime Savings by Member</h6>
            </div>
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.reports.savings') }}" class="row g-2 justify-content-md-end">
                    <input type="hidden" name="tab" value="lifetime">
                    <div class="col-auto">
                        <input type="text" name="lifetime_search" id="lifetime_search" class="form-control form-control-sm auto-search" placeholder="Search member..." value="{{ $lifetimeSearch }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Search</button>
                        <a href="{{ route('admin.reports.savings.export', ['tab' => 'lifetime', 'lifetime_search' => $lifetimeSearch]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
                        @if($lifetimeSearch)
                            <a href="{{ route('admin.reports.savings', ['tab' => 'lifetime']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Contribution Period</th>
                        <th>Total Savings Across All Years</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($lifetimeSavings as $ls)
                <tr>
                    <td><span class="badge bg-primary">{{ $ls->user->member_code }}</span></td>
                    <td class="fw-semibold">{{ $ls->user->name }}</td>
                    <td>
                        @if($ls->start_year == $ls->end_year)
                            <span class="badge bg-light text-dark">{{ $ls->start_year }}</span>
                        @else
                            <span class="badge bg-light text-dark">{{ $ls->start_year }} - {{ $ls->end_year }}</span>
                        @endif
                    </td>
                    <td class="text-success fw-bold fs-6">₦{{ number_format($ls->total_saved, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No savings records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $lifetimeSavings->links() }}
        </div>
    </div>
</div>
@endif

@if($activeTab === 'financing')
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-cash-coin"></i> Financing Run</h6>
        <a href="{{ route('admin.reports.savings.export', ['tab' => 'financing']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Year</th>
                        <th class="text-center">Month</th>
                        <th class="text-center">Financing Count</th>
                        <th class="text-end">Principal Amount</th>
                        <th class="text-end">Projected Profit</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($paginatedLoans as $period)
                <tr>
                    <td class="fw-semibold text-center">{{ $period['year'] }}</td>
                    <td class="text-center">{{ date('F', mktime(0, 0, 0, (int)$period['month'], 1)) }}</td>
                    <td class="text-center"><span class="badge bg-primary">{{ $period['loan_count'] }}</span></td>
                    <td class="text-end">₦{{ number_format($period['loan_principal'], 2) }}</td>
                    <td class="text-end text-success fw-semibold">₦{{ number_format($period['loan_profit'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No financing activity recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $paginatedLoans->links() }}
        </div>
    </div>
</div>
@endif

@if($activeTab === 'businesses')
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-graph-up-arrow"></i> Businesses Run</h6>
        <a href="{{ route('admin.reports.savings.export', ['tab' => 'businesses']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Year</th>
                        <th class="text-center">Month</th>
                        <th class="text-center">Business Count</th>
                        <th class="text-end">Capital Invested</th>
                        <th class="text-end">Sharable Profit</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($paginatedBusinesses as $period)
                <tr>
                    <td class="fw-semibold text-center">{{ $period['year'] }}</td>
                    <td class="text-center">{{ date('F', mktime(0, 0, 0, (int)$period['month'], 1)) }}</td>
                    <td class="text-center"><span class="badge bg-info text-dark">{{ $period['investment_count'] }}</span></td>
                    <td class="text-end text-primary fw-semibold">₦{{ number_format($period['investment_capital'], 2) }}</td>
                    <td class="text-end text-success fw-bold">₦{{ number_format($period['investment_profit'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No business activity recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $paginatedBusinesses->links() }}
        </div>
    </div>
</div>
@endif

@if($activeTab === 'charges')
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-receipt"></i> Running Charges by Member</h6>
            </div>
            <div class="col-md-9">
                <form method="GET" action="{{ route('admin.reports.savings') }}" class="row g-2 justify-content-md-end">
                    <input type="hidden" name="tab" value="charges">
                    <div class="col-auto">
                        <input type="text" name="charges_search" id="charges_search" class="form-control form-control-sm auto-search" placeholder="Search member..." value="{{ $chargesSearch }}">
                    </div>
                    <div class="col-auto">
                        <select name="charges_year" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ $chargesYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="charges_month" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Months</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $chargesMonth == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Search</button>
                        <a href="{{ route('admin.reports.savings.export', ['tab' => 'charges', 'charges_search' => $chargesSearch, 'charges_year' => $chargesYear, 'charges_month' => $chargesMonth]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
                        @if($chargesSearch || $chargesYear || $chargesMonth)
                            <a href="{{ route('admin.reports.savings', ['tab' => 'charges']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light text-primary">
                    <tr>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th class="text-center">Year</th>
                        <th class="text-center">Month</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($runningChargesReport as $item)
                <tr>
                    <td><span class="badge bg-primary">{{ $item->user->member_code }}</span></td>
                    <td class="fw-semibold">{{ $item->user->name }}</td>
                    <td class="text-center">{{ $item->year }}</td>
                    <td class="text-center">{{ date('F', mktime(0, 0, 0, (int)$item->month_num, 1)) }}</td>
                    <td class="text-end fw-semibold">₦{{ number_format($item->amount, 2) }}</td>
                    <td class="text-center">
                        @if($item->status === 'paid')
                            <span class="badge bg-success text-white">Paid</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                    <td class="text-center text-muted">
                        {{ $item->payment_date ? \Carbon\Carbon::parse($item->payment_date)->format('d/m/Y') : 'N/A' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No running charges records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $runningChargesReport->links() }}
        </div>
    </div>
</div>
@endif

@if($activeTab === 'dividends')
<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-primary border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Dividends Shared</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalAllShared, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-gift-fill fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-success border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Dividends Paid</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalAllPaid, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-warning border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Dividends Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalAllPending, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deductions Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-info border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Cooperative Earnings (Deductions)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalCooperativeDeductions, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-building fs-2 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-secondary border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Management Earnings (Deductions)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalManagementDeductions, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-person-gear fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-danger border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Profit Deducted (10%)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalDeductedAmount, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calculator fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-gift"></i> Dividends Earned by Member</h6>
            </div>
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.reports.savings') }}" class="row g-2 justify-content-md-end">
                    <input type="hidden" name="tab" value="dividends">
                    <div class="col-auto">
                        <input type="text" name="dividends_search" id="dividends_search" class="form-control form-control-sm auto-search" placeholder="Search member..." value="{{ $dividendsSearch }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Search</button>
                        <a href="{{ route('admin.reports.savings.export', ['tab' => 'dividends', 'dividends_search' => $dividendsSearch]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
                        @if($dividendsSearch)
                            <a href="{{ route('admin.reports.savings', ['tab' => 'dividends']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light text-primary">
                    <tr>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th class="text-end">Total Dividends Earned</th>
                        <th class="text-end text-success">Paid Dividends</th>
                        <th class="text-end text-warning">Pending Dividends</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($paginatedDividends as $item)
                <tr>
                    <td><span class="badge bg-primary">{{ $item->member_code }}</span></td>
                    <td class="fw-semibold">{{ $item->name }}</td>
                    <td class="text-end fw-bold">₦{{ number_format($item->total_earned ?? 0, 2) }}</td>
                    <td class="text-end text-success fw-semibold">₦{{ number_format($item->total_paid ?? 0, 2) }}</td>
                    <td class="text-end text-warning fw-semibold">₦{{ number_format($item->total_pending ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No dividends recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $paginatedDividends->links() }}
        </div>
    </div>
</div>
@endif

@if($activeTab === 'earnings')
<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-primary border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Lifetime Cooperative Earnings</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalCooperativeEarnings, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-building fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-info border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Lifetime Management Earnings</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalManagementEarnings, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-person-gear fs-2 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-success border-4 h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Earnings (10% Combined)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($totalCombinedEarnings, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-wallet2 fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-wallet2"></i> Cooperative & Management Earnings Report</h6>
        <a href="{{ route('admin.reports.savings.export', ['tab' => 'earnings']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle text-center">Year</th>
                        <th colspan="3" class="text-center bg-light">Businesses (Investments)</th>
                        <th colspan="3" class="text-center bg-light">Financing (Loans)</th>
                        <th colspan="3" class="text-center bg-light-primary text-primary">Yearly Totals (Business + Financing)</th>
                    </tr>
                    <tr>
                        <th class="text-end">Distributed Profit</th>
                        <th class="text-end">Cooperative (5%)</th>
                        <th class="text-end">Management (5%)</th>
                        <th class="text-end">Financing Profit</th>
                        <th class="text-end">Cooperative (5%)</th>
                        <th class="text-end">Management (5%)</th>
                        <th class="text-end">Cooperative Total</th>
                        <th class="text-end">Management Total</th>
                        <th class="text-end">Combined Total</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($paginatedEarnings as $item)
                <tr>
                    <td class="fw-bold text-center bg-light">{{ $item['year'] }}</td>
                    <td class="text-end text-muted">₦{{ number_format($item['business_profit'], 2) }}</td>
                    <td class="text-end text-success">₦{{ number_format($item['business_coop'], 2) }}</td>
                    <td class="text-end text-info">₦{{ number_format($item['business_mgmt'], 2) }}</td>
                    <td class="text-end text-muted">₦{{ number_format($item['financing_profit'], 2) }}</td>
                    <td class="text-end text-success">₦{{ number_format($item['financing_coop'], 2) }}</td>
                    <td class="text-end text-info">₦{{ number_format($item['financing_mgmt'], 2) }}</td>
                    <td class="text-end fw-bold text-success">₦{{ number_format($item['coop_total'], 2) }}</td>
                    <td class="text-end fw-bold text-info">₦{{ number_format($item['mgmt_total'], 2) }}</td>
                    <td class="text-end fw-bold text-primary">₦{{ number_format($item['grand_total'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-4 text-muted">No earnings records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $paginatedEarnings->links() }}
        </div>
    </div>
</div>
@endif
@endsection