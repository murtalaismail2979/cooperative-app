@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-piggy-bank"></i> My Savings</h4>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Savings History</h6>
                <span class="badge bg-primary fs-6">
                    Total: ₦{{ number_format($totalSavings, 2) }}
                </span>
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