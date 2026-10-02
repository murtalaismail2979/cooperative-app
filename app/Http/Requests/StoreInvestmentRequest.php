<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = \App\Models\InvestmentType::pluck('slug')->toArray();
        if (empty($types)) {
            $types = ['buying_selling_goods', 'agriculture', 'financing'];
        }

        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', $types),
            'description' => 'nullable|string',
            'capital_amount' => 'required|numeric|min:1',
            'quantity' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the investment name.',
            'type.required' => 'Please select the investment type.',
            'type.in' => 'Invalid investment type selected.',
            'capital_amount.required' => 'Please enter the capital amount.',
            'capital_amount.min' => 'Capital amount must be at least ₦1.',
            'start_date.required' => 'Please select the start date.',
        ];
    }
}