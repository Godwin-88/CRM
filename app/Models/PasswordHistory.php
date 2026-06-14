<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends Model
{
    protected $fillable = [
        'user_id',
        'password_hash',
    ];

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
