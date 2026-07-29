<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
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

    public function index()
    {
        $user = auth()->user();
        $fee = $user->registrationFee ?: $this->registrationFeeService->createObligationForMember($user);
        $fee->load(['payments' => function ($q) {
            $q->latest();
        }]);

        return view('member.registration_fee', compact('user', 'fee'));
    }

    public function receipt(RegistrationFeePayment $payment)
    {
        if ($payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to receipt.');
        }

        $payment->load(['user', 'registrationFee', 'recorder']);
        
        $previousPaid = RegistrationFeePayment::where('registration_fee_id', $payment->registration_fee_id)
            ->where('status', 'completed')
            ->where('id', '<', $payment->id)
            ->sum('amount');

        return view('receipts.registration_fee', compact('payment', 'previousPaid'));
    }
}
