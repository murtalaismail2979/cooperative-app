<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRunningChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'month' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'Please select a member.',
            'amount.required' => 'Please enter the charge amount.',
            'amount.min' => 'Charge amount must be at least ₦1.',
            'description.required' => 'Please provide a description.',
            'month.required' => 'Please select the month.',
        ];
    }
}