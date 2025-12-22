<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'latitude',
        'longitude',
        'manager_present',
        'order_made',
        'needs_order',
        'order_reference',
        'order_estimated_amount',
        'stock_issues',
        'stock_shortage_observed',
        'competitor_activity',
        'competitor_activity_observed',
        'comments',
        'validated_at',
    ];

    protected $casts = [
        'manager_present' => 'boolean',
        'order_made' => 'boolean',
        'needs_order' => 'boolean',
        'stock_shortage_observed' => 'boolean',
        'competitor_activity_observed' => 'boolean',
        'order_estimated_amount' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'validated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function photos()
    {
        return $this->morphMany(VisitPhoto::class, 'photoable');
    }

    /**
     * Business methods
     */
    public function validateReport()
    {
        if (!$this->latitude || !$this->longitude) {
            throw new \Exception('GPS coordinates are required for validation');
        }

        $this->validated_at = now();
        $this->save();

        return $this;
    }

    public function isValidated()
    {
        return !is_null($this->validated_at);
    }

    /**
     * Get a human-readable title for this report
     */
    public function getHumanReadableTitleAttribute()
    {
        if (!$this->visit || !$this->visit->client) {
            return 'Report #' . $this->id;
        }

        $date = $this->validated_at
            ? $this->validated_at->format('d M Y')
            : ($this->created_at ? $this->created_at->format('d M Y') : 'N/A');

        return 'Report - ' . $this->visit->client->name . ' (' . $date . ')';
    }
}
