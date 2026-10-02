@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center w-100">
        <h4 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Manage Savings Slots: {{ $member->name }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.loans.create', ['user_id' => $member->id]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-cash-stack me-1"></i> Grant New Financing
            </a>
            <a href="{{ route('admin.members.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people"></i> View Members
            </a>
        </div>
    </div>
@endsection

@section('content')
@php
    $activeCount = $member->savingsSlots->where('is_active', true)->count();
    $activeSlotNum = $member->savingsSlots->where('is_active', true)->max('slot_number') ?? $activeCount;
@endphp

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">{{ $member->name }}</span>
                <span class="badge bg-primary">{{ $member->member_code }}</span>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.members.slots.update', $member) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="slots" class="form-label fw-bold">Active Savings Slots</label>
                        <select name="slots" id="slots" class="form-select @error('slots') is-invalid @enderror" required>
                            @for($slotCount = 1; $slotCount <= 10; $slotCount++)
                                <option value="{{ $slotCount }}" {{ old('slots', $activeCount) == $slotCount ? 'selected' : '' }}>
                                    {{ $slotCount }} {{ Str::plural('Slot', $slotCount) }} (₦{{ number_format($slotCount * 2000) }}/month)
                                </option>
                            @endfor
                        </select>
                        @error('slots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="slot_change_date" class="form-label fw-bold">Effective Date</label>
                        <input type="date" name="slot_change_date" id="slot_change_date" class="form-control @error('slot_change_date') is-invalid @enderror" value="{{ old('slot_change_date', date('Y-m-d')) }}">
                        @error('slot_change_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">The date used in the slot change history.</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.members.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Slot Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow h-100">
            <div class="card-header fw-bold text-primary"><i class="bi bi-grid-3x3-gap me-1"></i> Active Member Slot</div>
            <div class="card-body d-flex flex-column justify-content-center">
                @if($activeCount > 0)
                    <div class="border border-success bg-light rounded p-4 text-center my-auto">
                        <div class="text-uppercase fw-bold text-success small mb-1">Active Slot Level</div>
                        <div class="display-5 fw-bold text-success mb-2">Slot {{ $activeSlotNum }}</div>
                        <div class="badge bg-success fs-6 mb-3 px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i> {{ $activeCount }} {{ Str::plural('Slot', $activeCount) }} Active
                        </div>
                        <div class="text-muted fw-semibold fs-6">
                            Monthly Savings: <strong class="text-dark">₦{{ number_format($activeCount * 2000) }} / month</strong>
                        </div>
                    </div>
                @else
                    <div class="border rounded p-4 text-center text-muted my-auto">
                        <i class="bi bi-exclamation-circle fs-2 text-secondary d-block mb-2"></i>
                        No active savings slots assigned.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow mt-4">
    <div class="card-header fw-bold text-primary">
        <i class="bi bi-clock-history me-1"></i> Savings Slot Registration & Adjustment History
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time / Effective Date</th>
                        <th>Previous Slots</th>
                        <th>New Slots</th>
                        <th>Monthly Saving Amount</th>
                        <th>Changed By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($member->slotHistories->sortByDesc('created_at') as $history)
                        <tr>
                            <td>{{ $history->created_at->format('d/m/Y h:i A') }}</td>
                            <td><span class="badge bg-secondary">{{ $history->previous_slots }}</span></td>
                            <td><span class="badge bg-success">{{ $history->current_slots }}</span></td>
                            <td><strong>₦{{ number_format($history->current_slots * 2000, 2) }}</strong></td>
                            <td>{{ $history->changedBy->name ?? 'System' }}</td>
                            <td><span class="text-muted">{{ $history->reason ?? 'N/A' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No slot change history found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
