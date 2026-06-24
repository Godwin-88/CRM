<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const TYPE_HARDWARE = 'hardware';

    public const TYPE_SOFTWARE = 'software';

    public const TYPE_VEHICLE = 'vehicle';

    public const TYPE_FURNITURE = 'furniture';

    public const TYPE_CUSTOM = 'custom';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_MAINTENANCE = 'under_maintenance';

    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'name',
        'type',
        'identifier',
        'purchase_date',
        'purchase_cost',
        'currency',
        'status',
        'useful_life_years',
        'total_quantity',
        'available_quantity',
        'minimum_threshold',
        'assigned_to',
        'assigned_to_account',
        'assignment_start_date',
        'expected_return_date',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'assignment_start_date' => 'date',
        'expected_return_date' => 'date',
        'total_quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'minimum_threshold' => 'decimal:2',
    ];

    protected $appends = ['book_value', 'depreciation'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'assigned_to_account');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function getBookValueAttribute(): ?float
    {
        $purchaseCost = $this->purchase_cost ? (float) $this->purchase_cost : null;
        $usefulLife = $this->useful_life_years ? (int) $this->useful_life_years : null;

        if (! $purchaseCost || ! $usefulLife || ! $this->purchase_date) {
            return $purchaseCost;
        }

        $yearsSincePurchase = $this->purchase_date->diffInYears(now());
        $annualDepreciation = $purchaseCost / $usefulLife;

        return max(0, (float) ($purchaseCost - ($annualDepreciation * $yearsSincePurchase)));
    }

    public function getDepreciationAttribute(): ?float
    {
        $purchaseCost = $this->purchase_cost ? (float) $this->purchase_cost : null;

        if (! $purchaseCost || ! $this->useful_life_years) {
            return null;
        }

        return (float) ($purchaseCost - $this->book_value);
    }

    public function getLastAssignmentDateAttribute(): ?string
    {
        $lastAssignment = $this->assignments()->latest()->first();

        return $lastAssignment?->assignment_start_date?->toDateString();
    }
}
