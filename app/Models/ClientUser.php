<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClientUser extends Pivot
{
    protected $table = 'client_user';

    protected $casts = [
        'assigned_at' => 'datetime',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = true;
}
