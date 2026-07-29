<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DividendAdjustment;
use App\Models\FinancialYear;
use App\Models\FinancialYearReconciliation;
use App\Services\DividendReconciliationService;
use Illuminate\Http\Request;

class DividendReconciliationController extends Controller
{
    protected DividendReconciliationService $service;

    public function __construct(DividendReconciliationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $selectedYear = (int) $request->input('year', date('Y'));
        $preview = $this->service->previewReconciliation($selectedYear);
        $reconciliations = FinancialYearReconciliation::with('approver')->latest('year')->get();
        $isClosed = $this->service->isYearClosed($selectedYear);

        return view('admin.dividends.reconciliation.index', compact('selectedYear', 'preview', 'reconciliations', 'isClosed'));
    }

    public function show(int $year)
    {
        $reconciliation = FinancialYearReconciliation::with(['adjustments.user', 'adjustments.recoveryPayments', 'approver'])
            ->where('year', $year)
            ->first();

        if (!$reconciliation) {
            return redirect()->route('admin.dividends.reconciliation.index', ['year' => $year])
                ->with('info', 'No finalized reconciliation report found for ' . $year . '. You can preview and generate one below.');
        }

        $isClosed = $this->service->isYearClosed($year);

        return view('admin.dividends.reconciliation.show', compact('reconciliation', 'year', 'isClosed'));
    }

    public function process(Request $request, int $year)
    {
        try {
            $notes = $request->input('notes');
            $reconciliation = $this->service->processYearEndReconciliation($year, $notes, auth()->id());

            return redirect()->route('admin.dividends.reconciliation.show', $year)
                ->with('success', "Annual Dividend & Loss Reconciliation for Financial Year {$year} successfully processed and recorded.");
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function recordRecovery(Request $request, DividendAdjustment $adjustment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:255',
        ]);

        try {
            $this->service->recordRecoveryPayment(
                $adjustment,
                (float) $request->amount,
                $request->payment_date,
                $request->payment_method,
                $request->reference_number,
                auth()->id()
            );

            return back()->with('success', 'Dividend loss adjustment recovery payment recorded successfully.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

    public function toggleYearLock(Request $request, int $year)
    {
        if ($this->service->isYearClosed($year)) {
            $this->service->reopenFinancialYear($year);
            $msg = "Financial Year {$year} has been reopened.";
        } else {
            $this->service->closeFinancialYear($year, auth()->id());
            $msg = "Financial Year {$year} has been closed and locked.";
        }

        return back()->with('success', $msg);
    }
}
