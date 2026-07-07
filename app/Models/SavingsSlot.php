<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slot_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function monthlySavings()
    {
        return $this->hasMany(MonthlySaving::class);
    }
}