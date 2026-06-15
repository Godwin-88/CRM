<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractVersion extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'version_number',
        'status',
        'document_path',
        'page_count',
        'file_size',
        'created_by',
        'variables',
        'selected_clauses',
        'notes',
        'signed_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'selected_clauses' => 'array',
        'page_count' => 'integer',
        'file_size' => 'integer',
        'signed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(ContractSignatory::class);
    }
}
