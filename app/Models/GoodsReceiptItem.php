<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'goods_receipt_id',
        'po_line_item_id',
        'received_quantity',
    ];

    protected $casts = [
        'received_quantity' => 'decimal:2',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(PoLineItem::class, 'po_line_item_id');
    }
}
