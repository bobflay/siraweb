<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Routing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'base_commerciale_id',
        'zone_id',
        'route_date',
        'start_time',
        'status',
        'created_by',
    ];

    protected $casts = [
        'route_date' => 'date',
        'start_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function baseCommerciale(): BelongsTo
    {
        return $this->belongsTo(BaseCommerciale::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function routingItems(): HasMany
    {
        return $this->hasMany(RoutingItem::class);
    }
}
