@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-gift"></i> My Dividends</h4>
@endsection

@section('content')
@if($year || $month)
<div class="card shadow mb-4 border-left-primary bg-light">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-1 fw-bold text-primary"><i class="bi bi-calculator me-1 fs-5"></i> Total Dividend @if($year) for Year {{ $year }}@endif @if($month)({{ $months[$month] ?? '' }})@endif</h6>
            <span class="text-muted small">Showing total dividend earnings and entitlement for selected filter</span>
        </div>
        <div class="text-end">
            <small class="text-muted d-block text-uppercase fw-bold">Total Selected Dividend</small>
            <span class="fs-4 fw-bold text-success">₦{{ number_format($filteredTotalDividend, 2) }}</span>
        </div>
    </div>
</div>
@endif

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow border-0 bg-primary text-white">
            <div class="card-body">
                <small class="text-white-50 text-uppercase font-weight-bold">
                    {{ $year ? "Original Dividends ($year)" : "Original Dividends" }}
                </small>
                <h4 class="mb-0 mt-2">₦{{ number_format(($year || $month) ? $filteredTotalDividend : ($summary['gross_original'] ?? $summary['total_amount'] ?? $totalDividends), 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow border-0 bg-danger text-white">
            <div class="card-body">
                <small class="text-white-50 text-uppercase font-weight-bold">
                    {{ $year ? "Loss Adjustments ($year)" : "Loss Adjustments" }}
                </small>
                <h4 class="mb-0 mt-2">-₦{{ number_format(($year || $month) ? $selectedYearLoss : ($summary['total_loss_adjustment'] ?? 0), 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow border-0 bg-success text-white">
            <div class="card-body">
                <small class="text-white-50 text-uppercase font-weight-bold">
                    {{ $year ? "Final Entitlement ($year)" : "Final Entitlement" }}
                </small>
                <h4 class="mb-0 mt-2">₦{{ number_format(($year || $month) ? $selectedYearFinal : ($summary['final_entitlement'] ?? $totalDividends), 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow border-0 bg-info text-white">
            <div class="card-body">
                <small class="text-white-50 text-uppercase font-weight-bold">
                    {{ $year ? "Total Paid ($year)" : "Total Paid" }}
                </small>
                <h4 class="mb-0 mt-2">₦{{ number_format(($year || $month) ? $filteredTotalPaid : ($summary['total_paid'] ?? 0), 2) }}</h4>
            </div>
        </div>
    </div>
</div>

@if(isset($adjustments) && $adjustments->count() > 0)
<div class="card shadow mb-4 border-left-warning">
    <div class="card-header py-3 bg-light">
        <h6 class="m-0 font-weight-bold text-warning"><i class="bi bi-exclamation-triangle"></i> Annual Loss & Dividend Adjustments</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Financial Year</th>
                        <th class="text-end">Original Entitlement</th>
                        <th class="text-end">Loss Adjustment</th>
                        <th class="text-end">Final Entitlement</th>
                        <th class="text-end">Overpayment / Recovery</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adjustments as $adj)
                        <tr>
                            <td><strong>FY {{ $adj->year }}</strong></td>
                            <td class="text-end">₦{{ number_format($adj->original_dividend_amount, 2) }}</td>
                            <td class="text-end text-danger">-₦{{ number_format($adj->loss_adjustment_amount, 2) }}</td>
                            <td class="text-end text-success fw-bold">₦{{ number_format($adj->final_entitlement_amount, 2) }}</td>
                            <td class="text-end text-danger">
                                @if($adj->overpayment_amount > 0)
                                    ₦{{ number_format($adj->overpayment_amount, 2) }} (Recovered: ₦{{ number_format($adj->amount_recovered, 2) }})
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $adj->status === 'settled' || $adj->status === 'fully_recovered' ? 'success' : 'warning' }}">
                                    {{ strtoupper($adj->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-list-stars me-1"></i> Dividend Payout Details</h6>
                <form method="GET" action="{{ route('member.dividends') }}" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                    <div class="col-auto">
                        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Months</option>
                            @foreach($months as $mNum => $mName)
                                <option value="{{ $mNum }}" {{ (string)$month === (string)$mNum ? 'selected' : '' }}>{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            @foreach($years as $yr)
                                <option value="{{ $yr }}" {{ (string)$year === (string)$yr ? 'selected' : '' }}>Year {{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    </div>
                    @if(!empty($month) || !empty($year))
                    <div class="col-auto">
                        <a href="{{ route('member.dividends') }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i> Clear</a>
                    </div>
                    @endif
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Business</th>
                                <th>Start Date</th>
                                <th>Units</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Paid Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payouts as $index => $payout)
                                <tr>
                                    <td>{{ $payouts->firstItem() + $index }}</td>
                                    <td>{{ $payout->dividend->investment->name ?? 'N/A (Year ' . ($payout->dividend->year ?? 'N/A') . ')' }}</td>
                                    <td>{{ $payout->dividend->investment?->start_date?->format('d/m/Y') ?? 'N/A' }}</td>
                                    <td>{{ $payout->units }}</td>
                                    <td>
                                        @if($payout->amount < 0)
                                            <span class="text-danger fw-bold">₦{{ number_format($payout->amount, 2) }} <span class="badge bg-danger ms-1">Loss</span></span>
                                        @else
                                            <span class="text-success fw-bold">₦{{ number_format($payout->amount, 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payout->paid)
                                            <span class="badge bg-success">Paid</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $payout->paid_date ? \Carbon\Carbon::parse($payout->paid_date)->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No dividend records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center">
                    {{ $payouts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection