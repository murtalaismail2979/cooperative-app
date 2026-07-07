@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-gift"></i> My Dividends</h4>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Dividend Payouts</h6>
                <span class="badge bg-success fs-6">
                    Total: ₦{{ number_format($totalDividends, 2) }}
                </span>
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
                                    <td>₦{{ number_format($payout->amount, 2) }}</td>
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
                                    <td colspan="6" class="text-center text-muted">No dividend records found.</td>
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