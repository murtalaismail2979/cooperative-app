<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RegistrationFee;
use App\Models\RegistrationFeePayment;
use App\Models\RegistrationFeeHistory;
use App\Services\RegistrationFeeService;
use Illuminate\Http\Request;

class RegistrationFeeController extends Controller
{
    protected $registrationFeeService;

    public function __construct(RegistrationFeeService $registrationFeeService)
    {
        $this->registrationFeeService = $registrationFeeService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = User::where('role', 'member')->with(['registrationFee.payments' => function ($q) {
            $q->where('status', 'completed')->latest();
        }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->whereHas('registrationFee', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $members = $query->paginate(15)->withQueryString();

        // Ensure every member listed has a registration fee record initialized if missing
        foreach ($members as $member) {
            if (!$member->registrationFee) {
                $this->registrationFeeService->createObligationForMember($member);
                $member->load('registrationFee');
            }
        }

        $stats = $this->registrationFeeService->getDashboardStats($startDate, $endDate);

        return view('admin.registration_fees.index', compact('members', 'search', 'status', 'startDate', 'endDate', 'stats'));
    }

    public function settings()
    {
        $currentAmount = $this->registrationFeeService->getCurrentFeeAmount();
        $histories = RegistrationFeeHistory::with('changedBy')->latest()->paginate(10);

        return view('admin.registration_fees.settings', compact('currentAmount', 'histories'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->registrationFeeService->updateFeeAmount(
            (float) $validated['amount'],
            $validated['reason'] ?? null,
            auth()->id()
        );

        return redirect()->route('admin.registration-fees.settings')
            ->with('success', 'Registration fee configuration updated successfully.');
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:Cash,Bank Transfer,Cheque,Online,Other',
            'reference_number' => 'nullable|string|max:255',
        ]);

        $member = User::findOrFail($validated['user_id']);
        $fee = $member->registrationFee ?: $this->registrationFeeService->createObligationForMember($member);

        $payment = $this->registrationFeeService->recordPayment(
            $fee,
            (float) $validated['amount'],
            $validated['payment_date'],
            $validated['payment_method'],
            $validated['reference_number'] ?? null,
            auth()->id()
        );

        return redirect()->back()->with('success', "Payment recorded successfully. Receipt #: {$payment->receipt_number}");
    }

    public function cancelPayment(Request $request, RegistrationFeePayment $payment)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        $this->registrationFeeService->cancelPayment($payment, $validated['cancellation_reason'], auth()->id());

        return redirect()->back()->with('success', 'Payment cancelled successfully.');
    }

    public function reconcile(Request $request, User $member)
    {
        $validated = $request->validate([
            'status' => 'required|in:unpaid,partially_paid,fully_paid,requires_verification,cancelled',
            'fee_amount' => 'nullable|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->registrationFeeService->reconcileMember(
            $member,
            $validated['status'],
            isset($validated['fee_amount']) ? (float) $validated['fee_amount'] : null,
            (float) ($validated['total_paid'] ?? 0.00),
            $validated['notes'] ?? null
        );

        return redirect()->back()->with('success', "Member registration fee reconciled successfully.");
    }

    public function receipt(RegistrationFeePayment $payment)
    {
        $payment->load(['user', 'registrationFee', 'recorder']);
        
        // Calculate previous payment total prior to this payment
        $previousPaid = RegistrationFeePayment::where('registration_fee_id', $payment->registration_fee_id)
            ->where('status', 'completed')
            ->where('id', '<', $payment->id)
            ->sum('amount');

        return view('receipts.registration_fee', compact('payment', 'previousPaid'));
    }
}
