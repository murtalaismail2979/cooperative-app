@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calculator"></i> Annual Dividend & Loss Reconciliation</h1>
            <p class="text-muted small mb-0">Reconcile annual business & financing losses against distributed dividends for accurate member entitlements.</p>
        </div>
        <div>
            @if($isClosed)
                <span class="badge bg-danger fs-6 px-3 py-2 me-2"><i class="bi bi-lock-fill"></i> Financial Year {{ $selectedYear }} Closed</span>
                <form action="{{ route('admin.dividends.reconciliation.lock', $selectedYear) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('Are you sure you want to reopen Financial Year {{ $selectedYear }}?')">
                        <i class="bi bi-unlock"></i> Reopen Year
                    </button>
                </form>
            @else
                <span class="badge bg-success fs-6 px-3 py-2 me-2"><i class="bi bi-unlock-fill"></i> Financial Year {{ $selectedYear }} Open</span>
                <form action="{{ route('admin.dividends.reconciliation.lock', $selectedYear) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to CLOSE and LOCK Financial Year {{ $selectedYear }}?')">
                        <i class="bi bi-lock"></i> Close & Lock Year
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Year Selection Card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.dividends.reconciliation.index') }}" method="GET" class="row align-items-center g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Select Financial Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Financial Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-8 text-md-end mt-4">
                    <a href="{{ route('admin.dividends.reconciliation.show', $selectedYear) }}" class="btn btn-info text-white me-2">
                        <i class="bi bi-file-earmark-text"></i> View Finalized Report
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Performance Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body">
                    <span class="text-white-50 text-uppercase small font-weight-bold">Gross Sharable Profit</span>
                    <h3 class="mb-0 mt-2">₦{{ number_format($preview['gross_sharable_profit'], 2) }}</h3>
                    <small class="text-white-50">Business: ₦{{ number_format($preview['total_business_profit'], 2) }} | Financing: ₦{{ number_format($preview['total_financing_profit'], 2) }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white h-100">
                <div class="card-body">
                    <span class="text-white-50 text-uppercase small font-weight-bold">Recognized Losses</span>
                    <h3 class="mb-0 mt-2">₦{{ number_format($preview['total_recognized_loss'], 2) }}</h3>
                    <small class="text-white-50">Carried Loss: ₦{{ number_format($preview['previous_carried_loss'], 2) }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark h-100">
                <div class="card-body">
                    <span class="text-muted text-uppercase small font-weight-bold">Adjusted Member Pool (90%)</span>
                    <h3 class="mb-0 mt-2">₦{{ number_format($preview['adjusted_member_pool'], 2) }}</h3>
                    <small class="text-muted">Net Sharable: ₦{{ number_format($preview['net_sharable_profit'], 2) }} (10% Coop/Mgmt deducted)</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white h-100">
                <div class="card-body">
                    <span class="text-white-50 text-uppercase small font-weight-bold">Distributed Dividends</span>
                    <h3 class="mb-0 mt-2">₦{{ number_format($preview['total_distributed'], 2) }}</h3>
                    <small class="text-white-50">Loss Adjustment Required: ₦{{ number_format($preview['total_loss_adjustment'], 2) }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Adjustment Preview Table -->
    <div class="card shadow mb-4">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-people"></i> Member Dividend Loss Adjustments Preview (FY {{ $selectedYear }})</h6>
            @if(!$isClosed)
                <form action="{{ route('admin.dividends.reconciliation.process', $selectedYear) }}" method="POST" onsubmit="return confirm('Process and finalize Year-End Dividend & Loss Reconciliation for {{ $selectedYear }}?')">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle"></i> Finalize & Post Reconciliation
                    </button>
                </form>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Member Code</th>
                            <th>Member Name</th>
                            <th class="text-end">Original Entitlement</th>
                            <th class="text-end">Loss Adjustment</th>
                            <th class="text-end">Final Entitlement</th>
                            <th class="text-end">Amount Paid</th>
                            <th class="text-end">Overpaid / Recoverable</th>
                            <th class="text-end">Outstanding Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($preview['member_adjustments'] as $adj)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $adj['member_code'] }}</span></td>
                                <td class="fw-bold">{{ $adj['user_name'] }}</td>
                                <td class="text-end">₦{{ number_format($adj['original_dividend_amount'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">-₦{{ number_format($adj['loss_adjustment_amount'], 2) }}</td>
                                <td class="text-end text-success fw-bold">₦{{ number_format($adj['final_entitlement_amount'], 2) }}</td>
                                <td class="text-end">₦{{ number_format($adj['amount_already_paid'], 2) }}</td>
                                <td class="text-end text-danger">
                                    @if($adj['overpayment_amount'] > 0)
                                        ₦{{ number_format($adj['overpayment_amount'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end text-primary">
                                    @if($adj['underpayment_amount'] > 0)
                                        ₦{{ number_format($adj['underpayment_amount'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No dividend distributions recorded for Financial Year {{ $selectedYear }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
