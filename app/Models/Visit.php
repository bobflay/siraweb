<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_id',
        'user_id',
        'base_commerciale_id',
        'zone_id',
        'routing_item_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'status',
        'latitude',
        'longitude',
        'termination_distance',
        'terminated_outside_range',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'termination_distance' => 'decimal:2',
        'terminated_outside_range' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

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

    public function routingItem(): BelongsTo
    {
        return $this->belongsTo(RoutingItem::class);
    }

    public function report()
    {
        return $this->hasOne(VisitReport::class);
    }

    public function alerts()
    {
        return $this->hasMany(VisitAlert::class);
    }

    public function photos()
    {
        return $this->hasMany(VisitPhoto::class);
    }

    public function start(): void
    {
        $this->started_at = now();
        $this->status = 'started';
        $this->save();
    }

    public function complete(): void
    {
        $this->ended_at = now();
        $this->duration_seconds = $this->started_at->diffInSeconds($this->ended_at);
        $this->status = 'completed';
        $this->save();
    }

    public function abort(): void
    {
        $this->ended_at = now();
        $this->duration_seconds = $this->started_at->diffInSeconds($this->ended_at);
        $this->status = 'aborted';
        $this->save();
    }
}
