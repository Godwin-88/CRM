<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'asset_id',
        'assigned_to',
        'assigned_to_account',
        'assignment_start_date',
        'expected_return_date',
        'returned_at',
        'condition_note',
        'requires_maintenance',
    ];

    protected $casts = [
        'assignment_start_date' => 'date',
        'expected_return_date' => 'date',
        'returned_at' => 'date',
        'requires_maintenance' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'assigned_to_account');
    }
}
