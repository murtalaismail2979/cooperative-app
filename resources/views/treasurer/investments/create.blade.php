@extends('layouts.app')
@section('page-title')<h4><i class="bi bi-plus-circle"></i> New Investment</h4>@endsection
@section('content')
<div class="card shadow"><div class="card-body">
<form method="POST" action="{{ route('treasurer.investments.store') }}">
    @csrf
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Investment Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
                <option value="">Select</option>
                @foreach($investmentTypes as $type)
                    <option value="{{ $type->slug }}" {{ old('type') == $type->slug ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Capital Amount (₦) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="capital_amount" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"></textarea>
    </div>
    <div class="d-flex justify-content-between">
        <a href="{{ route('treasurer.investments.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Create</button>
    </div>
</form>
</div></div>
@endsection