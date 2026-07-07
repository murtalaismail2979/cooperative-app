@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-piggy-bank"></i> Savings Management</h4>@endsection
@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Monthly Savings Overview - {{ $currentMonth->format('F Y') }}</span>
        <a href="{{ route('admin.savings.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Record Savings</a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.savings.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-auto">
                <label class="form-label mb-0 fw-bold">Select Month:</label>
            </div>
            <div class="col-auto">
                <input type="month" name="month" class="form-control" value="{{ $currentMonth->format('Y-m') }}" onchange="this.form.submit()">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.savings.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead><tr><th>Member</th><th>Code</th><th>Slots</th><th>Est. Monthly</th><th>Paid in {{ $currentMonth->format('M Y') }}?</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                    @foreach($members as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td><span class="badge bg-primary">{{ $member->member_code }}</span></td>
                        <td>{{ $member->savingsSlots->where('is_active', true)->count() }}/10</td>
                        <td>₦{{ number_format($member->savingsSlots->where('is_active', true)->count() * 2000, 2) }}</td>
                        <td>
                            @php
                                $paid = \App\Models\MonthlySaving::where('user_id', $member->id)
                                    ->whereYear('month', $currentMonth->year)
                                    ->whereMonth('month', $currentMonth->month)
                                    ->where('status', 'paid')->count();
                            @endphp
                            @if($paid >= $member->savingsSlots->where('is_active', true)->count() && $member->savingsSlots->where('is_active', true)->count() > 0)
                                <span class="badge bg-success">Complete</span>
                            @elseif($paid > 0)
                                <span class="badge bg-warning text-dark">{{ $paid }}/{{ $member->savingsSlots->where('is_active', true)->count() }}</span>
                            @else
                                <span class="badge bg-danger">Not Paid</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-gear"></i> Adjust Slots
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('admin.savings.history') }}" class="btn btn-info"><i class="bi bi-clock-history"></i> View Full History</a>
    </div>
</div>
@endsection