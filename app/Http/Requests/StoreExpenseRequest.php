<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => 'required|in:administrative,operational,utilities,business',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'investment_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('investments', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                })
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Please select an expense category.',
            'category.in' => 'Invalid category selected.',
            'description.required' => 'Please provide a description.',
            'amount.required' => 'Please enter the expense amount.',
            'amount.min' => 'Amount must be at least ₦1.',
            'expense_date.required' => 'Please select the expense date.',
        ];
    }
}