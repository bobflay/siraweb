<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'routing_id',
        'client_id',
        'zone_id',
        'sequence_order',
        'planned_at',
        'visit_id',
        'status',
        'overridden',
        'override_reason',
        'overridden_by',
        'overridden_at',
    ];

    protected $casts = [
        'planned_at' => 'datetime',
        'overridden' => 'boolean',
        'overridden_at' => 'datetime',
    ];

    public function routing(): BelongsTo
    {
        return $this->belongsTo(Routing::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
