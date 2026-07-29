<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LossCarryForward extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_year',
        'to_year',
        'unabsorbed_loss_amount',
        'absorbed_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'unabsorbed_loss_amount' => 'decimal:2',
        'absorbed_amount' => 'decimal:2',
    ];

    public function getRemainingUnabsorbedAttribute(): float
    {
        return max(0.00, (float) $this->unabsorbed_loss_amount - (float) $this->absorbed_amount);
    }
}
