<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fee_amount',
        'total_paid',
        'status',
        'notes',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(RegistrationFeePayment::class);
    }

    public function completedPayments()
    {
        return $this->hasMany(RegistrationFeePayment::class)->where('status', 'completed');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return max(0.00, (float)$this->fee_amount - (float)$this->total_paid);
    }

    /**
     * Recalculates total paid and status based on active completed payments.
     */
    public function updatePaymentTotals(): void
    {
        $sum = (float) $this->completedPayments()->sum('amount');
        $this->total_paid = $sum;

        if ($this->status !== 'requires_verification' && $this->status !== 'cancelled') {
            if ($sum <= 0) {
                $this->status = 'unpaid';
            } elseif ($sum >= (float) $this->fee_amount) {
                $this->status = 'fully_paid';
            } else {
                $this->status = 'partially_paid';
            }
        }
        $this->save();
    }
}
