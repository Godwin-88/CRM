<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingRecord extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'template_id',
        'contact_id',
        'account_id',
        'status',
        'percentage_complete',
        'enrolled_at',
        'completed_at',
        'enroled_by',
    ];

    protected $casts = [
        'percentage_complete' => 'integer',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function enroledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enroled_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OnboardingActivity::class);
    }
}
