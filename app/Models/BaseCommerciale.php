<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BaseCommerciale extends Model
{
    use HasFactory;
    protected $table = 'bases_commerciales';

    protected $fillable = [
        'code',
        'name',
        'description',
        'city',
        'region',
        'latitude',
        'longitude',
        'default_currency',
        'default_tax_rate',
        'allow_discount',
        'max_discount_percent',
        'order_cutoff_time',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'default_tax_rate' => 'decimal:2',
        'max_discount_percent' => 'decimal:2',
        'allow_discount' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'base_commerciale_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'base_user', 'base_commerciale_id', 'user_id');
    }
}
