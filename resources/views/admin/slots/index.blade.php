@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-grid-3x3-gap"></i> Slot Settings</h4>
@endsection

@section('content')
<div class="card shadow">
    <div class="card-header">
        <span class="fw-bold">Manage Slot Numbers and Monthly Amounts</span>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.slots.store') }}" class="row g-3 align-items-end mb-4">
            @csrf
            <div class="col-md-3">
                <label for="new_slot_number" class="form-label fw-bold">New Slot Number</label>
                <input type="number" name="slot_number" id="new_slot_number" class="form-control" min="1" required>
            </div>
            <div class="col-md-3">
                <label for="new_slot_amount" class="form-label fw-bold">Monthly Amount</label>
                <input type="number" name="amount" id="new_slot_amount" class="form-control" min="0" step="0.01" required>
            </div>
            <div class="col-md-3">
                <label for="new_slot_status" class="form-label fw-bold">Status</label>
                <select name="is_active" id="new_slot_status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Slot</button>
            </div>
        </form>

        @if($slots->isEmpty())
            <div class="alert alert-warning">No slot configurations exist. Add a slot above or run the slot seeder.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Slot Number</th>
                            <th>Monthly Amount</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slots as $slot)
                            <tr>
                                <td class="fw-bold">Slot {{ $slot->slot_number }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.slots.update', $slot) }}" class="row g-2 align-items-center">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-auto">
                                            <div class="input-group">
                                                <span class="input-group-text">₦</span>
                                                <input type="number" name="amount" class="form-control" min="0" step="0.01" value="{{ old('amount', $slot->amount) }}" required>
                                            </div>
                                        </div>
                                </td>
                                <td>
                                        <select name="is_active" class="form-select">
                                            <option value="1" {{ $slot->is_active ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ !$slot->is_active ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                </td>
                                <td class="text-center">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
