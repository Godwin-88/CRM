<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCatalogItemVersion extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'service_catalog_item_id',
        'version_number',
        'fields',
        'required_documents',
        'automation_config',
        'customer_instructions',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'required_documents' => 'array',
        'automation_config' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalogItem::class, 'service_catalog_item_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
