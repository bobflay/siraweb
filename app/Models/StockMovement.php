<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'stock_commercial_id',
        'user_id',
        'product_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function stockCommercial(): BelongsTo
    {
        return $this->belongsTo(StockCommercial::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the related reference (Invoice or Order)
     */
    public function reference()
    {
        if ($this->reference_type === 'invoice') {
            return $this->belongsTo(Invoice::class, 'reference_id');
        } elseif ($this->reference_type === 'order') {
            return $this->belongsTo(Order::class, 'reference_id');
        }
        return null;
    }
}
