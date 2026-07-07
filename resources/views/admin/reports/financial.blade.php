@extends('layouts.app')
@section('page-title')
<div class="d-flex justify-content-between align-items-center w-100">
    <h4 class="mb-0"><i class="bi bi-file-earmark-bar-graph"></i> Financial Report</h4>
    <a href="{{ route('admin.reports.all.export') }}" class="btn btn-sm btn-success shadow-sm"><i class="bi bi-file-earmark-zip"></i> Download All Reports (ZIP/Excel)</a>
</div>
@endsection
@section('content')
<div class="row mb-4">
    <div class="col-md-4"><div class="card bg-success text-white shadow"><div class="card-body"><strong>Investment Returns:</strong> ₦{{ number_format($investmentReturns,2) }}</div></div></div>
    <div class="col-md-4"><div class="card bg-primary text-white shadow"><div class="card-body"><strong>Financing Profit:</strong> ₦{{ number_format($totalLoanProfit,2) }}</div></div></div>
    <div class="col-md-4"><div class="card bg-info text-white shadow"><div class="card-body"><strong>Total Revenue:</strong> ₦{{ number_format($totalRevenue,2) }}</div></div></div>
</div>
<div class="row mb-4">
    <div class="col-md-6"><div class="card bg-danger text-white shadow"><div class="card-body"><strong>Total Expenses:</strong> ₦{{ number_format($totalExpenses,2) }}</div></div></div>
    <div class="col-md-6"><div class="card bg-{{ $netIncome >= 0 ? 'success' : 'danger' }} text-white shadow"><div class="card-body"><strong>Net Income:</strong> ₦{{ number_format($netIncome,2) }}</div></div></div>
</div>
<div class="row mb-4">
    <div class="col-md-3"><div class="card bg-secondary text-white shadow"><div class="card-body"><strong>Cooperative Deductions:</strong> ₦{{ number_format($cooperativeDeductions,2) }}</div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-white shadow"><div class="card-body"><strong>Management Deductions:</strong> ₦{{ number_format($managementDeductions,2) }}</div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-white shadow"><div class="card-body"><strong>Total Deducted:</strong> ₦{{ number_format($totalDeductions,2) }}</div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-white shadow"><div class="card-body"><strong>Total Member Dividends:</strong> ₦{{ number_format($totalMemberDividends,2) }}</div></div></div>
</div>
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary">Summary</h6>
        <a href="{{ route('admin.reports.financial.export') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
    <div class="card-body">
        <p>Total Revenue: ₦{{ number_format($totalRevenue,2) }} - Total Expenses: ₦{{ number_format($totalExpenses,2) }} = <strong>Net {{ $netIncome >= 0 ? 'Profit' : 'Loss' }}: ₦{{ number_format($netIncome,2) }}</strong></p>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar3"></i> Financing & Business Activity Breakdown</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle text-center">Year</th>
                        <th rowspan="2" class="align-middle text-center">Month</th>
                        <th colspan="3" class="text-center">Financing (Loans) Run</th>
                        <th colspan="3" class="text-center">Businesses (Investments) Run</th>
                    </tr>
                    <tr>
                        <th class="text-center">Count</th>
                        <th class="text-end">Principal Amount</th>
                        <th class="text-end">Projected Profit</th>
                        <th class="text-center">Count</th>
                        <th class="text-end">Capital Invested</th>
                        <th class="text-end">Sharable Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedPeriods as $period)
                    <tr>
                        <td class="fw-semibold text-center">{{ $period['year'] }}</td>
                        <td class="text-center">{{ date('F', mktime(0, 0, 0, $period['month'], 1)) }}</td>
                        <td class="text-center"><span class="badge bg-primary">{{ $period['loan_count'] }}</span></td>
                        <td class="text-end">₦{{ number_format($period['loan_principal'], 2) }}</td>
                        <td class="text-end text-success fw-semibold">₦{{ number_format($period['loan_profit'], 2) }}</td>
                        <td class="text-center"><span class="badge bg-info text-dark">{{ $period['investment_count'] }}</span></td>
                        <td class="text-end text-primary fw-semibold">₦{{ number_format($period['investment_capital'], 2) }}</td>
                        <td class="text-end text-success fw-semibold">₦{{ number_format($period['investment_profit'], 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No activity recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $paginatedPeriods->links() }}
        </div>
    </div>
</div>
@endsection