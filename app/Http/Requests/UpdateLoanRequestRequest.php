<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isManagementRole();
    }

    public function rules(): array
    {
        return [
            'amount_requested' => 'required|numeric|min:1000',
            'duration_months' => 'required|integer|between:1,12',
            'purpose' => 'nullable|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount_requested.required' => 'The requested amount is required.',
            'amount_requested.numeric' => 'The requested amount must be a valid number.',
            'amount_requested.min' => 'The requested amount must be at least ₦1,000.',
            'duration_months.required' => 'The payment duration is required.',
            'duration_months.between' => 'The payment duration must be between 1 and 12 months.',
        ];
    }
}
