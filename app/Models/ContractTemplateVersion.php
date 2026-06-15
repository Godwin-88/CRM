<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTemplateVersion extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'contract_template_id',
        'version_number',
        'content',
        'change_summary',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'change_summary' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
