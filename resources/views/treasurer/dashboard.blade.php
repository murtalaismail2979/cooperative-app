@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-speedometer2"></i> Treasurer Dashboard</h4>@endsection
@section('content')
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white shadow"><div class="card-body"><i class="bi bi-people icon"></i><div class="stat-label">Total Members</div><div class="stat-value">{{ $data['totalMembers'] }}</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-success text-white shadow"><div class="card-body"><i class="bi bi-piggy-bank icon"></i><div class="stat-label">Month's Savings</div><div class="stat-value">₦{{ number_format($data['currentMonthSavings'],2) }}</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-warning text-white shadow"><div class="card-body"><i class="bi bi-cash-stack icon"></i><div class="stat-label">Active Financing</div><div class="stat-value">{{ $data['activeLoans'] }}</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white shadow"><div class="card-body"><i class="bi bi-clock icon"></i><div class="stat-label">Pending Approvals</div><div class="stat-value">{{ $data['pendingApprovals'] }}</div></div></div>
    </div>
</div>
<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 fw-bold text-primary">Quick Actions</h6></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3"><a href="{{ route('treasurer.savings.index') }}" class="btn btn-outline-success w-100 p-3"><i class="bi bi-piggy-bank fs-3 d-block"></i>Record Savings</a></div>
            <div class="col-md-3 mb-3"><a href="{{ route('treasurer.loans.index') }}" class="btn btn-outline-warning w-100 p-3"><i class="bi bi-cash-stack fs-3 d-block"></i>Financing Repayments</a></div>
            <div class="col-md-3 mb-3"><a href="{{ route('treasurer.investments.index') }}" class="btn btn-outline-info w-100 p-3"><i class="bi bi-graph-up-arrow fs-3 d-block"></i>Investments</a></div>
            <div class="col-md-3 mb-3"><a href="{{ route('treasurer.expenses.create') }}" class="btn btn-outline-danger w-100 p-3"><i class="bi bi-cart fs-3 d-block"></i>New Expense</a></div>
        </div>
    </div>
</div>
@endsection