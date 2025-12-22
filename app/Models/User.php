<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword;

class User extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            \Log::info('User Model - Saving', [
                'user_id' => $user->id,
                'photo_value' => $user->photo,
                'photo_dirty' => $user->isDirty('photo'),
                'photo_original' => $user->getOriginal('photo'),
                'attributes' => $user->getAttributes(),
            ]);
        });

        static::saved(function ($user) {
            \Log::info('User Model - Saved', [
                'user_id' => $user->id,
                'photo_value' => $user->photo,
                'photo_raw' => $user->getRawOriginal('photo'),
                'attributes' => $user->getAttributes(),
            ]);
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'dob',
        'photo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    // protected $appends = ['photo_url'];


    public function isActive()
    {
        // Check if user has active role assignment
        return $this->roles()->exists();
    }


    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'dob' => 'date',
    ];

    public function setPhotoAttribute($value)
    {
        \Log::info('User Model - setPhotoAttribute called', [
            'value' => $value,
            'type' => gettype($value),
            'is_false' => $value === false,
            'is_uploaded_file' => $value instanceof \Illuminate\Http\UploadedFile,
        ]);

        // If Nova is trying to set false, don't save it
        if ($value === false) {
            \Log::warning('User Model - Preventing false from being saved to photo');
            return;
        }

        $this->attributes['photo'] = $value;
    }

    public function getPhotoUrlAttribute()
    {
        // Return null if no photo
        if (!$this->photo) {
            return null;
        }

        return config('app.url').'/'.$this->photo;
    }

    // Legacy relationships from previous project - commented out
    // public function groups()
    // {
    //     return $this->belongsToMany(Group::class);
    // }

    // public function reservations()
    // {
    //     return $this->hasMany(Reservation::class);
    // }

    // public function subscriptions()
    // {
    //     return $this->hasMany(Subscription::class);
    // }

    // public function checkins()
    // {
    //     return $this->hasMany(Checkin::class);
    // }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function createorUpdate($deviceData)
    {
        // Check if the device with the provided device_id already exists
        $device = $this->devices()->where('device_id', $deviceData['device_id'])->first();

        if ($device) {
            // If the device exists, update its attributes
            $device->update($deviceData);
            return $device;
        } else {
            // If the device doesn't exist, create a new one
            return $this->devices()->create($deviceData);
        }
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles()->where('code', $roleCode)->exists();
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permissionCode) {
            $query->where('code', $permissionCode);
        })->exists();
    }

    public function basesCommerciales(): BelongsToMany
    {
        return $this->belongsToMany(BaseCommerciale::class, 'base_user', 'user_id', 'base_commerciale_id');
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'zone_user', 'user_id', 'zone_id');
    }

    /**
     * Assigned clients (many-to-many)
     */
    public function assignedClients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_user')
            ->withPivot(['assigned_by', 'assigned_at', 'role', 'active'])
            ->withTimestamps()
            ->wherePivot('active', true)
            ->using(\App\Models\ClientUser::class);
    }

    /**
     * All client assignments including inactive (for audit trail)
     */
    public function allClientAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_user')
            ->withPivot(['assigned_by', 'assigned_at', 'role', 'active'])
            ->withTimestamps()
            ->using(\App\Models\ClientUser::class);
    }
}
