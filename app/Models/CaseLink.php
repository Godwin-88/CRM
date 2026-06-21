<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CaseLink extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'case_record_id',
        'linkable_type',
        'linkable_id',
        'link_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function caseRecord(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_record_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
