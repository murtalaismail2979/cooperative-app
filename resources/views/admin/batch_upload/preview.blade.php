@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-eye"></i> Import Preview & Data Validation</h4>
@endsection

@section('content')
<div class="row g-4 mb-4">
    <!-- Stat Summary Cards -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-primary text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-file-earmark-spreadsheet icon"></i>
                <div class="stat-label">Total Rows Detected</div>
                <div class="stat-value">{{ number_format($preview['total_rows']) }}</div>
                <small>File: <code>{{ $filename }}</code></small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-success text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-check-circle icon"></i>
                <div class="stat-label">Valid Records</div>
                <div class="stat-value">{{ number_format($preview['valid_count']) }}</div>
                <small>Ready to import</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-warning text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-exclamation-triangle icon"></i>
                <div class="stat-label">Duplicates Detected</div>
                <div class="stat-value">{{ number_format($preview['duplicate_count']) }}</div>
                <small>Policy: <strong class="text-uppercase">{{ $duplicateMode }}</strong></small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-danger text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-x-circle icon"></i>
                <div class="stat-label">Invalid Records</div>
                <div class="stat-value">{{ number_format($preview['invalid_count']) }}</div>
                <small>Will be rejected</small>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation & Actions Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="fw-bold text-dark">Data Type:</span> 
            <span class="badge bg-primary me-3 text-capitalize">{{ str_replace('_', ' ', $importType) }}</span>
            <span class="fw-bold text-dark">Duplicate Mode:</span> 
            <span class="badge bg-secondary text-uppercase me-3">{{ $duplicateMode }}</span>
            <span class="text-muted small"><i class="bi bi-info-circle"></i> Review the row-by-row breakdown below before confirming the import.</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.batch-upload.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle"></i> Cancel & Re-upload
            </a>
            <form method="POST" action="{{ route('admin.batch-upload.confirm') }}">
                @csrf
                <button type="submit" class="btn btn-success fw-bold px-4">
                    <i class="bi bi-check-lg me-1"></i> Confirm and Execute Import
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Parsed Rows Validation Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-table"></i> Row Validation Breakdown</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Row #</th>
                        <th>Record Identifier / Summary</th>
                        <th>Import Data Breakdown</th>
                        <th class="text-center" style="width: 120px;">Validation Status</th>
                        <th>Validation Details / Rejection Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preview['rows'] as $row)
                        <tr class="{{ $row['status'] === 'invalid' ? 'table-danger bg-opacity-25' : ($row['status'] === 'duplicate' ? 'table-warning bg-opacity-25' : '') }}">
                            <td class="fw-bold text-muted">#{{ $row['row_number'] }}</td>
                            <td>
                                <strong>{{ in_array($importType, ['savings', 'running_charges', 'loans', 'registration_fees'], true) ? ($row['data']['Member Code'] ?? 'Row ' . $row['row_number']) : ($row['data']['Full Name'] ?? $row['data']['Email'] ?? 'Row ' . $row['row_number']) }}</strong>
                                @if(!empty($row['data']['Registration Number']))
                                    <span class="badge bg-info text-dark ms-1" title="Obsolete column ignored - system will auto-generate code"><i class="bi bi-magic"></i> Auto-Generated Code</span>
                                @endif
                            </td>
                            <td class="small">
                                @foreach($row['data'] as $key => $val)
                                    @if(!empty($val) && ($importType !== 'savings' || $key !== 'Email') && !in_array($key, ['Full Name', 'Email']))
                                        <span class="text-muted">{{ $key }}:</span> <strong>{{ $val }}</strong> &bull;
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-center">
                                @if($row['status'] === 'valid')
                                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> Valid</span>
                                @elseif($row['status'] === 'duplicate')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Duplicate</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Invalid</span>
                                @endif
                            </td>
                            <td>
                                @if(count($row['errors']) > 0)
                                    <ul class="mb-0 ps-3 small text-danger">
                                        @foreach($row['errors'] as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-success small"><i class="bi bi-check-circle me-1"></i> Passed validation checks.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No rows detected in the uploaded file.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
