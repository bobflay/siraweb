<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view the client list (filtered by role)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        // Super Admin, Admin or Direction: full access
        if ($user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction')) {
            return true;
        }

        // Responsable de base: can view clients in their bases
        if ($user->hasRole('ROLE_BASE_MANAGER') || $user->hasRole('responsable_base')) {
            $baseIds = $user->basesCommerciales()->pluck('bases_commerciales.id');
            return $baseIds->contains($client->base_commerciale_id);
        }

        // Commercial: can view only assigned clients
        if ($user->hasRole('ROLE_AGENT') || $user->hasRole('commercial')) {
            return $client->assignedUsers()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Commercial, Admin, Direction, Super Admin can create clients
        return $user->hasRole('ROLE_AGENT')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('commercial')
            || $user->hasRole('admin')
            || $user->hasRole('direction')
            || $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        // Super Admin, Admin or Direction: full access
        if ($user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction')) {
            return true;
        }

        // Commercial: can only update assigned clients
        if ($user->hasRole('ROLE_AGENT') || $user->hasRole('commercial')) {
            return $client->assignedUsers()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        // Only Super Admin, Admin or Direction can delete
        return $user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return $user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can attach/detach users (assign commercials).
     */
    public function attachAnyUser(User $user, Client $client): bool
    {
        // Only Super Admin, Admin or Direction can manage assignments
        return $user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction');
    }

    /**
     * Determine whether the user can detach users.
     */
    public function detachUser(User $user, Client $client): bool
    {
        // Only Super Admin, Admin or Direction can manage assignments
        return $user->hasRole('ROLE_SUPER_ADMIN')
            || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('direction');
    }
}
