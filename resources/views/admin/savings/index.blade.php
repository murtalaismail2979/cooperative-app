@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-piggy-bank"></i> Savings Management</h4>@endsection
@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-calendar-check me-1"></i> Monthly Savings Checklist Overview — Year {{ $selectedYear }}</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.savings.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Record Savings</a>
            <a href="{{ route('admin.batch-upload.index', ['type' => 'savings']) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-cloud-arrow-up"></i> Batch Upload Savings
            </a>
            <a href="{{ route('admin.savings.history') }}" class="btn btn-info btn-sm text-white">
                <i class="bi bi-clock-history"></i> View Full History
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.savings.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-auto">
                <label class="form-label mb-0 fw-bold">Select Financial Year:</label>
            </div>
            <div class="col-auto">
                <select name="year" class="form-select auto-search" onchange="this.form.submit()">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (int)$selectedYear === (int)$yr ? 'selected' : '' }}>Year {{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            @if((int)$selectedYear !== (int)date('Y'))
            <div class="col-auto">
                <a href="{{ route('admin.savings.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Reset to Current Year</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light text-primary">
                    <tr>
                        <th>Member</th>
                        <th>Code</th>
                        <th>Slots</th>
                        <th>Est. Monthly</th>
                        <th style="width: 340px;">Jan - Dec Savings Status ({{ $selectedYear }})</th>
                        <th class="text-center" style="white-space: nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $monthRows = [
                            [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun'],
                            [7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec']
                        ];
                    @endphp
                    @foreach($members as $member)
                    @php
                        $memberSavings = $savingsData->get($member->id);
                    @endphp
                    <tr class="{{ !$member->is_active ? 'table-secondary opacity-75' : '' }}">
                        <td>
                            <strong>{{ $member->name }}</strong>
                            @if(!$member->is_active)
                                <span class="badge bg-secondary ms-1">Inactive</span>
                            @endif
                        </td>
                        <td><span class="badge bg-primary">{{ $member->member_code }}</span></td>
                        <td>{{ $member->savingsSlots->where('is_active', true)->count() }}/10</td>
                        <td>₦{{ number_format($member->savingsSlots->where('is_active', true)->count() * 2000, 2) }}</td>
                        <td>
                            <div class="py-1">
                                @foreach($monthRows as $rowIndex => $row)
                                    <div class="d-flex gap-1 align-items-center {{ $rowIndex === 0 ? 'mb-2' : '' }}">
                                        @foreach($row as $mNum => $mName)
                                            @php
                                                $rec = $memberSavings ? $memberSavings->firstWhere('month_num', $mNum) : null;
                                                $isPaid = $rec && (float)$rec->total_amount > 0;
                                            @endphp
                                            @if($isPaid)
                                                <span class="badge bg-success d-inline-flex align-items-center gap-1 py-1 px-1" style="min-width: 48px; justify-content: center; font-size: 0.72rem;" title="{{ $mName }} {{ $selectedYear }}: Paid (₦{{ number_format($rec->total_amount, 2) }})">
                                                    <i class="bi bi-check-circle-fill"></i> {{ $mName }}
                                                </span>
                                            @else
                                                <span class="badge bg-danger d-inline-flex align-items-center gap-1 py-1 px-1" style="min-width: 48px; justify-content: center; font-size: 0.72rem;" title="{{ $mName }} {{ $selectedYear }}: Not Paid">
                                                    <i class="bi bi-x-circle-fill"></i> {{ $mName }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                @if(!$member->is_active)
                                    <button class="btn btn-sm btn-secondary disabled" disabled title="Member is Inactive">
                                        <i class="bi bi-slash-circle"></i> Record
                                    </button>
                                @else
                                    <a href="{{ route('admin.savings.create', ['member_id' => $member->id]) }}" class="btn btn-sm btn-outline-success" title="Record Savings">
                                        <i class="bi bi-plus-circle"></i> Record
                                    </a>
                                @endif
                                <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-sm btn-outline-primary" title="Adjust Slots">
                                    <i class="bi bi-gear"></i> Slots
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection