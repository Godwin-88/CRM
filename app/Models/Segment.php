<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'type', // demographic, psychographic, behavioral, geographic
        'criteria', // JSON filter rules
        'join_operator', // 'and' or 'or'
        'contact_count',
        'contact_count_cached_at',
    ];

    protected $casts = [
        'criteria' => 'array',
        'contact_count' => 'integer',
        'contact_count_cached_at' => 'datetime',
    ];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_segments');
    }
}
