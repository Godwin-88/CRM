<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskIntegration extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'name',
        'kiosk_id',
        'api_key',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
