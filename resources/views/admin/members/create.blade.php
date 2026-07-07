@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-person-plus"></i> Add New Member</h4>
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
                        <option value="treasurer" {{ old('role') === 'treasurer' ? 'selected' : '' }}>Treasurer</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3" id="memberOnlyFieldsRow">
                <div class="col-md-6">
                    <label class="form-label">Number of Savings Slots (1-10) <span class="text-danger">*</span></label>
                    <input type="number" name="slots" id="slotsInput" class="form-control @error('slots') is-invalid @enderror" value="{{ old('slots', 1) }}" min="1" max="10" required>
                    <small class="text-muted">Each slot = ₦2,000/month</small>
                    @error('slots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Registration Year <span class="text-danger">*</span></label>
                    <input type="number" name="registration_year" id="regYearInput" class="form-control @error('registration_year') is-invalid @enderror" value="{{ old('registration_year', date('Y')) }}" min="2000" max="2100" required>
                    @error('registration_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <div class="col-md-6">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="nok_phone" id="nokPhoneInput" class="form-control @error('nok_phone') is-invalid @enderror" value="{{ old('nok_phone') }}" required>
                        @error('nok_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="nok_email" class="form-control @error('nok_email') is-invalid @enderror" value="{{ old('nok_email') }}">
                        @error('nok_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Add User
                </button>
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