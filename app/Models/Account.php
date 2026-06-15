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
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Account extends Model implements HasMedia
{
    use HasComments, HasFactory, HasUlids, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name',
        'type',
        'industry',
        'status',
        'website',
        'phone',
        'city',
        'state',
        'country',
        'annual_revenue',
        'employee_count',
        'parent_account_id',
        'account_manager_id',
    ];

    protected $casts = [
        'annual_revenue' => 'decimal:2',
        'employee_count' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────

    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    public function subAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_account')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryContact()
    {
        return $this->belongsToMany(Contact::class, 'contact_account')
            ->wherePivot('is_primary', true)
            ->withTimestamps();
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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getTotalInvoicedAttribute(): float
    {
        return (float) $this->invoices()->sum('total');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) Payment::whereHas('invoice', fn ($q) => $q->where('account_id', $this->id))->sum('amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->getTotalInvoicedAttribute() - $this->getTotalPaidAttribute();
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'customizable');
    }

    public function activitiesLog(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
