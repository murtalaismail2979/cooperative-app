<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Fee Receipt - {{ $payment->receipt_number }}</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 30px; }
        .receipt-card { max-width: 750px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; padding: 40px; }
        .receipt-header { border-bottom: 2px solid #6366f1; padding-bottom: 20px; margin-bottom: 30px; }
        .receipt-title { font-weight: 800; color: #4f46e5; letter-spacing: 0.5px; }
        .badge-status { font-size: 0.9rem; padding: 6px 14px; border-radius: 20px; }
        .table-receipt th { background-color: #f1f5f9; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        .receipt-footer { border-top: 1px solid #e2e8f0; margin-top: 40px; padding-top: 20px; font-size: 0.85rem; color: #64748b; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-card { box-shadow: none; border: none; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="d-flex justify-content-between align-items-center no-print mb-4">
            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i> Print / Save PDF</button>
        </div>

        <div class="receipt-header d-flex justify-content-between align-items-start">
            <div>
                <h2 class="receipt-title mb-1"><i class="bi bi-building me-2"></i>YLDA COOPERATIVE SOCIETY</h2>
                <p class="text-muted mb-0">Official Registration Fee Payment Receipt</p>
            </div>
            <div class="text-end">
                <span class="badge bg-success badge-status mb-2">PAID</span>
                <div class="fw-bold text-dark fs-5">{{ $payment->receipt_number }}</div>
                <small class="text-muted">Date: {{ $payment->payment_date->format('d M, Y') }}</small>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <h6 class="fw-bold text-uppercase text-muted small">Member Information</h6>
                <div class="fw-bold text-dark fs-5">{{ $payment->user->name }}</div>
                <div>Member Code: <strong>{{ $payment->user->member_code ?? 'N/A' }}</strong></div>
                <div>Email: {{ $payment->user->email }}</div>
                <div>Phone: {{ $payment->user->phone ?? 'N/A' }}</div>
            </div>
            <div class="col-6 text-end">
                <h6 class="fw-bold text-uppercase text-muted small">Payment Details</h6>
                <div>Payment Type: <strong>Registration Fee</strong></div>
                <div>Payment Method: <strong>{{ $payment->payment_method }}</strong></div>
                <div>Reference No: <strong>{{ $payment->reference_number ?? 'N/A' }}</strong></div>
                <div>Recorded By: <strong>{{ $payment->recorder->name ?? 'System' }}</strong></div>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle table-receipt">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount (₦)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="fw-bold">Member Registration Fee Obligation</div>
                            <small class="text-muted">Standard required registration fee for cooperative membership</small>
                        </td>
                        <td class="text-end fw-bold">₦{{ number_format($payment->registrationFee->fee_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Previous Total Payments Received</td>
                        <td class="text-end text-muted">₦{{ number_format($previousPaid, 2) }}</td>
                    </tr>
                    <tr class="table-success">
                        <td class="fw-bold text-success">Current Amount Paid (This Receipt)</td>
                        <td class="text-end fw-bold text-success fs-5">₦{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @php
                        $cumPaid = $previousPaid + $payment->amount;
                        $remaining = max(0.00, $payment->registrationFee->fee_amount - $cumPaid);
                    @endphp
                    <tr>
                        <td class="fw-bold">Total Cumulative Paid</td>
                        <td class="text-end fw-bold">₦{{ number_format($cumPaid, 2) }}</td>
                    </tr>
                    <tr class="{{ $remaining > 0 ? 'table-warning' : 'table-light' }}">
                        <td class="fw-bold">Remaining Outstanding Balance</td>
                        <td class="text-end fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">₦{{ number_format($remaining, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="row align-items-center mt-5 pt-3">
            <div class="col-6">
                <div class="border-top pt-2 text-center" style="max-width: 200px;">
                    <small class="fw-semibold text-muted">Authorized Signature</small>
                </div>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted">Generated on {{ now()->format('d/m/Y H:i:s') }}</small>
            </div>
        </div>

        <div class="receipt-footer text-center">
            <p class="mb-0">This is an officially computer-generated receipt issued by YLDA Cooperative Society.</p>
        </div>
    </div>
</body>
</html>
