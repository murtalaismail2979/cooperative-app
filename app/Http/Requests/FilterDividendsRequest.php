<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterDividendsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->isAdminOrTreasurer());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => 'nullable|integer|min:2000|max:2099',
            'investment_id' => 'nullable|integer|exists:investments,id',
            'investment_name' => 'nullable|string|max:255',
            'member_name' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:all,paid,unpaid,pending',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sort_by' => 'nullable|string|in:id,distributed_at,created_at,total_dividend_amount,year',
            'sort_dir' => 'nullable|string|in:asc,desc,ASC,DESC',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }
}
