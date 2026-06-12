<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportEmailAddress extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'support_email_addresses';

    protected $fillable = [
        'email',
        'default_category_id',
        'default_priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'default_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}