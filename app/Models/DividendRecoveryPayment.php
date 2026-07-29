<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DividendRecoveryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'dividend_adjustment_id',
        'user_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function dividendAdjustment()
    {
        return $this->belongsTo(DividendAdjustment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
