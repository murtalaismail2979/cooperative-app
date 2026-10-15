@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-piggy-bank"></i> My Savings</h4>
@endsection

@section('content')
@php
    $monthRows = [
        [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun'],
        [7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec']
    ];
@endphp

<!-- Jan - Dec Savings Status Quick View Card -->
<div class="card shadow mb-4 border-0" style="border-radius: 10px;">
    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <h6 class="m-0 font-weight-bold text-primary text-uppercase">
            <i class="bi bi-calendar3-check me-1"></i> JAN - DEC SAVINGS STATUS ({{ $quickViewYear }})
        </h6>
        <div class="d-flex align-items-center gap-2">
            <form method="GET" action="{{ route('member.savings') }}" class="d-flex align-items-center gap-2 mb-0">
                <label class="small text-muted me-1 mb-0 d-none d-sm-inline">Select Year:</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ (string)$quickViewYear === (string)$yr ? 'selected' : '' }}>Year {{ $yr }}</option>
                    @endforeach
                </select>
                @if(!empty($selectedYear))
                    <a href="{{ route('member.savings') }}" class="btn btn-outline-secondary btn-sm" title="Reset to all years">
                        <i class="bi bi-x-circle"></i> All Years
                    </a>
                @endif
            </form>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center gy-4">
            <!-- 12-Month Status Badges -->
            <div class="col-lg-7 col-xl-6">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="small fw-semibold text-muted text-uppercase mb-2">Month-by-Month Savings Tracker</div>
                    @foreach($monthRows as $rowIndex => $row)
                        <div class="d-flex gap-2 align-items-center flex-wrap {{ $rowIndex === 0 ? 'mb-2' : '' }}">
                            @foreach($row as $mNum => $mName)
                                @php
                                    $mStatus = $quickViewStatus[$mNum] ?? ['is_paid' => false, 'amount' => 0, 'payment_date' => null];
                                    $isPaid = $mStatus['is_paid'];
                                @endphp
                                @if($isPaid)
                                    <span class="badge bg-success d-inline-flex align-items-center gap-1 py-2 px-2 shadow-sm" 
                                          style="min-width: 54px; justify-content: center; font-size: 0.8rem; border-radius: 6px;"
                                          title="{{ $mName }} {{ $quickViewYear }}: Paid ₦{{ number_format($mStatus['amount'], 2) }}{{ $mStatus['payment_date'] ? ' on ' . $mStatus['payment_date'] : '' }}">
                                        <i class="bi bi-check-circle-fill"></i> {{ $mName }}
                                    </span>
                                @elseif(!empty($mStatus['is_not_applicable']))
                                    @php
                                        $reason = $mStatus['reason'] ?? ((int)$quickViewYear === 2021 ? 'Before Savings Inception (Started Oct 2021)' : 'Not Applicable (Savings held Jan–Apr only in 2022)');
                                    @endphp
                                    <span class="badge bg-secondary d-inline-flex align-items-center gap-1 py-2 px-2 shadow-sm opacity-50" 
                                          style="min-width: 54px; justify-content: center; font-size: 0.8rem; border-radius: 6px;"
                                          title="{{ $mName }} {{ $quickViewYear }}: {{ $reason }}">
                                        <i class="bi bi-dash-circle"></i> {{ $mName }}
                                    </span>
                                @else
                                    <span class="badge bg-danger d-inline-flex align-items-center gap-1 py-2 px-2 shadow-sm" 
                                          style="min-width: 54px; justify-content: center; font-size: 0.8rem; border-radius: 6px;"
                                          title="{{ $mName }} {{ $quickViewYear }}: Not Paid">
                                        <i class="bi bi-x-circle-fill"></i> {{ $mName }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                    <div class="d-flex gap-3 mt-3 pt-2 border-top small text-muted flex-wrap">
                        <span><i class="bi bi-check-circle-fill text-success"></i> Paid</span>
                        <span><i class="bi bi-x-circle-fill text-danger"></i> Not Paid / Pending</span>
                        @if((int)$quickViewYear === 2021)
                            <span><i class="bi bi-dash-circle text-secondary"></i> Before Inception (Pre-Oct 2021)</span>
                        @elseif((int)$quickViewYear === 2022)
                            <span><i class="bi bi-dash-circle text-secondary"></i> Not Applicable (May–Dec 2022)</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Metrics Summary -->
            <div class="col-lg-5 col-xl-6">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 h-100 bg-white shadow-sm">
                            <div class="text-muted small fw-semibold text-uppercase">Months Paid in {{ $quickViewYear }}</div>
                            <div class="fs-4 fw-bold text-dark mt-1">{{ $quickViewPaidCount }} <span class="text-muted fs-6">/ {{ $quickViewPossibleMonths ?? 12 }}</span></div>
                            @php $pct = round(($quickViewPaidCount / max(1, $quickViewPossibleMonths ?? 12)) * 100); @endphp
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">{{ $pct }}% completed</small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 h-100 bg-white shadow-sm">
                            <div class="text-muted small fw-semibold text-uppercase">Total Saved in {{ $quickViewYear }}</div>
                            <div class="fs-4 fw-bold text-success mt-1">₦{{ number_format($quickViewTotalAmount, 2) }}</div>
                            <small class="text-muted mt-1 d-block">Lifetime: <strong>₦{{ number_format($totalSavings, 2) }}</strong></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($yearlyOverview) && count($yearlyOverview) > 1)
        <!-- Multi-Year Quick View Breakdown Table -->
        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="bi bi-layers me-1 text-primary"></i> Multi-Year Savings Status Comparison
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Year</th>
                            <th style="width: 130px;">Months Paid</th>
                            <th style="width: 150px;">Total Saved</th>
                            <th style="min-width: 360px;">Jan - Dec Savings Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($yearlyOverview as $yrData)
                        @php $possibleYrMonths = $yrData['possible_months'] ?? 12; @endphp
                        <tr class="{{ (int)$yrData['year'] === (int)$quickViewYear ? 'table-primary bg-opacity-10' : '' }}">
                            <td>
                                <strong>{{ $yrData['year'] }}</strong>
                                @if((int)$yrData['year'] === (int)$quickViewYear)
                                    <span class="badge bg-primary ms-1">Selected</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $yrData['paid_count'] === $possibleYrMonths ? 'success' : ($yrData['paid_count'] > 0 ? 'info' : 'secondary') }}">
                                    {{ $yrData['paid_count'] }} / {{ $possibleYrMonths }}
                                </span>
                            </td>
                            <td class="fw-semibold">₦{{ number_format($yrData['total_amount'], 2) }}</td>
                            <td>
                                <div class="py-1">
                                    @foreach($monthRows as $rowIndex => $row)
                                        <div class="d-flex gap-1 align-items-center {{ $rowIndex === 0 ? 'mb-1' : '' }}">
                                            @foreach($row as $mNum => $mName)
                                                @php
                                                    $mStatus = $yrData['months'][$mNum] ?? ['is_paid' => false, 'amount' => 0];
                                                    $isPaid = $mStatus['is_paid'];
                                                @endphp
                                                @if($isPaid)
                                                    <span class="badge bg-success d-inline-flex align-items-center gap-1 py-1 px-1" 
                                                          style="min-width: 46px; justify-content: center; font-size: 0.72rem; border-radius: 4px;" 
                                                          title="{{ $mName }} {{ $yrData['year'] }}: Paid (₦{{ number_format($mStatus['amount'], 2) }})">
                                                        <i class="bi bi-check-circle-fill"></i> {{ $mName }}
                                                    </span>
                                                @elseif(!empty($mStatus['is_not_applicable']))
                                                    @php
                                                        $yrReason = (int)$yrData['year'] === 2021 ? 'Before Savings Inception (Started Oct 2021)' : 'Not Applicable (Savings Jan–Apr only)';
                                                    @endphp
                                                    <span class="badge bg-secondary d-inline-flex align-items-center gap-1 py-1 px-1 opacity-50" 
                                                          style="min-width: 46px; justify-content: center; font-size: 0.72rem; border-radius: 4px;" 
                                                          title="{{ $mName }} {{ $yrData['year'] }}: {{ $yrReason }}">
                                                        <i class="bi bi-dash-circle"></i> {{ $mName }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger d-inline-flex align-items-center gap-1 py-1 px-1" 
                                                          style="min-width: 46px; justify-content: center; font-size: 0.72rem; border-radius: 4px;" 
                                                          title="{{ $mName }} {{ $yrData['year'] }}: Not Paid">
                                                        <i class="bi bi-x-circle-fill"></i> {{ $mName }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-clock-history me-1"></i> Detailed Transaction History</h6>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="badge bg-primary fs-6">
                        Lifetime Savings: ₦{{ number_format($totalSavings, 2) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Month</th>
                                <th>Slots Paid</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($savings as $index => $saving)
                                <tr>
                                    <td>{{ $savings->firstItem() + $index }}</td>
                                    <td>{{ \Carbon\Carbon::parse($saving->month)->format('F Y') }}</td>
                                    <td>{{ $saving->slots_count }} {{ Str::plural('Slot', $saving->slots_count) }}</td>
                                    <td>₦{{ number_format($saving->total_amount, 2) }}</td>
                                    <td>
                                        @if(($saving->status ?? 'paid') === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                    <td>{{ $saving->latest_payment_date ? \Carbon\Carbon::parse($saving->latest_payment_date)->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No savings records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center">
                    {{ $savings->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection