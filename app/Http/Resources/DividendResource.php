<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DividendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'total_dividend_amount' => (float) $this->total_dividend_amount,
            'total_units' => (int) $this->total_units,
            'unit_value' => (float) $this->unit_value,
            'distributed_at' => $this->distributed_at?->toIso8601String(),
            'distributed_by' => $this->distributed_by,
            'investment_id' => $this->investment_id,
            'original_sharable_profit' => (float) $this->original_sharable_profit,
            'cooperative_amount' => (float) $this->cooperative_amount,
            'management_amount' => (float) $this->management_amount,
            'member_distribution_pool' => (float) $this->member_distribution_pool,
            'investment' => $this->whenLoaded('investment', function () {
                return [
                    'id' => $this->investment->id,
                    'name' => $this->investment->name,
                    'type' => $this->investment->type,
                    'capital_amount' => (float) $this->investment->capital_amount,
                    'status' => $this->investment->status,
                ];
            }),
            'distributor' => $this->whenLoaded('distributor', function () {
                return [
                    'id' => $this->distributor->id,
                    'name' => $this->distributor->name,
                    'email' => $this->distributor->email,
                ];
            }),
            'payouts' => $this->whenLoaded('payouts', function () {
                return $this->payouts->map(function ($payout) {
                    return [
                        'id' => $payout->id,
                        'user_id' => $payout->user_id,
                        'member_name' => $payout->user?->name,
                        'member_code' => $payout->user?->member_code,
                        'units' => (int) $payout->units,
                        'amount' => (float) $payout->amount,
                        'paid' => (bool) $payout->paid,
                        'paid_date' => $payout->paid_date?->format('Y-m-d'),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
