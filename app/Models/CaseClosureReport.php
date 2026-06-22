<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseClosureReport extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'case_record_id',
        'prepared_by_id',
        'summary',
        'root_cause',
        'resolution_details',
        'customer_facing_summary',
        'status',
        'prepared_at',
        'submitted_at',
    ];

    protected $casts = [
        'prepared_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function caseRecord(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_record_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }
}
