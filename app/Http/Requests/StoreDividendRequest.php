<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDividendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investment_id' => 'required|exists:investments,id',
            'total_dividend_amount' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'investment_id.required' => 'Please select an investment.',
            'investment_id.exists' => 'The selected investment is invalid.',
            'total_dividend_amount.required' => 'Please enter the total dividend amount.',
            'total_dividend_amount.min' => 'Dividend amount must be a positive value.',
        ];
    }
}