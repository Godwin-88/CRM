<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSchemaVersion extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'form_schema_id',
        'version_number',
        'fields',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function schema(): BelongsTo
    {
        return $this->belongsTo(FormSchema::class, 'form_schema_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class, 'form_schema_version_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
