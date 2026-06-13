<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'deal_id',
        'quote_template_id',
        'created_by',
        'status',
        'total',
        'pdf_path',
        'shareable_link',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'quote_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuoteLineItem::class);
    }
}
