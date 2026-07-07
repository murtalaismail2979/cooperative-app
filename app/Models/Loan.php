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

    public function getOutstandingBalanceAttribute()
    {
        $totalPaid = $this->repayments()->sum('amount');
        return $this->total_amount - $totalPaid;
    }
}