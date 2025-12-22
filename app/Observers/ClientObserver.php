<?php

namespace App\Observers;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     * Auto-assign the creating user as primary commercial.
     */
    public function created(Client $client): void
    {
        $user = Auth::user();

        // If created by a commercial, auto-assign them as primary
        if ($user && ($user->hasRole('ROLE_AGENT') || $user->hasRole('commercial'))) {
            $client->assignedUsers()->attach($user->id, [
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'role' => 'primary',
                'active' => true,
            ]);
        }
    }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "force deleted" event.
     */
    public function forceDeleted(Client $client): void
    {
        //
    }
}
