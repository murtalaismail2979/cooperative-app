@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-people"></i> {{ !empty($readOnly) ? 'Members List' : 'Members Management' }}</h4>
@endsection

@section('content')
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ !empty($readOnly) ? 'All Members' : 'All Users' }}</span>
        @if(!empty($readOnly))
        <a href="{{ route('admin.members.export', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Download Excel
        </a>
        @endif
        @if(empty($readOnly))
        <div class="d-flex gap-2">
            @if(auth()->user()->isManagementRole())
            <a href="{{ route('admin.members.view') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-lines-fill"></i> View Members
            </a>
            @endif
            <a href="{{ route('admin.loans.create') }}" class="btn btn-success btn-sm me-1">
                <i class="bi bi-plus-circle"></i> New Financing
            </a>
            <a href="{{ route('admin.batch-upload.index', ['type' => 'members']) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-cloud-arrow-up"></i> Batch Upload Members
            </a>
            <a href="{{ route('admin.members.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Add User
            </a>
        </div>
        @endif
    </div>
    <div class="card-body">
        @if(!empty($pendingCount) && $pendingCount > 0 && empty($readOnly))
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4 py-2 px-3 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-exclamation fs-4 me-2 text-warning"></i>
                <span><strong>{{ $pendingCount }} membership {{ Str::plural('application', $pendingCount) }}</strong> pending review and approval.</span>
            </div>
            @if(($status ?? '') !== 'pending')
                <a href="{{ route('admin.members.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm fw-semibold text-dark">
                    View Pending ({{ $pendingCount }})
                </a>
            @endif
        </div>
        @endif

        <form method="GET" action="{{ route('admin.members.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="memberSearch" class="form-control border-start-0 auto-search" placeholder="Search by name, email or code..." value="{{ $search ?? '' }}">
                </div>
            </div>
            
            <div class="col-md-2">
                @if(empty($readOnly))
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="member" {{ ($roleFilter ?? '') === 'member' ? 'selected' : '' }}>Member</option>
                    <option value="admin" {{ ($roleFilter ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="chairman" {{ ($roleFilter ?? '') === 'chairman' ? 'selected' : '' }}>Chairman</option>
                    <option value="secretary" {{ ($roleFilter ?? '') === 'secretary' ? 'selected' : '' }}>Secretary</option>
                    <option value="treasurer" {{ ($roleFilter ?? '') === 'treasurer' ? 'selected' : '' }}>Treasurer</option>
                </select>
                @else
                <input type="hidden" name="role" value="member">
                @endif
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

            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending @if(!empty($pendingCount))({{ $pendingCount }})@endif</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                        <th>Reg. Period</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Slots</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                    <tr @if($member->isPendingMembership()) class="table-warning bg-opacity-10" @endif>
                        <td>
                            @if($member->member_code)
                                <span class="badge bg-primary text-white">{{ $member->member_code }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $member->name }}</div>
                            @if($member->isPendingMembership())
                                <small class="badge bg-warning text-dark"><i class="bi bi-clock"></i> New Applicant</small>
                            @endif
                        </td>
                        <td>
                            @if($member->isAdmin())
                                <span class="badge bg-danger text-white">Admin</span>
                            @elseif($member->isChairman())
                                <span class="badge bg-primary text-white">Chairman</span>
                            @elseif($member->isSecretary())
                                <span class="badge bg-success text-white">Secretary</span>
                            @elseif($member->isTreasurer())
                                <span class="badge bg-warning text-white">Treasurer</span>
                            @else
                                <span class="badge bg-info text-white">Member</span>
                            @endif
                        </td>
                        <td>{{ $member->registration_month_year }}</td>
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
                            @if($member->isPendingMembership())
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending Approval</span>
                            @elseif($member->isRejectedMembership())
                                <span class="badge bg-danger text-white"><i class="bi bi-x-circle"></i> Rejected</span>
                            @elseif($member->is_active)
                                <span class="badge bg-success text-white">Active</span>
                            @else
                                <span class="badge bg-secondary text-white">Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if(empty($readOnly))
                            <div class="d-flex align-items-center gap-1">
                                @if($member->isPendingMembership())
                                    <form action="{{ route('admin.members.approve', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve {{ addslashes($member->name) }} as an active member?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success text-white" title="Approve Membership">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Reject Application" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $member->id }}">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                    <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-sm btn-info text-white" title="Edit member profile">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @elseif($member->isRejectedMembership())
                                    <form action="{{ route('admin.members.approve', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Re-approve {{ addslashes($member->name) }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Approve Member">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-sm btn-info text-white" title="Edit member profile">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @else
                                    <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-sm btn-info text-white" title="Edit member profile">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($member->isMember())
                                    <a href="{{ route('admin.members.slots', $member) }}" class="btn btn-sm btn-outline-primary" title="Manage savings slots">
                                        <i class="bi bi-grid-3x3-gap"></i>
                                    </a>
                                    @endif
                                @endif
                            </div>
                            @else
                                <span class="text-muted">View only</span>
                            @endif
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

@if(empty($readOnly))
@foreach($members as $member)
    @if($member->isPendingMembership())
    <div class="modal fade" id="rejectModal{{ $member->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $member->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.members.reject', $member) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel{{ $member->id }}"><i class="bi bi-person-x text-danger me-2"></i> Reject Membership Application</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reject the membership application for <strong>{{ $member->name }}</strong> ({{ $member->email }})?</p>
                        <div class="mb-3">
                            <label for="reason{{ $member->id }}" class="form-label">Reason for Rejection (Optional):</label>
                            <textarea name="rejection_reason" id="reason{{ $member->id }}" class="form-control" rows="3" placeholder="Provide a reason for the applicant..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endif
@endsection