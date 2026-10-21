<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isApprovedMembership();
    }

    public function rules(): array
    {
        return [
            'amount_requested' => 'required|numeric|min:1000',
            'duration_months' => 'required|integer|between:1,12',
            'purpose' => 'required|string|min:3|max:1000',
            'agreement' => 'accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'amount_requested.required' => 'Please enter the financing amount you wish to request.',
            'amount_requested.numeric' => 'The financing amount must be a valid number.',
            'amount_requested.min' => 'The minimum financing request is ₦1,000.',
            'duration_months.required' => 'Please select your preferred repayment duration.',
            'duration_months.between' => 'Please select a valid duration period between 1 and 12 months.',
            'purpose.required' => 'Please provide the purpose or reason for your financing request.',
            'agreement.accepted' => 'You must agree to the cooperative financing terms and monthly repayment schedule.',
        ];
    }
}
