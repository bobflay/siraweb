<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'type',
        'potential',
        'base_commerciale_id',
        'zone_id',
        'created_by',
        'manager_name',
        'phone',
        'whatsapp',
        'email',
        'city',
        'district',
        'address_description',
        'latitude',
        'longitude',
        'visit_frequency',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['last_visit_date', 'has_open_alert'];

    // Relationships
    public function baseCommerciale(): BelongsTo
    {
        return $this->belongsTo(BaseCommerciale::class, 'base_commerciale_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(VisitAlert::class);
    }

    /**
     * Assigned commercials (many-to-many)
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user')
            ->withPivot(['assigned_by', 'assigned_at', 'role', 'active'])
            ->withTimestamps()
            ->wherePivot('active', true)
            ->using(ClientUser::class);
    }

    /**
     * All user assignments including inactive (for audit trail)
     */
    public function allUserAssignments(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user')
            ->withPivot(['assigned_by', 'assigned_at', 'role', 'active'])
            ->withTimestamps()
            ->using(ClientUser::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(VisitPhoto::class, 'photoable');
    }

    // Accessors
    public function getLastVisitDateAttribute()
    {
        return $this->visits()->latest('started_at')->value('started_at');
    }

    public function getHasOpenAlertAttribute(): bool
    {
        return $this->alerts()->where('status', 'open')->exists();
    }

    // Query Scopes
    public function scopeForUser(Builder $query, User $user): Builder
    {
        // Super Admin, Admin or Direction: see all clients
        if ($user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction')) {
            return $query;
        }

        // Responsable de base: see clients in their commercial bases
        if ($user->hasRole('ROLE_BASE_MANAGER') || $user->hasRole('responsable_base')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $query->whereIn('base_commerciale_id', $baseIds);
        }

        // Commercial: see only assigned clients (pivot-based)
        if ($user->hasRole('ROLE_AGENT') || $user->hasRole('commercial')) {
            return $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('manager_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('whatsapp', 'like', "%{$search}%");
        });
    }

    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        if (empty($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public function scopeFilterByCity(Builder $query, ?string $city): Builder
    {
        if (empty($city)) {
            return $query;
        }

        return $query->where('city', $city);
    }

    public function scopeFilterByZone(Builder $query, ?int $zoneId): Builder
    {
        if (empty($zoneId)) {
            return $query;
        }

        return $query->where('zone_id', $zoneId);
    }

    public function scopeFilterByCommercial(Builder $query, ?int $commercialId): Builder
    {
        if (empty($commercialId)) {
            return $query;
        }

        return $query->where('created_by', $commercialId);
    }

    public function scopeFilterByAlert(Builder $query, ?bool $hasAlert): Builder
    {
        if ($hasAlert === null) {
            return $query;
        }

        if ($hasAlert) {
            return $query->whereHas('alerts', function ($q) {
                $q->where('status', 'open');
            });
        }

        return $query->whereDoesntHave('alerts', function ($q) {
            $q->where('status', 'open');
        });
    }

    public function scopeUpdatedAfter(Builder $query, ?string $datetime): Builder
    {
        if (empty($datetime)) {
            return $query;
        }

        return $query->where('updated_at', '>=', $datetime);
    }
}
