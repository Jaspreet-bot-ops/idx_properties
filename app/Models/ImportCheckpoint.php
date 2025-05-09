<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_type',
        'next_url',
        'total_processed',
        'last_run_at',
        'is_completed'
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'is_completed' => 'boolean',
    ];
}
