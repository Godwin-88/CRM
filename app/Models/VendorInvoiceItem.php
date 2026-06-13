<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorInvoiceItem extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'vendor_invoice_id',
        'po_line_item_id',
        'invoiced_quantity',
    ];

    protected $casts = [
        'invoiced_quantity' => 'decimal:2',
    ];

    public function vendorInvoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class);
    }

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(PoLineItem::class, 'po_line_item_id');
    }
}
