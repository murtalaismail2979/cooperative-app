@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-speedometer2"></i> Admin Dashboard</h4>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white shadow">
            <div class="card-body">
                <i class="bi bi-people icon"></i>
                <div class="stat-label">Total Members</div>
                <div class="stat-value">{{ $data['totalMembers'] }}</div>
                <small>{{ $data['activeMembers'] }} Active</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-success text-white shadow">
            <div class="card-body">
                <i class="bi bi-piggy-bank icon"></i>
                <div class="stat-label">Total Savings</div>
                <div class="stat-value">₦{{ number_format($data['totalSavings'], 2) }}</div>
                <small>₦{{ number_format($data['currentMonthSavings'], 2) }} This Month</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-warning text-white shadow">
            <div class="card-body">
                <i class="bi bi-cash-stack icon"></i>
                <div class="stat-label">Active Financing</div>
                <div class="stat-value">{{ $data['activeLoans'] }}</div>
                <small>₦{{ number_format($data['totalOutstanding'], 2) }} Outstanding</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white shadow">
            <div class="card-body">
                <i class="bi bi-graph-up-arrow icon"></i>
                <div class="stat-label">Investments</div>
                <div class="stat-value">{{ $data['activeInvestments'] }}</div>
                <small>₦{{ number_format($data['totalInvestmentCapital'], 2) }} Capital</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('admin.members.create') }}" class="btn btn-outline-primary w-100 p-3">
                            <i class="bi bi-person-plus fs-3 d-block"></i>
                            Add Member
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('admin.savings.create') }}" class="btn btn-outline-success w-100 p-3">
                            <i class="bi bi-piggy-bank fs-3 d-block"></i>
                            Record Savings
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('admin.loans.create') }}" class="btn btn-outline-warning w-100 p-3">
                            <i class="bi bi-cash-stack fs-3 d-block"></i>
                            New Financing
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('admin.investments.create') }}" class="btn btn-outline-info w-100 p-3">
                            <i class="bi bi-graph-up-arrow fs-3 d-block"></i>
                            New Investment
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('admin.expenses.pending') }}" class="btn btn-outline-danger w-100 p-3">
                            <i class="bi bi-check-circle fs-3 d-block"></i>
                            Approve Expenses
                            @if($data['pendingExpenses'] > 0)
                                <span class="badge bg-danger">{{ $data['pendingExpenses'] }}</span>
                            @endif
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('admin.dividends.create') }}" class="btn btn-outline-secondary w-100 p-3">
                            <i class="bi bi-gift fs-3 d-block"></i>
                            Distribute Dividends
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Members</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @forelse($data['recentMembers'] as $member)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $member->name }}
                            <span class="badge bg-primary rounded-pill">{{ $member->member_code }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No members yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-calendar-check"></i> Savings Summary by Year & Month</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="table-light text-primary">
                            <tr>
                                <th>Year</th>
                                <th>Month</th>
                                <th class="text-end">Total Savings Collected</th>
                                <th class="text-end">Active Members who Contributed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['savingsBreakdown'] as $breakdown)
                                <tr>
                                    <td><strong>{{ $breakdown->year }}</strong></td>
                                    <td>{{ date('F', mktime(0, 0, 0, $breakdown->month_num, 1)) }}</td>
                                    <td class="text-end text-success"><strong>₦{{ number_format($breakdown->total, 2) }}</strong></td>
                                    <td class="text-end">{{ $breakdown->members }} {{ Str::plural('Member', $breakdown->members) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No savings history recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection