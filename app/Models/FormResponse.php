<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormResponse extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'form_schema_version_id',
        'formable_type',
        'formable_id',
        'submitted_by_id',
        'response_data',
        'field_snapshots',
    ];

    protected $casts = [
        'response_data' => 'array',
        'field_snapshots' => 'array',
    ];

    public function schemaVersion(): BelongsTo
    {
        return $this->belongsTo(FormSchemaVersion::class, 'form_schema_version_id');
    }

    public function formable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }
}
