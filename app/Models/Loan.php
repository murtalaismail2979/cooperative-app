<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'principal_amount', 'profit_rate', 'total_amount',
        'monthly_payment', 'duration_months', 'remaining_months',
        'date_granted', 'status', 'approved_by',
    ];

    protected $casts = [
        'date_granted' => 'date',
        'principal_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function loanRequest()
    {
        return $this->hasOne(LoanRequest::class);
    }

    public function getOutstandingBalanceAttribute()
    {
        if (array_key_exists('repayments_sum_amount', $this->attributes)) {
            return (float) $this->total_amount - (float) ($this->attributes['repayments_sum_amount'] ?? 0);
        }
        if ($this->relationLoaded('repayments')) {
            return (float) $this->total_amount - (float) $this->repayments->sum('amount');
        }
        $totalPaid = (float) $this->repayments()->sum('amount');
        return (float) $this->total_amount - $totalPaid;
    }

    public function getProfitAmountAttribute(): float
    {
        return (float) $this->principal_amount * ((float) $this->profit_rate / 100);
    }
}