<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => 'required|exists:users,id',
            'month' => 'required|date',
            'slots' => 'required|array',
            'slots.*' => 'exists:savings_slots,id',
            'payment_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'Please select a member.',
            'member_id.exists' => 'Selected member does not exist.',
            'month.required' => 'Please select the month.',
            'slots.required' => 'Please select at least one savings slot.',
            'payment_date.required' => 'Please enter the contribution date.',
            'payment_date.date' => 'The contribution date is not a valid date.',
        ];
    }
}