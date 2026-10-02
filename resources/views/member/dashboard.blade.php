@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-speedometer2"></i> My Dashboard</h4>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white shadow">
            <div class="card-body">
                <i class="bi bi-piggy-bank icon"></i>
                <div class="stat-label">My Savings Slots</div>
                <div class="stat-value">{{ $data['totalSlots'] }}</div>
                <small>Active slots</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-success text-white shadow">
            <div class="card-body">
                <i class="bi bi-wallet2 icon"></i>
                <div class="stat-label">Month's Savings</div>
                <div class="stat-value">₦{{ number_format($data['currentMonthSavings'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white shadow">
            <div class="card-body">
                <i class="bi bi-cash-stack icon"></i>
                <div class="stat-label">Total Savings</div>
                <div class="stat-value">₦{{ number_format($data['totalSavings'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-warning text-white shadow">
            <div class="card-body">
                <i class="bi bi-receipt icon"></i>
                <div class="stat-label">Running Charges Paid</div>
                <div class="stat-value">{{ $data['runningChargesPaid'] }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Registration Fee Card -->
@if(isset($data['registrationFee']))
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 me-3">
                        <i class="bi bi-card-checklist fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-bold text-dark">Member Registration Fee</h6>
                        <small class="text-muted">Required: ₦{{ number_format($data['registrationFee']->fee_amount, 2) }} | Paid: <strong class="text-success">₦{{ number_format($data['registrationFee']->total_paid, 2) }}</strong> | Outstanding: <strong class="text-danger">₦{{ number_format($data['registrationFee']->outstanding_balance, 2) }}</strong></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @if($data['registrationFee']->status === 'fully_paid')
                        <span class="badge bg-success rounded-pill px-3">Fully Paid</span>
                    @elseif($data['registrationFee']->status === 'partially_paid')
                        <span class="badge bg-warning text-dark rounded-pill px-3">Partially Paid</span>
                    @elseif($data['registrationFee']->status === 'requires_verification')
                        <span class="badge bg-info text-dark rounded-pill px-3">Requires Verification</span>
                    @else
                        <span class="badge bg-danger rounded-pill px-3">Unpaid</span>
                    @endif
                    <a href="{{ route('member.registration-fee.index') }}" class="btn btn-sm btn-outline-primary">View Details & Receipts</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Active Financing</h6>
            </div>
            <div class="card-body">
                @if($data['activeLoan'])
                    <div class="d-flex justify-content-between mb-2">
                        <span>Principal:</span>
                        <strong>₦{{ number_format($data['activeLoan']->principal_amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Amount:</span>
                        <strong>₦{{ number_format($data['activeLoan']->total_amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Monthly Payment:</span>
                        <strong>₦{{ number_format($data['activeLoan']->monthly_payment, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Outstanding:</span>
                        <strong class="text-danger">₦{{ number_format($data['activeLoan']->outstanding_balance, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Remaining Months:</span>
                        <strong>{{ $data['activeLoan']->remaining_months }} / {{ $data['activeLoan']->duration_months }}</strong>
                    </div>
                @else
                    <p class="text-muted mb-0">No active financing.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Savings</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Slots Paid</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['recentSavings'] as $saving)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($saving->month)->format('M Y') }}</td>
                                    <td>{{ $saving->slots_count }} {{ Str::plural('Slot', $saving->slots_count) }}</td>
                                    <td>₦{{ number_format($saving->total_amount, 2) }}</td>
                                    <td>
                                        @if(($saving->status ?? 'paid') === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">No savings records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-person"></i> Personal Profile</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Member Code:</span>
                    <strong class="text-primary">{{ auth()->user()->member_code }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Full Name:</span>
                    <strong>{{ auth()->user()->name }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Email Address:</span>
                    <strong>{{ auth()->user()->email }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Phone Number:</span>
                    <strong>{{ auth()->user()->phone ?? 'N/A' }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Registration Year:</span>
                    <strong>{{ auth()->user()->registration_year }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Address:</span>
                    <strong>{{ auth()->user()->address ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-people"></i> Next of Kin Details</h6>
            </div>
            <div class="card-body">
                @if(auth()->user()->nextOfKin)
                    <div class="d-flex justify-content-between mb-2">
                        <span>Name:</span>
                        <strong>{{ auth()->user()->nextOfKin->name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Relationship:</span>
                        <strong>{{ auth()->user()->nextOfKin->relationship }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Phone Number:</span>
                        <strong>{{ auth()->user()->nextOfKin->phone }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Contact Address:</span>
                        <strong>{{ auth()->user()->nextOfKin->address ?? 'N/A' }}</strong>
                    </div>
                @else
                    <p class="text-muted mb-0">No next of kin details recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Links</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('member.savings') }}" class="btn btn-outline-primary w-100 p-3">
                            <i class="bi bi-piggy-bank fs-3 d-block"></i>
                            My Savings
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('member.loans') }}" class="btn btn-outline-warning w-100 p-3">
                            <i class="bi bi-cash-stack fs-3 d-block"></i>
                            My Financing
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('member.dividends') }}" class="btn btn-outline-success w-100 p-3">
                            <i class="bi bi-gift fs-3 d-block"></i>
                            Dividends
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('password.recovery') }}" class="btn btn-outline-secondary w-100 p-3">
                            <i class="bi bi-shield-lock fs-3 d-block"></i>
                            Retrieve Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection