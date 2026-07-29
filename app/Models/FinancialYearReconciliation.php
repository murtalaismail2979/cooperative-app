<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialYearReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'total_business_profit',
        'total_financing_profit',
        'total_recognized_loss',
        'net_sharable_profit',
        'cooperative_amount',
        'management_amount',
        'adjusted_member_pool',
        'total_distributed',
        'total_loss_adjustment',
        'total_over_distributed',
        'total_outstanding_payable',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'total_business_profit' => 'decimal:2',
        'total_financing_profit' => 'decimal:2',
        'total_recognized_loss' => 'decimal:2',
        'net_sharable_profit' => 'decimal:2',
        'cooperative_amount' => 'decimal:2',
        'management_amount' => 'decimal:2',
        'adjusted_member_pool' => 'decimal:2',
        'total_distributed' => 'decimal:2',
        'total_loss_adjustment' => 'decimal:2',
        'total_over_distributed' => 'decimal:2',
        'total_outstanding_payable' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function adjustments()
    {
        return $this->hasMany(DividendAdjustment::class, 'reconciliation_id');
    }
}
