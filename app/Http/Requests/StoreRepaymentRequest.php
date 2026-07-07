<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the repayment amount.',
            'amount.min' => 'Repayment amount must be at least ₦1.',
            'payment_date.required' => 'Please select the payment date.',
        ];
    }
}