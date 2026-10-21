<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'cooperative_id',
        'amount_requested',
        'duration_months',
        'purpose',
        'member_total_savings_at_request',
        'max_eligible_amount_at_request',
        'status',
        'approved_amount',
        'approved_duration_months',
        'approved_profit_rate',
        'date_granted',
        'loan_id',
        'rejection_reason',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'amount_requested' => 'decimal:2',
        'member_total_savings_at_request' => 'decimal:2',
        'max_eligible_amount_at_request' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'approved_profit_rate' => 'decimal:2',
        'date_granted' => 'date',
        'reviewed_at' => 'datetime',
        'duration_months' => 'integer',
        'approved_duration_months' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getEstimatedProfitRateAttribute(): float
    {
        if ($this->approved_profit_rate !== null) {
            return (float) $this->approved_profit_rate;
        }
        return \App\Services\LoanRequestService::getProfitRateForDuration((int) $this->duration_months);
    }

    public function getEstimatedProfitAttribute(): float
    {
        $rate = $this->estimated_profit_rate;
        $principal = (float) ($this->approved_amount ?? $this->amount_requested);
        return $principal * ($rate / 100);
    }

    public function getEstimatedTotalAmountAttribute(): float
    {
        $principal = (float) ($this->approved_amount ?? $this->amount_requested);
        return $principal + $this->estimated_profit;
    }

    public function getEstimatedMonthlyPaymentAttribute(): float
    {
        $duration = (int) ($this->approved_duration_months ?? $this->duration_months);
        if ($duration <= 0) {
            return 0.0;
        }
        return $this->estimated_total_amount / $duration;
    }
}
