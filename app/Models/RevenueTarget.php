<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueTarget extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'team_id',
        'created_by',
        'period',
        'period_start',
        'period_end',
        'target_revenue',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'target_revenue' => 'decimal:2',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
