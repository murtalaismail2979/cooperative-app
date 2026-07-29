<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'import_type',
        'original_filename',
        'duplicate_mode',
        'total_rows',
        'successful_rows',
        'duplicate_rows',
        'invalid_rows',
        'status',
        'error_details',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'successful_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'invalid_rows' => 'integer',
        'error_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
