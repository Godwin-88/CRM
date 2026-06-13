<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorInvoice extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'purchase_order_id',
        'vendor_invoice_number',
        'total',
        'invoice_date',
        'pdf_path',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'invoice_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorInvoiceItem::class);
    }
}
