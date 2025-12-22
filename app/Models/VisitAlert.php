<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'visit_report_id',
        'user_id',
        'client_id',
        'base_commerciale_id',
        'zone_id',
        'type',
        'comment',
        'custom_type',
        'latitude',
        'longitude',
        'alerted_at',
        'status',
        'handled_by',
        'handled_at',
        'handling_comment',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'alerted_at' => 'datetime',
        'handled_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function visitReport()
    {
        return $this->belongsTo(VisitReport::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function baseCommerciale()
    {
        return $this->belongsTo(BaseCommerciale::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function photos()
    {
        return $this->morphMany(VisitPhoto::class, 'photoable');
    }
}
