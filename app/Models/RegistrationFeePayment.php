<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationFeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_fee_id',
        'user_id',
        'receipt_number',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function registrationFee()
    {
        return $this->belongsTo(RegistrationFee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
