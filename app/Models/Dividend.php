<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dividend extends Model
{
    use HasFactory;

    protected $fillable = [
        'year', 'total_dividend_amount', 'total_units',
        'unit_value', 'distributed_at', 'distributed_by', 'investment_id',
        'original_sharable_profit', 'cooperative_amount', 'management_amount', 'member_distribution_pool',
    ];

    protected $casts = [
        'distributed_at' => 'datetime',
        'total_dividend_amount' => 'decimal:2',
        'unit_value' => 'decimal:2',
        'original_sharable_profit' => 'decimal:2',
        'cooperative_amount' => 'decimal:2',
        'management_amount' => 'decimal:2',
        'member_distribution_pool' => 'decimal:2',
    ];

    public function getOriginalSharableProfitAttribute($value)
    {
        return $value ?? $this->total_dividend_amount;
    }

    public function getCooperativeAmountAttribute($value)
    {
        return $value ?? '0.00';
    }

    public function getManagementAmountAttribute($value)
    {
        return $value ?? '0.00';
    }

    public function getMemberDistributionPoolAttribute($value)
    {
        return $value ?? $this->total_dividend_amount;
    }

    public function payouts()
    {
        return $this->hasMany(DividendPayout::class);
    }

    public function distributor()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }
}