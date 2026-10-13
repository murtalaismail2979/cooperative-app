@extends('layouts.app')

@section('title', 'Platform Charges & Cooperative Billing')

@section('page-title')
    <h2 class="h4 mb-0"><i class="bi bi-cash-stack me-2 text-primary"></i> Platform Charges & Billing</h2>
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- Top Description Card -->
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 12px;">
        <div class="card-body p-4 text-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-3 mb-md-0">
                    <span class="badge bg-primary px-3 py-1 mb-2 fs-6 shadow-sm"><i class="bi bi-shield-check me-1"></i> Super Admin Billing Control</span>
                    <h3 class="fw-bold mb-1 text-white">Cooperative Application Usage Charges</h3>
                    <p class="mb-0 text-white-50" style="max-width: 720px;">
                        Configure the agreed monthly software usage charges per registered member for each cooperative. The system automatically computes the total monthly and projected annual bill based on each cooperative's member count.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#batchRateModal">
                        <i class="bi bi-sliders me-1"></i> Apply Uniform Rate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <!-- Cooperatives Count -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-primary text-white shadow h-100">
                <div class="card-body">
                    <i class="bi bi-buildings icon"></i>
                    <div class="stat-label">Registered Cooperatives</div>
                    <div class="stat-value">{{ number_format($totalCooperatives) }}</div>
                    <div class="d-flex align-items-center mt-2 small text-white-50">
                        <span class="badge bg-success bg-opacity-75 me-2">{{ $activeCooperatives }} Active</span>
                        <span>{{ $totalCooperatives - $activeCooperatives }} Inactive</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billable Members -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-info text-white shadow h-100">
                <div class="card-body">
                    <i class="bi bi-people icon"></i>
                    <div class="stat-label">Total Billable Members</div>
                    <div class="stat-value">{{ number_format($totalBillableMembers) }}</div>
                    <div class="mt-2 small text-white-50">
                        Active registered member base across all cooperatives
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Monthly Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-success text-white shadow h-100">
                <div class="card-body">
                    <i class="bi bi-cash-coin icon"></i>
                    <div class="stat-label">Total Monthly Platform Bill</div>
                    <div class="stat-value">₦{{ number_format($totalMonthlyRevenue, 2) }}</div>
                    <div class="mt-2 small text-white-50">
                        Sum of all monthly cooperative charges
                    </div>
                </div>
            </div>
        </div>

        <!-- Projected Annual Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card bg-warning text-white shadow h-100">
                <div class="card-body">
                    <i class="bi bi-calendar-check icon"></i>
                    <div class="stat-label">Projected Annual Revenue</div>
                    <div class="stat-value">₦{{ number_format($totalAnnualRevenue, 2) }}</div>
                    <div class="mt-2 small text-white-50">
                        Estimated 12-month platform return
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Table Card -->
    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-0 text-gray-800 fw-bold">
                <i class="bi bi-table me-2 text-primary"></i> Cooperative Charges & Calculated Totals
            </h5>
            <span class="text-muted small">
                Calculation: <code>Total Monthly = Registered Members × Charge Rate Per Member</code>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Cooperative</th>
                            <th>Code / Slug</th>
                            <th class="text-center">Registered Members</th>
                            <th class="text-end">Charge / Member / Mo</th>
                            <th class="text-end">Total Monthly Charge</th>
                            <th class="text-end">Projected Annual</th>
                            <th>Agreement Notes</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cooperatives as $coop)
                            @php
                                $members = (int) ($coop->members_count ?? 0);
                                $rate = (float) ($coop->monthly_charge_per_member ?? 0);
                                $monthlyTotal = $members * $rate;
                                $annualTotal = $monthlyTotal * 12;
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted fw-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-building fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">{{ $coop->name }}</h6>
                                            <small class="text-muted">{{ $coop->email ?? 'No email set' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white font-monospace fs-6 px-3 py-1 shadow-sm" style="letter-spacing: 0.5px;">
                                        {{ $coop->slug }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark rounded-pill px-3 py-1 fw-bold fs-6 shadow-sm">
                                        <i class="bi bi-people-fill me-1"></i> {{ number_format($members) }} Members
                                    </span>
                                </td>
                                <td class="text-end font-monospace fw-semibold">
                                    <span class="badge bg-light text-primary border border-primary border-opacity-25 px-2.5 py-1.5 fs-6">
                                        ₦{{ number_format($rate, 2) }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace">
                                    <span class="fw-bold text-success fs-6">
                                        ₦{{ number_format($monthlyTotal, 2) }}
                                    </span>
                                    <div class="small text-muted">{{ $members }} × ₦{{ number_format($rate, 2) }}</div>
                                </td>
                                <td class="text-end font-monospace text-secondary fw-semibold">
                                    ₦{{ number_format($annualTotal, 2) }}
                                </td>
                                <td style="max-width: 200px;">
                                    @if(!empty($coop->billing_notes))
                                        <span class="text-truncate d-inline-block" style="max-width: 180px;" title="{{ $coop->billing_notes }}">
                                            <i class="bi bi-journal-text text-muted me-1"></i> {{ $coop->billing_notes }}
                                        </span>
                                    @else
                                        <span class="text-muted fst-italic small">Standard billing</span>
                                    @endif
                                </td>
                                <td>
                                    @if($coop->is_active)
                                        <span class="badge bg-success shadow-sm"><i class="bi bi-check-circle me-1"></i> Active</span>
                                    @else
                                        <span class="badge bg-danger shadow-sm"><i class="bi bi-x-circle me-1"></i> Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm shadow-sm"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#setChargeModal{{ $coop->id }}"
                                            title="Set or Adjust Monthly Charge">
                                        <i class="bi bi-pencil-square me-1"></i> Set Charge
                                    </button>

                                    <!-- Edit Charge Modal for this Cooperative -->
                                    <div class="modal fade" id="setChargeModal{{ $coop->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $coop->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <form action="{{ route('admin.platform-charges.update', $coop) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header bg-light border-bottom">
                                                        <h5 class="modal-title fw-bold text-dark" id="modalLabel{{ $coop->id }}">
                                                            <i class="bi bi-cash-coin text-primary me-2"></i> Set Charge for {{ $coop->name }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <!-- Cooperative Info summary -->
                                                        <div class="p-3 bg-light rounded-3 mb-3 border">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted small">Cooperative Code:</span>
                                                                <span class="fw-bold">{{ $coop->slug }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span class="text-muted small">Registered Members Count:</span>
                                                                <span class="badge bg-info text-dark fw-bold">{{ $members }} Members</span>
                                                            </div>
                                                        </div>

                                                        <!-- Input: Monthly charge per member -->
                                                        <div class="mb-3">
                                                            <label for="monthly_charge_per_member_{{ $coop->id }}" class="form-label fw-bold">
                                                                Monthly Charge Per Member (₦) <span class="text-danger">*</span>
                                                            </label>
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-white fw-bold">₦</span>
                                                                <input type="number" 
                                                                       step="0.01" 
                                                                       min="0" 
                                                                       class="form-control form-control-lg charge-rate-input" 
                                                                       id="monthly_charge_per_member_{{ $coop->id }}" 
                                                                       name="monthly_charge_per_member" 
                                                                       value="{{ old('monthly_charge_per_member', $coop->monthly_charge_per_member ?? 0) }}" 
                                                                       data-members="{{ $members }}"
                                                                       data-target-monthly="previewMonthly{{ $coop->id }}"
                                                                       data-target-annual="previewAnnual{{ $coop->id }}"
                                                                       required>
                                                            </div>
                                                            <small class="text-muted">The monthly rate agreed upon for each member of this cooperative.</small>
                                                        </div>

                                                        <!-- Live Calculated Bill Preview -->
                                                        <div class="card border border-success border-opacity-25 bg-success bg-opacity-10 mb-3">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="text-dark small fw-semibold">Calculated Monthly Total:</span>
                                                                    <span class="fw-bold fs-5 text-success font-monospace" id="previewMonthly{{ $coop->id }}">
                                                                        ₦{{ number_format($monthlyTotal, 2) }}
                                                                    </span>
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="text-dark small">Projected Annual Total:</span>
                                                                    <span class="text-secondary font-monospace fw-semibold" id="previewAnnual{{ $coop->id }}">
                                                                        ₦{{ number_format($annualTotal, 2) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Notes / Agreement details -->
                                                        <div class="mb-3">
                                                            <label for="billing_notes_{{ $coop->id }}" class="form-label fw-bold">
                                                                Agreement Notes / Contract Terms (Optional)
                                                            </label>
                                                            <textarea class="form-control" 
                                                                      id="billing_notes_{{ $coop->id }}" 
                                                                      name="billing_notes" 
                                                                      rows="3" 
                                                                      placeholder="e.g. Rate agreed as per service contract effective Jan 2026.">{{ old('billing_notes', $coop->billing_notes) }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light border-top">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary fw-semibold shadow-sm">
                                                            <i class="bi bi-check-lg me-1"></i> Save Charge Rate
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-buildings fs-1 text-secondary d-block mb-2"></i>
                                    No registered cooperatives found in the system.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($cooperatives->isNotEmpty())
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="3" class="ps-4 text-uppercase">Platform Grand Totals</td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill px-3 py-1 fs-6">
                                        {{ number_format($totalBillableMembers) }} Members
                                    </span>
                                </td>
                                <td class="text-end text-muted">-</td>
                                <td class="text-end font-monospace text-success fs-5">
                                    ₦{{ number_format($totalMonthlyRevenue, 2) }}
                                </td>
                                <td class="text-end font-monospace text-secondary fs-5">
                                    ₦{{ number_format($totalAnnualRevenue, 2) }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Batch Rate Modal -->
<div class="modal fade" id="batchRateModal" tabindex="-1" aria-labelledby="batchRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.platform-charges.batch') }}" method="POST">
                @csrf
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="batchRateModalLabel">
                        <i class="bi bi-sliders text-primary me-2"></i> Set Uniform Rate Across Cooperatives
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Quickly set a standard monthly rate per member across all registered cooperatives or select specific ones.
                    </p>

                    <div class="mb-3">
                        <label for="default_rate" class="form-label fw-bold">
                            Uniform Monthly Rate Per Member (₦) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white fw-bold">₦</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   class="form-control form-control-lg" 
                                   id="default_rate" 
                                   name="default_rate" 
                                   placeholder="e.g. 150.00" 
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Apply To:</label>
                        <div class="p-3 bg-light rounded-3 border" style="max-height: 180px; overflow-y: auto;">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="selectAllCoops" checked>
                                <label class="form-check-label fw-bold" for="selectAllCoops">
                                    All Cooperatives ({{ $cooperatives->count() }})
                                </label>
                            </div>
                            <hr class="my-2">
                            @foreach($cooperatives as $coop)
                                <div class="form-check mb-1">
                                    <input class="form-check-input coop-select-check" 
                                           type="checkbox" 
                                           name="cooperative_ids[]" 
                                           value="{{ $coop->id }}" 
                                           id="coop_check_{{ $coop->id }}" 
                                           checked>
                                    <label class="form-check-label" for="coop_check_{{ $coop->id }}">
                                        {{ $coop->name }} ({{ $coop->slug }}) - <small class="text-muted">{{ $coop->members_count ?? 0 }} members</small>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Apply Rates
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Live calculation for modal charge inputs
    const chargeInputs = document.querySelectorAll('.charge-rate-input');
    chargeInputs.forEach(input => {
        function updatePreview() {
            const members = parseFloat(input.dataset.members) || 0;
            const rate = parseFloat(input.value) || 0;
            const monthlyTotal = members * rate;
            const annualTotal = monthlyTotal * 12;

            const monthlyTarget = document.getElementById(input.dataset.targetMonthly);
            const annualTarget = document.getElementById(input.dataset.targetAnnual);

            if (monthlyTarget) {
                monthlyTarget.textContent = '₦' + monthlyTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            if (annualTarget) {
                annualTarget.textContent = '₦' + annualTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }

        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });

    // Select all cooperatives checkbox in batch modal
    const selectAll = document.getElementById('selectAllCoops');
    const coopCheckboxes = document.querySelectorAll('.coop-select-check');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            coopCheckboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
        });

        coopCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                const allChecked = Array.from(coopCheckboxes).every(c => c.checked);
                selectAll.checked = allChecked;
            });
        });
    }
});
</script>
@endsection
