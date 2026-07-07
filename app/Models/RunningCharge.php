<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunningCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'month',
        'payment_date',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'month' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}