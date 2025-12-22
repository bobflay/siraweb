<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Zone extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'base_commerciale_id',
        'city',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function baseCommerciale(): BelongsTo
    {
        return $this->belongsTo(BaseCommerciale::class, 'base_commerciale_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'zone_user', 'zone_id', 'user_id');
    }
}
