@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center w-100">
        <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Member: {{ $member->name }}</h4>
        <div class="d-flex gap-2">
            @if($member->isMember())
            <a href="{{ route('admin.loans.create', ['user_id' => $member->id]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-cash-stack me-1"></i> Grant New Financing
            </a>
            @endif
            <a href="{{ route('admin.members.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people"></i> View Members
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.members.update', $member) }}">
            @csrf
            @method('PATCH')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Member Code</label>
                    <input type="text" class="form-control" value="{{ $member->member_code }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $member->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $member->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $member->phone) }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Contact Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $member->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">System Role <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="member" {{ old('role', $member->role) === 'member' ? 'selected' : '' }}>Member</option>
                        <option value="admin" {{ old('role', $member->role) === 'admin' ? 'selected' : '' }}>Admin - Full Privilege</option>
                        <option value="chairman" {{ old('role', $member->role) === 'chairman' ? 'selected' : '' }}>Chairman - View members & financial records</option>
                        <option value="secretary" {{ old('role', $member->role) === 'secretary' ? 'selected' : '' }}>Secretary - Edit members & view financial records</option>
                        <option value="treasurer" {{ old('role', $member->role) === 'treasurer' ? 'selected' : '' }}>Treasurer - View members & financial records</option>
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ $member->is_active ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !$member->is_active ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="memberOnlyFieldsRow">
                <div class="col-md-4">
                    <label class="form-label">Registration Month <span class="text-danger">*</span></label>
                    <select name="registration_month" id="regMonthInput" class="form-select @error('registration_month') is-invalid @enderror" required>
                        @foreach([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ] as $mNum => $mName)
                            <option value="{{ $mNum }}" {{ (int)old('registration_month', $member->registration_month ?? date('n')) === $mNum ? 'selected' : '' }}>
                                {{ $mName }}
                            </option>
                        @endforeach
                    </select>
                    @error('registration_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Registration Year <span class="text-danger">*</span></label>
                    <input type="number" name="registration_year" id="regYearInput" class="form-control @error('registration_year') is-invalid @enderror" value="{{ old('registration_year', $member->registration_year ?? date('Y')) }}" min="2000" max="2100" required>
                    @error('registration_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Number of Slots (1-10) <span class="text-danger">*</span></label>
                    <select name="slots" id="slotsSelect" class="form-select @error('slots') is-invalid @enderror" required>
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ old('slots', $member->savingsSlots()->where('is_active', true)->count()) == $i ? 'selected' : '' }}>
                                {{ $i }} {{ Str::plural('Slot', $i) }} (₦{{ number_format($i * 2000) }}/month)
                            </option>
                        @endfor
                    </select>
                    @error('slots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3" id="slotChangeDateRow">
                <div class="col-md-6">
                    <label class="form-label">Slot Change Effective Date</label>
                    <input type="date" name="slot_change_date" id="slotChangeDateInput" class="form-control @error('slot_change_date') is-invalid @enderror" value="{{ old('slot_change_date', date('Y-m-d')) }}">
                    @error('slot_change_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Specify the date when this slot adjustment takes effect (only logged if the number of slots is changed).</small>
                </div>
            </div>

            <div id="nextOfKinBlock">
                <hr class="my-4">
                <h5 class="mb-3 text-primary"><i class="bi bi-person-bounding-box"></i> Next of Kin Details</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Next of Kin Name <span class="text-danger">*</span></label>
                        <input type="text" name="nok_name" id="nokNameInput" class="form-control @error('nok_name') is-invalid @enderror" value="{{ old('nok_name', $member->nextOfKin->name ?? '') }}" required>
                        @error('nok_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Relationship <span class="text-danger">*</span></label>
                        @php
                            $currentRelationship = old('nok_relationship', $member->nextOfKin->relationship ?? '');
                            $predefinedRelationships = ['Son', 'Daughter', 'Father', 'Mother', 'Sister', 'Brother', 'Spouse', 'Sibling', 'Aunty', 'Uncle'];
                            if ($currentRelationship && !in_array($currentRelationship, $predefinedRelationships)) {
                                $predefinedRelationships[] = $currentRelationship;
                            }
                        @endphp
                        <select name="nok_relationship" id="nokRelSelect" class="form-select @error('nok_relationship') is-invalid @enderror" required>
                            <option value="" disabled {{ !$currentRelationship ? 'selected' : '' }}>Select Relationship</option>
                            @foreach($predefinedRelationships as $rel)
                                <option value="{{ $rel }}" {{ $currentRelationship == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                            @endforeach
                        </select>
                        @error('nok_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="nok_phone" id="nokPhoneInput" class="form-control @error('nok_phone') is-invalid @enderror" value="{{ old('nok_phone', $member->nextOfKin->phone ?? '') }}" required>
                        @error('nok_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Contact Address</label>
                        <textarea name="nok_address" class="form-control @error('nok_address') is-invalid @enderror" rows="2">{{ old('nok_address', $member->nextOfKin->address ?? '') }}</textarea>
                        @error('nok_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.members.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-people"></i> View Members
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mt-4 mb-4" id="historyBlock">
    <div class="card-header"><h5 class="mb-0 text-primary"><i class="bi bi-clock-history"></i> Savings Slot Registration History</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('roleSelect');
        const memberOnlyRow = document.getElementById('memberOnlyFieldsRow');
        const slotChangeDateRow = document.getElementById('slotChangeDateRow');
        const nextOfKinBlock = document.getElementById('nextOfKinBlock');
        const historyBlock = document.getElementById('historyBlock');

        const inputsToToggle = [
            document.getElementById('regYearInput'),
            document.getElementById('slotsSelect'),
            document.getElementById('nokNameInput'),
            document.getElementById('nokRelSelect'),
            document.getElementById('nokPhoneInput')
        ];

        function toggleFields() {
            const isMember = roleSelect.value === 'member';
            if (isMember) {
                memberOnlyRow.style.display = 'flex';
                if (slotChangeDateRow) slotChangeDateRow.style.display = 'flex';
                if (nextOfKinBlock) nextOfKinBlock.style.display = 'block';
                if (historyBlock) historyBlock.style.display = 'block';
                inputsToToggle.forEach(input => {
                    if (input) input.setAttribute('required', 'required');
                });
            } else {
                memberOnlyRow.style.display = 'none';
                if (slotChangeDateRow) slotChangeDateRow.style.display = 'none';
                if (nextOfKinBlock) nextOfKinBlock.style.display = 'none';
                if (historyBlock) historyBlock.style.display = 'none';
                inputsToToggle.forEach(input => {
                    if (input) input.removeAttribute('required');
                });
            }
        }

        roleSelect.addEventListener('change', toggleFields);
        toggleFields(); // Init on load
    });
</script>
@endpush
@endsection