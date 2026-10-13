@extends('layouts.app')

@section('title', 'Cooperative Management')

@section('page-title')
    <h2 class="h4 mb-0"><i class="bi bi-buildings me-2 text-primary"></i> Cooperative Management</h2>
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-gray-800 fw-bold"><i class="bi bi-list-stars me-2"></i> Registered Cooperatives</h5>
        <a href="{{ route('admin.cooperatives.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Register New Cooperative
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Cooperative Name</th>
                        <th>Code / Slug</th>
                        <th>Members</th>
                        <th>Slot Base Fee</th>
                        <th>Dividend Basis</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cooperatives as $coop)
                        <tr>
                            <td class="ps-4 text-muted fw-bold">{{ $coop->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-building fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $coop->name }}</h6>
                                        <small class="text-muted">{{ $coop->email ?? 'No email set' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-primary text-white font-monospace fs-6 px-3 py-1 shadow-sm" style="letter-spacing: 0.5px;">{{ $coop->slug }}</span></td>
                            <td><span class="badge bg-info text-dark rounded-pill px-3">{{ $coop->users_count }} Members</span></td>
                            <td class="fw-semibold text-primary">₦{{ number_format($coop->base_slot_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-secondary text-capitalize">
                                    {{ str_replace('_', ' ', $coop->dividend_allocation_basis) }}
                                </span>
                            </td>
                            <td>
                                @if($coop->is_active)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Active</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.cooperatives.edit', $coop) }}" class="btn btn-outline-primary btn-sm me-1" title="Edit Settings">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <form action="{{ route('admin.cooperatives.toggle', $coop) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to change status for this cooperative?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $coop->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        <i class="bi {{ $coop->is_active ? 'bi-dash-circle' : 'bi-check-circle' }}"></i>
                                        {{ $coop->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No cooperatives registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
