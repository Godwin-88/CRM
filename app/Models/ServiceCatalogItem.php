<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ServiceCatalogItem extends Model
{
    use HasFactory, HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'description',
        'customer_instructions',
        'default_priority',
        'default_team_id',
        'default_owner_role',
        'sla_policy_id',
        'intake_form_schema_id',
        'required_documents',
        'automation_config',
        'portal_visible',
        'email_visible',
        'kiosk_visible',
        'api_visible',
        'is_active',
        'is_agent_only',
        'created_by_id',
        'deactivated_at',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'automation_config' => 'array',
        'portal_visible' => 'boolean',
        'email_visible' => 'boolean',
        'kiosk_visible' => 'boolean',
        'api_visible' => 'boolean',
        'is_active' => 'boolean',
        'is_agent_only' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function defaultTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'default_team_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaDefinition::class, 'sla_policy_id');
    }

    public function intakeFormSchema(): BelongsTo
    {
        return $this->belongsTo(FormSchema::class, 'intake_form_schema_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ServiceCatalogItemVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ServiceCatalogItemVersion::class)->latestOfMany('version_number');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'catalog_item_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePortalVisible($query)
    {
        return $query->where('portal_visible', true)->where('is_agent_only', false);
    }
}
