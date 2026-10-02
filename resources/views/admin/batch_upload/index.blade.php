@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-cloud-arrow-up"></i> Batch Upload / Bulk Import</h4>
@endsection

@section('content')
<div class="row g-4 mb-4">
    <!-- Main Upload Card -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="m-0 fw-bold"><i class="bi bi-upload"></i> Upload Import File</h6>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.batch-upload.preview') }}" enctype="multipart/form-data" id="batchUploadForm">
                    @csrf

                    <!-- Data Type Selection -->
                    <div class="mb-3">
                        <label for="import_type" class="form-label text-muted small fw-bold text-uppercase">1. Select Data Type to Import <span class="text-danger">*</span></label>
                        <select name="import_type" id="import_type" class="form-select @error('import_type') is-invalid @enderror" required>
                            <option value="members" {{ (request('type', old('import_type', 'members')) === 'members') ? 'selected' : '' }}>Members (Profiles, Slots, Next of Kin, Reg Fee)</option>
                            <option value="savings" {{ (request('type', old('import_type', 'members')) === 'savings') ? 'selected' : '' }}>Monthly Savings</option>
                            <option value="running_charges" {{ (request('type', old('import_type', 'members')) === 'running_charges') ? 'selected' : '' }}>Running Charges</option>
                            <option value="loans" {{ (request('type', old('import_type', 'members')) === 'loans') ? 'selected' : '' }}>Financing / Loans</option>
                            <option value="expenses" {{ (request('type', old('import_type', 'members')) === 'expenses') ? 'selected' : '' }}>Expenses</option>
                            <option value="investments" {{ (request('type', old('import_type', 'members')) === 'investments') ? 'selected' : '' }}>Investments / Ventures</option>
                            <option value="registration_fees" {{ (request('type', old('import_type', 'members')) === 'registration_fees') ? 'selected' : '' }}>Registration Fees</option>
                        </select>
                        @error('import_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Duplicate Handling Option -->
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">2. Duplicate Handling Policy <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="form-check card p-3 border shadow-none mb-0">
                                    <input class="form-check-input" type="radio" name="duplicate_mode" id="dup_skip" value="skip" checked>
                                    <label class="form-check-label fw-bold ms-1" for="dup_skip">
                                        <i class="bi bi-skip-forward text-warning"></i> Skip Duplicates
                                    </label>
                                    <small class="text-muted d-block ms-4 fs-7">Ignore rows that already exist in database.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check card p-3 border shadow-none mb-0">
                                    <input class="form-check-input" type="radio" name="duplicate_mode" id="dup_update" value="update" {{ old('duplicate_mode') === 'update' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold ms-1" for="dup_update">
                                        <i class="bi bi-pencil-square text-info"></i> Update Existing
                                    </label>
                                    <small class="text-muted d-block ms-4 fs-7">Update existing database records with file data.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check card p-3 border shadow-none mb-0">
                                    <input class="form-check-input" type="radio" name="duplicate_mode" id="dup_new" value="new_only" {{ old('duplicate_mode') === 'new_only' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold ms-1" for="dup_new">
                                        <i class="bi bi-plus-circle text-success"></i> New Records Only
                                    </label>
                                    <small class="text-muted d-block ms-4 fs-7">Only process completely new records.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload Input -->
                    <div class="mb-4">
                        <label for="import_file" class="form-label text-muted small fw-bold text-uppercase">3. Select File (.csv, .xlsx, .xls) <span class="text-danger">*</span></label>
                        <div class="border border-2 border-dashed rounded-3 p-4 text-center bg-light">
                            <i class="bi bi-file-earmark-spreadsheet text-primary fs-1 d-block mb-2"></i>
                            <input type="file" name="import_file" id="import_file" class="form-control d-none @error('import_file') is-invalid @enderror" accept=".csv,.xlsx,.xls,.txt" required>
                            <button type="button" class="btn btn-outline-primary btn-sm mb-2" onclick="document.getElementById('import_file').click();">
                                <i class="bi bi-folder2-open"></i> Browse File
                            </button>
                            <div id="fileNameDisplay" class="fw-bold text-dark small">No file selected yet</div>
                            <small class="text-muted d-block mt-1">Supported formats: CSV, Excel (.xlsx, .xls) &bull; Max size: 10MB</small>
                        </div>
                        @error('import_file')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit & Reset Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="reset" class="btn btn-outline-secondary" onclick="document.getElementById('fileNameDisplay').innerText = 'No file selected yet';">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="submitBtn">
                            <i class="bi bi-search"></i> Preview & Validate Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Instructions & Template Downloads Card -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-file-earmark-arrow-down"></i> Download Sample CSV Templates</h6>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Download sample CSV template files containing the correct headers, required formatting, and clean example rows for each cooperative record type:
                </p>

                <div class="list-group mb-4 shadow-none">
                    <a href="{{ route('admin.batch-upload.template', 'members') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-primary"><i class="bi bi-people-fill me-1"></i> Members Template</div>
                            <small class="text-muted">Includes slots, DOB, contact address, next of kin details.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                    <a href="{{ route('admin.batch-upload.template', 'savings') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-success"><i class="bi bi-piggy-bank-fill me-1"></i> Monthly Savings Template</div>
                            <small class="text-muted">Member identifier, Month (YYYY-MM), amount, slot number.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                    <a href="{{ route('admin.batch-upload.template', 'running_charges') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-info"><i class="bi bi-receipt me-1"></i> Running Charges Template</div>
                            <small class="text-muted">Member Code, Full Name, Month (YYYY-MM), amount, payment date.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-info"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                    <a href="{{ route('admin.batch-upload.template', ['type' => 'running_charges', 'late_2021' => 1]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-info"><i class="bi bi-calendar3 me-1"></i> Running Charges: Nov-Dec 2021</div>
                            <small class="text-muted">Member Code, Full Name, and only November and December 2021.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-info"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                    <a href="{{ route('admin.batch-upload.template', 'loans') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-warning"><i class="bi bi-cash-stack me-1"></i> Financing / Loans Template</div>
                            <small class="text-muted">Principal amount, profit %, duration, date granted, status.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-warning"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                    <a href="{{ route('admin.batch-upload.template', 'expenses') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-danger"><i class="bi bi-cart me-1"></i> Expenses Template</div>
                            <small class="text-muted">Category, description, amount, expense date, status.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-danger"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                    <a href="{{ route('admin.batch-upload.template', 'investments') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-dark"><i class="bi bi-graph-up-arrow me-1"></i> Investments Template</div>
                            <small class="text-muted">Investment name, type, capital amount, start date.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-dark"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                    <a href="{{ route('admin.batch-upload.template', 'registration_fees') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-secondary"><i class="bi bi-card-checklist me-1"></i> Registration Fees Template</div>
                            <small class="text-muted">Member identifier, fee amount, payment date, reference.</small>
                        </div>
                        <span class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> .CSV</span>
                    </a>
                </div>

                <div class="alert alert-light border small text-muted mb-0">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-info-circle text-primary me-1"></i> Import Guidelines:</strong>
                    <ul class="mb-0 ps-3">
                        <li>Dates must use <strong>YYYY-MM-DD</strong> format (e.g. 1990-05-15).</li>
                        <li>Months must use <strong>YYYY-MM</strong> format (e.g. 2026-07).</li>
                        <li>Monetary amounts must be positive numbers without currency symbols.</li>
                        <li>Running charges use Member Code as the only member identifier.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Audit History Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-journal-text"></i> Bulk Import Audit Trail History</h6>
        <span class="badge bg-secondary">{{ $recentImports->total() }} Total Uploads</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Uploaded By</th>
                        <th>Data Type</th>
                        <th>Original Filename</th>
                        <th>Duplicate Policy</th>
                        <th class="text-center">Total Rows</th>
                        <th class="text-center">Imported</th>
                        <th class="text-center">Duplicates</th>
                        <th class="text-center">Rejected</th>
                        <th class="text-end me-3">Error Report</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentImports as $import)
                        <tr>
                            <td>{{ $import->created_at->format('M d, Y - h:i A') }}</td>
                            <td>
                                <strong>{{ $import->user->name ?? 'System' }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-primary text-capitalize">{{ str_replace('_', ' ', $import->import_type) }}</span>
                            </td>
                            <td><code>{{ $import->original_filename }}</code></td>
                            <td>
                                <span class="badge bg-outline-secondary text-uppercase">{{ $import->duplicate_mode }}</span>
                            </td>
                            <td class="text-center fw-bold">{{ number_format($import->total_rows) }}</td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ number_format($import->successful_rows) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark">{{ number_format($import->duplicate_rows) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger">{{ number_format($import->invalid_rows) }}</span>
                            </td>
                            <td class="text-end">
                                @if(!empty($import->error_details) && count($import->error_details) > 0)
                                    <a href="{{ route('admin.batch-upload.error-report', $import->id) }}" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-earmark-x me-1"></i> Export Error CSV
                                    </a>
                                @else
                                    <span class="text-muted small"><i class="bi bi-check-all text-success me-1"></i> Clean</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No bulk imports recorded yet. Upload a CSV file above to begin.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($recentImports->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $recentImports->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.getElementById('import_file');
        const display = document.getElementById('fileNameDisplay');

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    display.innerText = "Selected: " + this.files[0].name + " (" + (this.files[0].size / 1024).toFixed(1) + " KB)";
                } else {
                    display.innerText = "No file selected yet";
                }
            });
        }
    });
</script>
@endpush
@endsection
