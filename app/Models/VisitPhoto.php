<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'photoable_type',
        'photoable_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'type',
        'title',
        'description',
        'latitude',
        'longitude',
        'taken_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'taken_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function photoable()
    {
        return $this->morphTo();
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Backward compatibility helpers
     */
    public function visitReport()
    {
        return $this->photoable_type === 'App\Models\VisitReport'
            ? $this->photoable
            : null;
    }

    public function visitAlert()
    {
        return $this->photoable_type === 'App\Models\VisitAlert'
            ? $this->photoable
            : null;
    }
}
