<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'account_id',
        'contact_id',
        'invoice_number',
        'status',
        'currency',
        'subtotal',
        'total_tax',
        'total',
        'due_date',
        'pdf_path',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(Deal::class, 'invoice_deal');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->total - $this->getTotalPaidAttribute();
    }

    public function updateStatusBasedOnPayments(): void
    {
        if ($this->status === self::STATUS_SENT && $this->due_date->isPast() && $this->getTotalPaidAttribute() < $this->total) {
            $this->status = self::STATUS_OVERDUE;
            $this->save();
        } elseif ($this->total > 0) {
            $paid = $this->getTotalPaidAttribute();
            if ($paid >= $this->total) {
                $this->status = self::STATUS_PAID;
                $this->paid_at = $this->paid_at ?? now();
            } elseif ($paid > 0) {
                $this->status = self::STATUS_PARTIALLY_PAID;
            }
            $this->save();
        }
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('created_at', now()->year)
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->orderByDesc('invoice_number')
            ->first();

        $sequence = $last ? (int) substr($last->invoice_number, -4) + 1 : 1;

        return "INV-{$year}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
