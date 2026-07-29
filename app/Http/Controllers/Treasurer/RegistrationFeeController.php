<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RegistrationFeePayment;
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

        foreach ($members as $member) {
            if (!$member->registrationFee) {
                $this->registrationFeeService->createObligationForMember($member);
                $member->load('registrationFee');
            }
        }

        $stats = $this->registrationFeeService->getDashboardStats();

        return view('treasurer.registration_fees.index', compact('members', 'search', 'status', 'stats'));
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

    public function receipt(RegistrationFeePayment $payment)
    {
        $payment->load(['user', 'registrationFee', 'recorder']);
        
        $previousPaid = RegistrationFeePayment::where('registration_fee_id', $payment->registration_fee_id)
            ->where('status', 'completed')
            ->where('id', '<', $payment->id)
            ->sum('amount');

        return view('receipts.registration_fee', compact('payment', 'previousPaid'));
    }
}
