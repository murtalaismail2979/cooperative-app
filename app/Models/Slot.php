<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_number',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'slot_number' => 'integer',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}