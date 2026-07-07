<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'description', 'capital_amount',
        'total_returns', 'start_date', 'end_date', 'status', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'capital_amount' => 'decimal:2',
        'total_returns' => 'decimal:2',
    ];

    public function returns()
    {
        return $this->hasMany(InvestmentReturn::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getApprovedExpensesAttribute()
    {
        return (float) $this->expenses()->where('status', 'approved')->sum('amount');
    }

    public function getSharableProfitAttribute()
    {
        return (float) $this->total_returns - $this->approved_expenses;
    }

    public function getNetReturnsAttribute()
    {
        return $this->sharable_profit;
    }

    public function getRoiAttribute()
    {
        if ($this->capital_amount > 0) {
            return round(($this->sharable_profit / $this->capital_amount) * 100, 2);
        }
        return 0;
    }

    public function getProfitAttribute()
    {
        return $this->sharable_profit - $this->capital_amount;
    }

    public function dividend()
    {
        return $this->hasOne(Dividend::class);
    }

    public function investmentType()
    {
        return $this->belongsTo(InvestmentType::class, 'type', 'slug');
    }
}