<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'principal_amount' => 'required|numeric|min:1',
            'profit_rate' => 'nullable|numeric|min:0',
            'duration_months' => 'nullable|integer|min:1',
            'date_granted' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Please select a member.',
            'user_id.exists' => 'Selected member does not exist.',
            'principal_amount.required' => 'Please enter the loan amount.',
            'principal_amount.min' => 'Loan amount must be at least ₦1.',
            'profit_rate.numeric' => 'Profit rate must be a number.',
            'profit_rate.min' => 'Profit rate must be at least 0%.',
            'duration_months.integer' => 'Installments must be a whole number.',
            'duration_months.min' => 'Installments must be at least 1 month.',
            'date_granted.required' => 'Please select the loan date.',
        ];
    }
}