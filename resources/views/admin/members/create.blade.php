@extends('layouts.app')

@section('page-title')
    <div class="d-flex justify-content-between align-items-center w-100">
        <h4 class="mb-0"><i class="bi bi-person-plus"></i> Add New Member</h4>
        <a href="{{ route('admin.members.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-people"></i> View Members
        </a>
    </div>
@endsection

@section('content')
<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.members.store') }}">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">System Role <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="member" {{ old('role') === 'member' || !old('role') ? 'selected' : '' }}>Member</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin - Full Privilege</option>
                        <option value="chairman" {{ old('role') === 'chairman' ? 'selected' : '' }}>Chairman - View members & financial records</option>
                        <option value="secretary" {{ old('role') === 'secretary' ? 'selected' : '' }}>Secretary - Edit members & view financial records</option>
                        <option value="treasurer" {{ old('role') === 'treasurer' ? 'selected' : '' }}>Treasurer - View members & financial records</option>
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3" id="memberOnlyFieldsRow">
                <div class="col-md-2">
                    <label class="form-label">Slots (1-10) <span class="text-danger">*</span></label>
                    <input type="number" name="slots" id="slotsInput" class="form-control @error('slots') is-invalid @enderror" value="{{ old('slots', 1) }}" min="1" max="10" required>
                    <small class="text-muted">₦2,000/slot/mo</small>
                    @error('slots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Registration Month <span class="text-danger">*</span></label>
                    <select name="registration_month" id="regMonthInput" class="form-select @error('registration_month') is-invalid @enderror" required>
                        @foreach([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ] as $mNum => $mName)
                            <option value="{{ $mNum }}" {{ (int)old('registration_month', date('n')) === $mNum ? 'selected' : '' }}>
                                {{ $mName }}
                            </option>
                        @endforeach
                    </select>
                    @error('registration_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Registration Year <span class="text-danger">*</span></label>
                    <input type="number" name="registration_year" id="regYearInput" class="form-control @error('registration_year') is-invalid @enderror" value="{{ old('registration_year', date('Y')) }}" min="2000" max="2100" required>
                    @error('registration_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Registration Fee (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="registration_fee" id="regFeeInput" class="form-control @error('registration_fee') is-invalid @enderror" value="{{ old('registration_fee', $defaultRegistrationFee ?? 1000) }}" required>
                    <small class="text-muted">Paid at once during registration</small>
                    @error('registration_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" id="paymentMethodInput" class="form-select @error('payment_method') is-invalid @enderror" required>
                        <option value="Pending" {{ old('payment_method') === 'Pending' ? 'selected' : '' }}>Pending (Unpaid)</option>
                        <option value="Cash" {{ old('payment_method') === 'Cash' || (!old('payment_method') && old('payment_method') !== 'Pending') ? 'selected' : '' }}>Cash</option>
                        <option value="Bank Transfer" {{ old('payment_method') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="Cheque" {{ old('payment_method') === 'Cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="Online" {{ old('payment_method') === 'Online' ? 'selected' : '' }}>Online</option>
                        <option value="Other" {{ old('payment_method') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div id="nextOfKinBlock">
                <hr class="my-4">
                <h5 class="mb-3 text-primary"><i class="bi bi-person-bounding-box"></i> Next of Kin Details</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Next of Kin Name <span class="text-danger">*</span></label>
                        <input type="text" name="nok_name" id="nokNameInput" class="form-control @error('nok_name') is-invalid @enderror" value="{{ old('nok_name') }}" required>
                        @error('nok_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Relationship <span class="text-danger">*</span></label>
                        <select name="nok_relationship" id="nokRelSelect" class="form-select @error('nok_relationship') is-invalid @enderror" required>
                            <option value="" disabled {{ old('nok_relationship') ? '' : 'selected' }}>Select Relationship</option>
                            @foreach(['Son', 'Daughter', 'Father', 'Mother', 'Sister', 'Brother', 'Spouse', 'Sibling', 'Aunty', 'Uncle'] as $rel)
                                <option value="{{ $rel }}" {{ old('nok_relationship') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                            @endforeach
                        </select>
                        @error('nok_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="nok_phone" id="nokPhoneInput" class="form-control @error('nok_phone') is-invalid @enderror" value="{{ old('nok_phone') }}" required>
                        @error('nok_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Contact Address</label>
                        <textarea name="nok_address" class="form-control @error('nok_address') is-invalid @enderror" rows="2">{{ old('nok_address') }}</textarea>
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
                        <i class="bi bi-save"></i> Add User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('roleSelect');
        const memberOnlyRow = document.getElementById('memberOnlyFieldsRow');
        const nextOfKinBlock = document.getElementById('nextOfKinBlock');

        const inputsToToggle = [
            document.getElementById('slotsInput'),
            document.getElementById('regYearInput'),
            document.getElementById('regFeeInput'),
            document.getElementById('paymentMethodInput'),
            document.getElementById('nokNameInput'),
            document.getElementById('nokRelSelect'),
            document.getElementById('nokPhoneInput')
        ];

        function toggleFields() {
            const isMember = roleSelect.value === 'member';
            if (isMember) {
                memberOnlyRow.style.display = 'flex';
                nextOfKinBlock.style.display = 'block';
                inputsToToggle.forEach(input => {
                    if (input) input.setAttribute('required', 'required');
                });
            } else {
                memberOnlyRow.style.display = 'none';
                nextOfKinBlock.style.display = 'none';
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