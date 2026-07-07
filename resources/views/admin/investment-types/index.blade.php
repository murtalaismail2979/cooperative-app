@extends('layouts.app')
@section('page-title')
    <h4><i class="bi bi-gear"></i> Investment Types</h4>
@endsection

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.investments.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Investments</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- List of Types -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-list-task"></i> Current Investment Types</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th class="text-center">Associated Investments</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($types as $index => $type)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-bold">{{ $type->name }}</td>
                                <td><code>{{ $type->slug }}</code></td>
                                <td class="text-center">
                                    <span class="badge bg-info px-2.5 py-1.5">{{ $type->investments_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($type->investments_count == 0)
                                        <form method="POST" action="{{ route('admin.investment-types.destroy', $type) }}" onsubmit="return confirm('Are you sure you want to delete this investment type?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                                        </form>
                                    @else
                                        <span class="text-muted small" title="Cannot delete type with active investments"><i class="bi bi-lock-fill"></i> In use</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No custom investment types defined.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Type Form -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-plus-circle"></i> Add New Type</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.investment-types.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="type-name">Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="type-name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Real Estate" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted small mt-1">
                            The name of the investment type as it will appear in creation forms. The system will automatically generate a unique slug.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Save Investment Type</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
