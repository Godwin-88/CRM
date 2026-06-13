<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'po_number',
        'vendor_id',
        'status',
        'category',
        'currency',
        'subtotal',
        'total_tax',
        'total',
        'required_by_date',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'pdf_path',
        'received_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total' => 'decimal:2',
        'required_by_date' => 'date',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(PoLineItem::class, 'purchase_order_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function vendorInvoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public static function generatePoNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('created_at', now()->year)
            ->where('po_number', 'like', "PO-{$year}-%")
            ->orderByDesc('po_number')
            ->first();

        $sequence = $last ? (int) substr($last->po_number, -4) + 1 : 1;

        return "PO-{$year}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalReceivedAttribute(): float
    {
        return (float) $this->goodsReceiptItems()->sum('received_quantity');
    }

    public function getReceivedPercentageAttribute(): float
    {
        if ($this->lineItems->sum('quantity') == 0) {
            return 0;
        }

        return round($this->getTotalReceivedAttribute() / $this->lineItems->sum('quantity') * 100, 2);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasManyThrough(GoodsReceiptItem::class, GoodsReceipt::class);
    }
}
