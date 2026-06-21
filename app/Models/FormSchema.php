<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FormSchema extends Model
{
    use HasFactory, HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    protected $fillable = [
        'name',
        'slug',
        'owner_type',
        'owner_id',
        'description',
        'created_by_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FormSchemaVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(FormSchemaVersion::class)->latestOfMany('version_number');
    }

    public function responses(): HasMany
    {
        return $this->hasManyThrough(FormResponse::class, FormSchemaVersion::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
