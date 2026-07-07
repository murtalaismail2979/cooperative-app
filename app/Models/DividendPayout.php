<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DividendPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'dividend_id', 'user_id', 'units', 'amount', 'paid', 'paid_date',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function dividend()
    {
        return $this->belongsTo(Dividend::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}