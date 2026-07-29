<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DividendAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_id',
        'user_id',
        'year',
        'original_dividend_amount',
        'loss_adjustment_amount',
        'final_entitlement_amount',
        'amount_already_paid',
        'overpayment_amount',
        'underpayment_amount',
        'amount_recovered',
        'status',
        'recovery_method',
        'notes',
    ];

    protected $casts = [
        'original_dividend_amount' => 'decimal:2',
        'loss_adjustment_amount' => 'decimal:2',
        'final_entitlement_amount' => 'decimal:2',
        'amount_already_paid' => 'decimal:2',
        'overpayment_amount' => 'decimal:2',
        'underpayment_amount' => 'decimal:2',
        'amount_recovered' => 'decimal:2',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(FinancialYearReconciliation::class, 'reconciliation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recoveryPayments()
    {
        return $this->hasMany(DividendRecoveryPayment::class);
    }

    public function getOutstandingRecoveryAttribute(): float
    {
        return max(0.00, (float) $this->overpayment_amount - (float) $this->amount_recovered);
    }
}
