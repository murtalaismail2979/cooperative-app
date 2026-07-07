@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-people"></i> Members Management</h4>
@endsection

@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Users</span>
        <a href="{{ route('admin.members.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Add User
        </a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.members.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="memberSearch" class="form-control border-start-0 auto-search" placeholder="Search by name, email or code..." value="{{ $search ?? '' }}">
                </div>
            </div>
            
            <div class="col-md-2">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="member" {{ ($roleFilter ?? '') === 'member' ? 'selected' : '' }}>Member</option>
                    <option value="treasurer" {{ ($roleFilter ?? '') === 'treasurer' ? 'selected' : '' }}>Treasurer</option>
                    <option value="admin" {{ ($roleFilter ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="reg_year" class="form-select" onchange="this.form.submit()">
                    <option value="">All Reg Years</option>
                    @foreach($registrationYears as $yearVal)
                        <option value="{{ $yearVal }}" {{ $regYear == $yearVal ? 'selected' : '' }}>{{ $yearVal }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="slots" class="form-select" onchange="this.form.submit()">
                    <option value="">All Slots</option>
                    @for($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}" {{ $slots == $i ? 'selected' : '' }}>{{ $i }} {{ Str::plural('Slot', $i) }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-md-1">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            </div>

            @if(!empty($search) || !empty($regYear) || !empty($slots) || !empty($status) || !empty($roleFilter))
            <div class="col-auto">
                <a href="{{ route('admin.members.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
            </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Reg. Year</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Slots</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                    <tr>
                        <td>
                            @if($member->member_code)
                                <span class="badge bg-primary">{{ $member->member_code }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $member->name }}</td>
                        <td>
                            @if($member->isAdmin())
                                <span class="badge bg-danger">Admin</span>
                            @elseif($member->isTreasurer())
                                <span class="badge bg-warning text-dark">Treasurer</span>
                            @else
                                <span class="badge bg-info text-dark">Member</span>
                            @endif
                        </td>
                        <td>{{ $member->registration_year ?? 'N/A' }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->phone ?? 'N/A' }}</td>
                        <td>
                            @if($member->isMember())
                                {{ $member->savingsSlots->where('is_active', true)->count() }}/10
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($member->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-sm btn-info">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center">No users found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $members->links() }}
    </div>
</div>
@endsection