<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealthLog extends Model
{
    protected $fillable = [
        'check_type',
        'status',
        'message',
        'details',
        'checked_at',
    ];

    protected $casts = [
        'details' => 'array',
        'checked_at' => 'datetime',
    ];
}