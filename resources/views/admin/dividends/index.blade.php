@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-gift"></i> Dividend Distributions</h4>@endsection
@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Dividends</span>
        <a href="{{ route('admin.dividends.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Distribute Dividends</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Business</th><th>Start Date</th><th>Total Amount</th><th>Total Units</th><th>Unit Profit (₦)</th><th>Distributed</th><th>By</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($dividends as $d)
            <tr>
                <td>{{ $d->investment->name ?? 'N/A (Year ' . $d->year . ')' }}</td>
                <td>{{ $d->investment?->start_date?->format('d/m/Y') ?? 'N/A' }}</td>
                <td>₦{{ number_format($d->total_dividend_amount,2) }}</td>
                <td>{{ number_format($d->total_units) }}</td>
                <td>₦{{ number_format($d->unit_value,2) }}</td>
                <td>{{ $d->distributed_at?->format('d/m/Y') }}</td>
                <td>{{ $d->distributor->name ?? 'N/A' }}</td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.dividends.show', $d) }}" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i></a>
                        <form method="POST" action="{{ route('admin.dividends.destroy', $d) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this dividend distribution?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">No dividends distributed</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $dividends->appends(request()->except('dividends_page'))->links() }}
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-people"></i> Member Dividends Summary</h6>
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
        <form method="GET" action="{{ route('admin.dividends.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-4 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="dividendMemberSearch" class="form-control border-start-0 auto-search" placeholder="Search member name or code..." value="{{ $search ?? '' }}">
                </div>
            </div>
            @if(!empty($search))
            <div class="col-auto">
                <a href="{{ route('admin.dividends.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
            </div>
            @endif
        </form>

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
            {{ $memberSummaries->appends(request()->except('members_page'))->links() }}
        </div>
    </div>
</div>
@endsection