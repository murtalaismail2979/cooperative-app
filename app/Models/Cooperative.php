<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cooperative extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'primary_color',
        'secondary_color',
        'email',
        'phone',
        'address',
        'is_active',
        'enable_shares',
        'enable_savings',
        'base_slot_amount',
        'share_unit_price',
        'min_share_units',
        'dividend_allocation_basis',
        'hybrid_shares_percentage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enable_shares' => 'boolean',
        'enable_savings' => 'boolean',
        'base_slot_amount' => 'decimal:2',
        'share_unit_price' => 'decimal:2',
        'hybrid_shares_percentage' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
