<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\HasComments;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contact extends Model implements HasMedia
{
    use HasComments, HasFactory, HasUlids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'type',
        'status',
        'source',
        'owner_id',
        'clv_score',
        'ltv',
        'churn_risk_score',
        'last_activity_at',
        'loyalty_tier',
        'preferred_channel',
        'score',
        'marketing_consent',
        'data_processing_consent',
        'consent_timestamp',
        'national_id',
    ];

    protected $casts = [
        'clv_score' => 'decimal:2',
        'score' => 'integer',
        'marketing_consent' => 'boolean',
        'data_processing_consent' => 'boolean',
        'consent_timestamp' => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeExcludeDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    // ─── Relationships ────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'contact_account')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    // Polymorphic custom field values
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'customizable');
    }

    // Activity log
    public function activitiesLog(): MorphMany
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function loyaltyEnrollments(): HasMany
    {
        return $this->hasMany(LoyaltyEnrollment::class);
    }

    public function pointsLedger(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function onboardingRecords(): HasMany
    {
        return $this->hasMany(OnboardingRecord::class);
    }

    public function journeyCompletions(): HasMany
    {
        return $this->hasMany(JourneyCompletion::class);
    }

    public function reactivationContacts(): HasMany
    {
        return $this->hasMany(ReactivationContact::class);
    }

    public function clvCalculation(): HasMany
    {
        return $this->hasMany(ClvCalculation::class);
    }
}
