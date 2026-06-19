<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const CATEGORY_GOODS = 'goods';

    public const CATEGORY_SERVICES = 'services';

    public const CATEGORY_BOTH = 'both';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLACKLISTED = 'blacklisted';

    protected $fillable = [
        'name',
        'category',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'registration_number',
        'tax_identification_number',
        'account_name',
        'account_number',
        'bank_name',
        'branch_code',
        'swift_code',
        'physical_address',
        'website',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'account_number' => 'encrypted',
    ];

    protected $hidden = [
        'account_number',
    ];

    protected $appends = [
        'has_financials_access',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(VendorRating::class);
    }

    public function getOverallRatingAttribute(): ?float
    {
        $avg = $this->ratings()->avg('quality + delivery_timeliness + communication + value_for_money');

        return $avg ? round($avg / 4, 2) : null;
    }

    public function getHasFinancialsAccessAttribute(): bool
    {
        return auth()->check() && auth()->user()->can('viewFinancials', $this);
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        if (! $this->account_number) {
            return '';
        }

        return '****'.substr($this->account_number, -4);
    }
}
