<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Invoice;

class Deal extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'account_id',
        'contact_id',
        'stage',
        'value',
        'currency',
        'probability',
        'expected_close_date',
        'pipeline_id',
        'owner_id',
        'win_loss_reason_id',
        'win_loss_note',
        'exclude_from_automations',
        'predicted_score',
        'manual_score',
        'score_override_note',
        'score_last_calculated_at',
        'product_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'exclude_from_automations' => 'boolean',
        'predicted_score' => 'integer',
        'manual_score' => 'integer',
        'score_last_calculated_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function winLossReason(): BelongsTo
    {
        return $this->belongsTo(WinLossReason::class, 'win_loss_reason_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DealComment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function demoTrials(): HasMany
    {
        return $this->hasMany(DemoTrial::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getWeightedValue(): float
    {
        return (float) $this->value * ($this->probability / 100);
    }

    public function isClosedWon(): bool
    {
        return str_starts_with($this->stage, 'closed_won');
    }

    public function isClosedLost(): bool
    {
        return str_starts_with($this->stage, 'closed_lost');
    }

    public function isClosed(): bool
    {
        return $this->isClosedWon() || $this->isClosedLost();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
