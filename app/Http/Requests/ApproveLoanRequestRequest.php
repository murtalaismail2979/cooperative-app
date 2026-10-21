<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLoanRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isManagementRole();
    }

    public function rules(): array
    {
        return [
            'approved_amount' => 'nullable|numeric|min:1000',
            'approved_duration_months' => 'nullable|integer|between:1,12',
            'approved_profit_rate' => 'nullable|numeric|min:0|max:100',
            'date_granted' => 'nullable|date',
            'admin_notes' => 'nullable|string|max:1000',
        ];
    }
}
