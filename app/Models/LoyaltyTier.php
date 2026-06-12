<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'program_id',
        'name',
        'min_points_threshold',
        'benefits',
        'sort_order',
    ];

    protected $casts = [
        'min_points_threshold' => 'integer',
        'benefits' => 'array',
        'sort_order' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}
