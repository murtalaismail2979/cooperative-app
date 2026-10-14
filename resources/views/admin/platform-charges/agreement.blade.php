@extends('layouts.app')

@section('title', 'Master SaaS Agreement - ' . $cooperative->name)

@section('content')
<div class="container-fluid px-0">
    <!-- Top Action Toolbar (Hidden in Print) -->
    <div class="card shadow-sm border-0 mb-4 d-print-none" style="border-radius: 12px; background: #0f172a;">
        <div class="card-body p-3 text-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <a href="{{ route('admin.platform-charges.index') }}" class="btn btn-outline-light btn-sm me-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Platform Charges
                    </a>
                    <div>
                        <span class="badge bg-primary px-2.5 py-1 text-uppercase me-2 font-monospace">MCS-MSA-2026-{{ $cooperative->slug }}</span>
                        <span class="text-white fw-bold">{{ $cooperative->name }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="exportToWord()" class="btn btn-outline-light btn-sm fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-word me-1 text-info"></i> Download Word (.doc)
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                        <i class="bi bi-printer me-1"></i> Print / Save as PDF
                    </button>
                    <a href="{{ route('admin.platform-charges.index') }}" class="btn btn-light btn-sm fw-semibold shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i> Adjust Tariff Rate
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Printable Legal Document Wrapper -->
    <div class="saas-agreement-container bg-white shadow-sm p-4 p-md-5 mb-5 mx-auto" style="max-width: 960px; border-radius: 8px; border: 1px solid #e2e8f0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.65;">
        
        <!-- Document Header / Letterhead -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pb-4 mb-4 border-bottom border-2 border-primary">
            <div class="d-flex align-items-center mb-3 mb-sm-0">
                <img src="{{ asset('images/mycoopsuite-logo.png') }}" 
                     alt="MyCoopSuite Logo" 
                     class="me-3" 
                     style="height: 52px; width: auto; object-fit: contain;"
                     onerror="this.onerror=null; this.src='{{ asset('images/logo.png') }}';">
                <div>
                    <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">MyCoopSuite</h3>
                    <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.8px; font-size: 0.75rem;">Multi-Tenant Cooperative Cloud Infrastructure</small>
                </div>
            </div>
            <div class="text-sm-end">
                <span class="badge bg-light text-primary border border-primary px-3 py-1 font-monospace fw-bold fs-6">
                    REF: MCS-MSA-2026-{{ $cooperative->slug }}
                </span>
                <div class="small text-muted mt-1">Generated: {{ now()->format('F d, Y') }}</div>
            </div>
        </div>

        <!-- Agreement Title -->
        <div class="text-center my-4 py-2">
            <h2 class="fw-bolder text-dark mb-1 text-uppercase" style="letter-spacing: 0.5px;">Master Software-as-a-Service (SaaS) Agreement</h2>
            <p class="text-muted mb-0 font-monospace small">STANDARD ENTERPRISE SUBSCRIPTION, DATA SEGREGATION & SERVICE LEVEL AGREEMENT</p>
        </div>

        <!-- Parties Introduction -->
        <div class="p-3 mb-4 bg-light rounded border border-light-subtle">
            <p class="mb-2">This Master Software-as-a-Service Agreement (the <strong>"Agreement"</strong>) is made and entered into as of <strong>{{ $cooperative->created_at ? $cooperative->created_at->format('F d, Y') : now()->format('F d, Y') }}</strong> (the <strong>"Effective Date"</strong>), by and between:</p>
            <ol class="mb-0 ps-3">
                <li class="mb-2">
                    <strong>MYCOOPSUITE TECHNOLOGIES</strong> (hereinafter referred to as the <strong>"Provider"</strong>, <strong>"Software Owner"</strong>, or <strong>"Licensor"</strong>), the lawful developer, proprietor, and multi-tenant administrator of the <strong>MyCoopSuite</strong> financial management cloud application; and
                </li>
                <li>
                    <strong>{{ strtoupper($cooperative->name) }}</strong>, a registered cooperative society having its principal place of business at <em>{{ $cooperative->address ?? 'Registered Office' }}</em> (hereinafter referred to as the <strong>"Tenant"</strong>, <strong>"Customer"</strong>, or <strong>"Cooperative"</strong>).
                </li>
            </ol>
            <p class="mt-2 mb-0 small text-muted">The Provider and Tenant are individually referred to as a <strong>"Party"</strong> and collectively as the <strong>"Parties"</strong>.</p>
        </div>

        <!-- Recitals -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark text-uppercase border-bottom pb-1 mb-2" style="font-size: 1rem;">Recitals</h5>
            <p class="mb-1"><strong>WHEREAS:</strong></p>
            <p class="mb-1 ps-3"><strong>A.</strong> The Provider operates and licenses a proprietary cloud-based, multi-tenant software system known as <strong>MyCoopSuite</strong> (the <strong>"Platform"</strong>), designed specifically for cooperative societies to automate member registers, savings slots, loans, investments, dividends, and running charges.</p>
            <p class="mb-1 ps-3"><strong>B.</strong> The Tenant wishes to engage the Provider and subscribe to the Platform for the ongoing operational administration of its registered cooperative society and member accounts.</p>
            <p class="mb-0 ps-3"><strong>C.</strong> The Provider agrees to grant access to the Tenant on a per-member subscription fee basis, governed by strict data segregation, security standards, and the Service Level commitments set forth herein.</p>
        </div>

        <!-- Section 1 -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-1 mb-2" style="font-size: 1rem;">1. Definitions & Interpretation</h5>
            <p class="mb-1"><strong>1.1. "Active / Registered Member"</strong> means any natural person or entity registered within the Tenant's segregated partition on the Platform under the role of "Member" during any portion of a billing cycle.</p>
            <p class="mb-1"><strong>1.2. "Tenant Partition"</strong> means the logically isolated database instance and secure tenant domain allocated exclusively to the Tenant on the MyCoopSuite infrastructure.</p>
            <p class="mb-1"><strong>1.3. "Tenant Data"</strong> means all member identities, contact information, savings transactions, running charge entries, investment records, and related financial data submitted by or through the Tenant.</p>
            <p class="mb-1"><strong>1.4. "Per-Member Monthly Charge"</strong> means the unit tariff configured by the Super Admin in the Platform Charges portal and stipulated in Schedule A of this Agreement.</p>
            <p class="mb-0"><strong>1.5. "Super Admin"</strong> means the platform administration authority appointed by the Provider holding overarching maintenance rights over the multi-tenant environment.</p>
        </div>

        <!-- Section 2 -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-1 mb-2" style="font-size: 1rem;">2. Scope of Service & License Grant</h5>
            <p class="mb-1"><strong>2.1. Grant of License:</strong> Subject to timely payment of platform charges, the Provider grants the Tenant a non-exclusive, non-transferable, revocable license to access and utilize MyCoopSuite throughout the duration of this Agreement.</p>
            <p class="mb-1"><strong>2.2. Multi-Tenant Architectural Segregation:</strong> The Provider guarantees that the Tenant's data shall remain logically segregated and protected against cross-tenant data leakage or unauthorized access by any other cooperative on the Platform.</p>
            <p class="mb-0"><strong>2.3. Available Modules:</strong> The subscription entitles the Tenant to utilize member onboarding, automated savings slot tracking, loan administration, financing, dividend reconciliation, receipt generation, and role-based administrative dashboards (Chairman, Secretary, Treasurer, and Member portals).</p>
        </div>

        <!-- Section 3 -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-1 mb-2" style="font-size: 1rem;">3. Platform Charges & Automated Calculation</h5>
            <p class="mb-2"><strong>3.1. Dynamic Billing Formula:</strong> The Tenant agrees to pay a monthly usage charge calculated automatically by the Platform based upon the total number of registered members:</p>
            <div class="bg-light p-3 rounded text-center border font-monospace mb-2">
                <strong>Total Monthly Platform Charge (₦) = Total Registered Members × Agreed Rate Per Member (₦)</strong>
            </div>
            <p class="mb-1"><strong>3.2. Rate Schedule:</strong> The agreed rate for the Tenant is <strong>₦{{ number_format($rate, 2) }}</strong> per member per month, yielding a current monthly bill of <strong>₦{{ number_format($monthlyTotal, 2) }}</strong> based on <strong>{{ number_format($membersCount) }}</strong> registered members, as formalized in <strong>Schedule A</strong>.</p>
            <p class="mb-1"><strong>3.3. Invoicing & Due Date:</strong> Invoices are generated electronically on the first business day following each calendar month and are payable within fourteen (14) calendar days.</p>
            <p class="mb-0"><strong>3.4. Grace Period:</strong> In the event of payment delinquency exceeding thirty (30) calendar days, the Provider may restrict administrative posting privileges while maintaining read-only historical transparency for members.</p>
        </div>

        <!-- Section 4 -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-1 mb-2" style="font-size: 1rem;">4. Data Ownership, Security & Privacy</h5>
            <p class="mb-1"><strong>4.1. Exclusive Tenant Ownership:</strong> The Tenant retains 100% full, exclusive, and unencumbered ownership of all Tenant Data. The Provider acquires no proprietary rights over member ledgers or financial deposits.</p>
            <p class="mb-1"><strong>4.2. Data Protection Compliance:</strong> The Provider shall comply with applicable Data Protection Laws (NDPA/GDPR best practices). Personal member records shall never be sold, leased, or disclosed to unauthorized third parties.</p>
            <p class="mb-0"><strong>4.3. Encryption & Backups:</strong> The Provider enforces end-to-end TLS 1.3 encryption in transit and AES-256 for sensitive stores at rest, alongside automated daily geo-redundant database backups.</p>
        </div>

        <!-- Section 5 -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-1 mb-2" style="font-size: 1rem;">5. Service Level Agreement (SLA) & Incident Response</h5>
            <p class="mb-2"><strong>5.1. Uptime Guarantee:</strong> The Provider commits to a monthly Service Uptime of not less than <strong>99.5%</strong>, excluding scheduled off-peak maintenance.</p>
            <p class="mb-2"><strong>5.2. Support Response Severity Matrix:</strong></p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;">Severity</th>
                            <th>Description</th>
                            <th style="width: 20%;">Target First Response</th>
                            <th style="width: 20%;">Target Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold text-danger">P1 - Critical</td>
                            <td>Platform completely inaccessible to cooperative members or admins</td>
                            <td>&le; 1 hour</td>
                            <td>&le; 6 hours</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-warning">P2 - Major</td>
                            <td>Core transaction or member registration function impaired</td>
                            <td>&le; 4 hours</td>
                            <td>&le; 24 hours</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-primary">P3 - Minor</td>
                            <td>Non-blocking interface anomaly or reporting inquiry</td>
                            <td>&le; 12 hours</td>
                            <td>&le; 48 hours</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary">P4 - Routine</td>
                            <td>General guidance, rate adjustments, or feature recommendations</td>
                            <td>&le; 24 hours</td>
                            <td>Next scheduled update</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 6 & 7 -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-1 mb-2" style="font-size: 1rem;">6. Term, Offboarding & Data Portability</h5>
            <p class="mb-1"><strong>6.1. Term:</strong> This Agreement shall remain in effect for an initial period of twelve (12) months from the Effective Date and shall automatically renew for successive one-year terms unless terminated with thirty (30) days' written notice.</p>
            <p class="mb-0"><strong>6.2. 30-Day Export Guarantee:</strong> Upon termination or deactivation of service, the Tenant is guaranteed a <strong>thirty (30) calendar day window</strong> to extract and receive a complete, uncorrupted export of all member ledgers and transaction history in industry-standard formats (SQL / CSV).</p>
        </div>

        <!-- Section 8 -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-1 mb-2" style="font-size: 1rem;">7. Limitation of Liability & Governing Law</h5>
            <p class="mb-1"><strong>7.1. Liability Cap:</strong> The aggregate monetary liability of the Provider for any claim arising out of this Agreement shall be limited to the total fees actually paid by the Tenant during the twelve (12) months preceding the event.</p>
            <p class="mb-0"><strong>7.2. Governing Law:</strong> This Agreement is governed by and construed under the laws of the Federal Republic of Nigeria. Any unresolved disputes shall be submitted to binding arbitration.</p>
        </div>

        <!-- SCHEDULE A -->
        <div class="my-5 p-4 rounded border-2 border-primary border" style="background: #f8fafc; page-break-inside: avoid;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-primary mb-0 text-uppercase" style="letter-spacing: 0.5px;">
                    <i class="bi bi-file-earmark-check me-2"></i> Schedule A: Service Order & Tariff Schedule
                </h4>
                <span class="badge bg-primary text-white font-monospace px-3 py-1">CODE: {{ $cooperative->slug }}</span>
            </div>
            <p class="text-muted small mb-3">The following commercial parameters apply to the Tenant partition on the MyCoopSuite Platform:</p>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle mb-0" style="font-size: 0.9rem;">
                    <tbody>
                        <tr>
                            <th class="bg-light" style="width: 35%;">Platform Name</th>
                            <td class="fw-semibold">MyCoopSuite (Multi-Tenant Cooperative Cloud Infrastructure)</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Software Owner / Licensor</th>
                            <td>MyCoopSuite Technologies</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Tenant Cooperative Name</th>
                            <td class="fw-bold text-dark fs-6">{{ $cooperative->name }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Tenant Code / Unique Identifier</th>
                            <td><span class="badge bg-primary font-monospace fs-6">{{ $cooperative->slug }}</span></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Official Cooperative Contact</th>
                            <td>
                                <div><strong>Email:</strong> {{ $cooperative->email ?? 'Not specified' }}</div>
                                <div><strong>Phone:</strong> {{ $cooperative->phone ?? 'Not specified' }}</div>
                                <div><strong>Address:</strong> {{ $cooperative->address ?? 'Not specified' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Active Billable Member Count</th>
                            <td>
                                <span class="badge bg-info text-dark rounded-pill px-3 py-1 fw-bold fs-6">
                                    {{ number_format($membersCount) }} Registered Members
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Agreed Charge Per Member / Month</th>
                            <td class="font-monospace fw-bold text-primary fs-6">
                                ₦{{ number_format($rate, 2) }} / member / month
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Total Monthly SaaS Bill</th>
                            <td class="font-monospace fw-bold text-success fs-5">
                                ₦{{ number_format($monthlyTotal, 2) }} <span class="text-muted fs-6 fw-normal">({{ number_format($membersCount) }} × ₦{{ number_format($rate, 2) }})</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Projected 12-Month Annual Charge</th>
                            <td class="font-monospace fw-semibold text-secondary fs-6">
                                ₦{{ number_format($annualTotal, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Billing Invoicing Cycle</th>
                            <td>Monthly in arrears (Net 14 Calendar Days from invoice issuance)</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Special Contract Notes</th>
                            <td>{{ $cooperative->billing_notes ?? 'Standard automated per-member billing agreement.' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Effective Commencement Date</th>
                            <td>{{ $cooperative->created_at ? $cooperative->created_at->format('F d, Y') : now()->format('F d, Y') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Signature Execution Block -->
        <div class="mt-5 pt-4 border-top" style="page-break-inside: avoid;">
            <p class="fw-bold mb-4 text-center text-uppercase" style="letter-spacing: 0.5px;">
                IN WITNESS WHEREOF, the Parties have executed this Master SaaS Agreement as of the Effective Date.
            </p>

            <div class="row g-4 pt-2">
                <!-- Licensor / Provider -->
                <div class="col-md-6">
                    <div class="p-3 border rounded h-100 bg-light">
                        <h6 class="fw-bold text-primary text-uppercase mb-3 pb-1 border-bottom">For the Software Owner / Provider:</h6>
                        <div class="mb-3 small text-muted">MYCOOPSUITE TECHNOLOGIES</div>
                        
                        <div class="signature-line my-4" style="border-bottom: 1.5px dashed #64748b; height: 40px;"></div>
                        <div class="small fw-semibold">Authorized Signature</div>
                        <div class="small text-muted mt-2"><strong>Name:</strong> ___________________________________</div>
                        <div class="small text-muted mt-1"><strong>Title:</strong> Platform Administrator / Managing Director</div>
                        <div class="small text-muted mt-1"><strong>Date:</strong> ___________________________________</div>
                    </div>
                </div>

                <!-- Tenant / Cooperative -->
                <div class="col-md-6">
                    <div class="p-3 border rounded h-100 bg-light">
                        <h6 class="fw-bold text-primary text-uppercase mb-3 pb-1 border-bottom">For the Tenant Cooperative:</h6>
                        <div class="mb-3 small text-muted text-truncate"><strong>{{ $cooperative->name }}</strong></div>
                        
                        <div class="signature-line my-4" style="border-bottom: 1.5px dashed #64748b; height: 40px;"></div>
                        <div class="small fw-semibold">Authorized Executive Signature (President / Chairman)</div>
                        <div class="small text-muted mt-2"><strong>Name:</strong> ___________________________________</div>
                        <div class="small text-muted mt-1"><strong>Title:</strong> Cooperative Chairman / President</div>
                        <div class="small text-muted mt-1"><strong>Date:</strong> ___________________________________</div>

                        <div class="signature-line my-4" style="border-bottom: 1.5px dashed #64748b; height: 30px;"></div>
                        <div class="small fw-semibold">Attested By (Secretary / Treasurer)</div>
                        <div class="small text-muted mt-2"><strong>Name:</strong> ___________________________________</div>
                        <div class="small text-muted mt-1"><strong>Title:</strong> General Secretary / Treasurer</div>
                        <div class="small text-muted mt-1"><strong>Date:</strong> ___________________________________</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Footer -->
        <div class="text-center mt-5 pt-3 border-top text-muted small">
            <div>MyCoopSuite Cloud Infrastructure &bull; Master SaaS Service Agreement &bull; Ref: MCS-MSA-2026-{{ $cooperative->slug }}</div>
            <div class="text-muted" style="font-size: 0.75rem;">This document constitutes a legally binding software service agreement upon execution.</div>
        </div>

    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    /* Hide layout chrome */
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt !important;
    }
    .d-print-none,
    nav,
    aside,
    .sidebar,
    .topbar,
    header,
    footer,
    .btn,
    .modal {
        display: none !important;
    }
    .saas-agreement-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: transparent !important;
    }
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #94a3b8 !important;
    }
    a {
        text-decoration: none !important;
        color: #000000 !important;
    }
}
</style>

<script>
function exportToWord() {
    const content = document.querySelector('.saas-agreement-container').cloneNode(true);
    
    // Remove buttons or interactive elements if any inside container
    content.querySelectorAll('.d-print-none, button').forEach(el => el.remove());

    const header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' "+
        "xmlns:w='urn:schemas-microsoft-com:office:word' "+
        "xmlns='http://www.w3.org/TR/REC-html40'>"+
        "<head><meta charset='utf-8'><title>Master SaaS Agreement - {{ $cooperative->name }}</title>"+
        "<style>"+
        "body { font-family: 'Calibri', Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #1e293b; margin: 20px; } "+
        "h2, h3, h4, h5 { color: #0f172a; font-family: 'Calibri', Arial, sans-serif; } "+
        "table { border-collapse: collapse; width: 100%; margin: 16px 0; } "+
        "th, td { border: 1px solid #94a3b8; padding: 7px 10px; text-align: left; vertical-align: middle; } "+
        "th { background-color: #f1f5f9; font-weight: bold; } "+
        ".signature-line { border-bottom: 1.5px dashed #64748b; height: 35px; margin: 15px 0; } "+
        ".badge { display: inline-block; padding: 3px 7px; border: 1px solid #cbd5e1; font-weight: bold; } "+
        "</style></head><body>";
    const footer = "</body></html>";
    const sourceHTML = header + content.innerHTML + footer;
    
    const blob = new Blob(['\ufeff', sourceHTML], {
        type: 'application/msword;charset=utf-8'
    });
    
    const url = URL.createObjectURL(blob);
    const downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    downloadLink.href = url;
    downloadLink.download = "MyCoopSuite_SaaS_Agreement_{{ $cooperative->slug }}.doc";
    downloadLink.click();
    document.body.removeChild(downloadLink);
    URL.revokeObjectURL(url);
}
</script>
@endsection
