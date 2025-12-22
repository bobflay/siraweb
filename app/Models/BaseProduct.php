<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaseProduct extends Model
{
    protected $fillable = [
        'base_commerciale_id',
        'product_id',
        'sku_base',
        'current_price',
        'allow_discount',
        'is_active',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'allow_discount' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function baseCommerciale(): BelongsTo
    {
        return $this->belongsTo(BaseCommerciale::class, 'base_commerciale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
