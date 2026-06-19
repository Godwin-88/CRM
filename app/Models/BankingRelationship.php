<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BankingRelationship extends Model implements HasMedia
{
    use HasFactory, HasUlids, SoftDeletes, InteractsWithMedia;

    public const TYPE_CURRENT_ACCOUNT = 'current_account';

    public const TYPE_CREDIT_FACILITY = 'credit_facility';

    public const TYPE_OVERDRAFT = 'overdraft';

    public const TYPE_TRADE_FINANCE = 'trade_finance';

    public const TYPE_TREASURY = 'treasury';

    protected $fillable = [
        'institution_name',
        'relationship_type',
        'relationship_manager_name',
        'relationship_manager_email',
        'relationship_manager_phone',
        'account_number',
        'account_name',
        'credit_limit',
        'facility_expiry_date',
        'interest_rate',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'facility_expiry_date' => 'date',
        'account_number' => 'encrypted',
    ];

    protected $hidden = [
        'account_number',
    ];

    public function notes(): HasMany
    {
        return $this->hasMany(BankingNote::class);
    }

    public function getFacilitiesExpiringSoonAttribute(): bool
    {
        return $this->facility_expiry_date && $this->facility_expiry_date->isBefore(now()->addDays(60));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk('r2')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword']);
    }
}
