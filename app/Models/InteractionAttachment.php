<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionAttachment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'interaction_id',
        'filename',
        'mime_type',
        'size_bytes',
        'storage_path',
        'disk',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function getUrlAttribute(): string
    {
        return \Storage::disk($this->disk)->url($this->storage_path);
    }
}