<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'base_product_id',
        'product_name_snapshot',
        'sku_snapshot',
        'unit_snapshot',
        'packaging_snapshot',
        'unit_price_snapshot',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price_snapshot' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function baseProduct(): BelongsTo
    {
        return $this->belongsTo(BaseProduct::class);
    }
}
