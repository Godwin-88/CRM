<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateLimit extends Model
{
    protected $fillable = [
        'key',
        'max_requests',
        'window_seconds',
    ];
}
