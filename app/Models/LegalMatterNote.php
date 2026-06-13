<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalMatterNote extends Model
{
    use HasUlids;

    protected $fillable = [
        'legal_matter_id',
        'created_by',
        'body',
        'type',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(LegalMatter::class, 'legal_matter_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
