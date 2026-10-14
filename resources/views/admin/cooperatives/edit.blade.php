@extends('layouts.app')

@section('title', 'Edit Cooperative Settings')

@section('page-title')
    <h2 class="h4 mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i> Edit Cooperative Settings</h2>
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-gray-800 fw-bold">Edit {{ $cooperative->name }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.cooperatives.update', $cooperative) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label font-weight-bold">Cooperative Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $cooperative->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="slug" class="form-label font-weight-bold">Cooperative Code / Slug <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $cooperative->slug) }}" required>
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Official Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $cooperative->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" inputmode="tel" pattern="[\+]?[0-9\s\-\(\)]{7,20}" maxlength="20" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $cooperative->phone) }}" placeholder="e.g. +234 800 000 0000 or 08012345678">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $cooperative->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="state" class="form-label">State</label>
                    <select class="form-select @error('state') is-invalid @enderror" id="state" name="state">
                        <option value="">-- Select State --</option>
                        @foreach([
                            'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
                            'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT - Abuja', 'Gombe',
                            'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos',
                            'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto',
                            'Taraba', 'Yobe', 'Zamfara'
                        ] as $stateOption)
                            <option value="{{ $stateOption }}" {{ old('state', $cooperative->state) == $stateOption ? 'selected' : '' }}>{{ $stateOption }}</option>
                        @endforeach
                    </select>
                    @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="lga" class="form-label">LGA (Local Government Area)</label>
                    <select class="form-select @error('lga') is-invalid @enderror" id="lga" name="lga">
                        <option value="">-- Select State First --</option>
                    </select>
                    @error('lga') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-sliders me-1"></i> Financial & Slot Settings</h6>

                <div class="col-md-6">
                    <label for="base_slot_amount" class="form-label font-weight-bold">Base Slot Monthly Amount (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control @error('base_slot_amount') is-invalid @enderror" id="base_slot_amount" name="base_slot_amount" value="{{ old('base_slot_amount', $cooperative->base_slot_amount) }}" required>
                    @error('base_slot_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="dividend_allocation_basis" class="form-label font-weight-bold">Dividend Allocation Method <span class="text-danger">*</span></label>
                    <select class="form-select @error('dividend_allocation_basis') is-invalid @enderror" id="dividend_allocation_basis" name="dividend_allocation_basis" required>
                        <option value="savings_only" {{ old('dividend_allocation_basis', $cooperative->dividend_allocation_basis) == 'savings_only' ? 'selected' : '' }}>Savings Only (100% based on total savings pool)</option>
                        <option value="shares_only" {{ old('dividend_allocation_basis', $cooperative->dividend_allocation_basis) == 'shares_only' ? 'selected' : '' }}>Shares Only</option>
                        <option value="hybrid" {{ old('dividend_allocation_basis', $cooperative->dividend_allocation_basis) == 'hybrid' ? 'selected' : '' }}>Hybrid (Savings & Shares)</option>
                    </select>
                    @error('dividend_allocation_basis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="primary_color" class="form-label">Primary Theme Color</label>
                    <input type="color" class="form-control form-control-color w-100" id="primary_color" name="primary_color" value="{{ old('primary_color', $cooperative->primary_color) }}">
                </div>

                <div class="col-md-6">
                    <label for="secondary_color" class="form-label">Secondary Theme Color</label>
                    <input type="color" class="form-control form-control-color w-100" id="secondary_color" name="secondary_color" value="{{ old('secondary_color', $cooperative->secondary_color) }}">
                </div>

                <div class="col-12 mt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $cooperative->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="is_active">Cooperative Active Status</label>
                    </div>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-between">
                    <a href="{{ route('admin.cooperatives.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Cooperative</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/nigeria-states-lgas.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initNigeriaStateLga('state', 'lga', @json(old('lga', $cooperative->lga ?? '')));
    });
</script>
@endpush
