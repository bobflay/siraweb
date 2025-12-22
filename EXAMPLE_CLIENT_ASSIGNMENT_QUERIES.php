<?php

/**
 * Example Client-User Assignment Queries
 *
 * This file demonstrates how to work with the client-user pivot table
 * in various scenarios throughout the SIRA application.
 */

namespace App\Examples;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClientAssignmentExamples
{
    /**
     * EXAMPLE 1: Assign a commercial to a client
     */
    public function assignCommercialToClient()
    {
        $client = Client::find(1);
        $commercial = User::find(5);
        $assignedBy = auth()->user();

        // Method 1: Using attach with pivot data
        $client->assignedUsers()->attach($commercial->id, [
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'role' => 'secondary',
            'active' => true,
        ]);

        // Method 2: Using sync to replace all assignments
        $client->assignedUsers()->sync([
            5 => ['assigned_by' => $assignedBy->id, 'assigned_at' => now(), 'role' => 'primary'],
            7 => ['assigned_by' => $assignedBy->id, 'assigned_at' => now(), 'role' => 'secondary'],
        ]);

        // Method 3: Using syncWithoutDetaching to add without removing existing
        $client->assignedUsers()->syncWithoutDetaching([
            9 => ['assigned_by' => $assignedBy->id, 'assigned_at' => now(), 'role' => 'secondary'],
        ]);
    }

    /**
     * EXAMPLE 2: Get all clients assigned to a commercial
     */
    public function getClientsForCommercial()
    {
        $commercial = User::find(5);

        // Method 1: Via relationship
        $clients = $commercial->assignedClients;

        // Method 2: Via query scope
        $clients = Client::whereHas('assignedUsers', function ($query) use ($commercial) {
            $query->where('users.id', $commercial->id);
        })->get();

        // Method 3: With pivot data
        $clients = $commercial->assignedClients()
            ->withPivot(['assigned_by', 'assigned_at', 'role'])
            ->get();

        foreach ($clients as $client) {
            echo "Client: {$client->name}\n";
            echo "Role: {$client->pivot->role}\n";
            echo "Assigned at: {$client->pivot->assigned_at}\n";
        }

        return $clients;
    }

    /**
     * EXAMPLE 3: Check if a user is assigned to a client
     */
    public function isUserAssignedToClient(User $user, Client $client): bool
    {
        return $client->assignedUsers()
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * EXAMPLE 4: Get clients with role-based access (same as API)
     */
    public function getClientsForCurrentUser()
    {
        $user = auth()->user();

        // This uses the scopeForUser from Client model
        $clients = Client::forUser($user)
            ->with(['assignedUsers', 'zone', 'baseCommerciale'])
            ->paginate(20);

        return $clients;
    }

    /**
     * EXAMPLE 5: Remove assignment (soft delete - mark inactive)
     */
    public function deactivateAssignment()
    {
        $client = Client::find(1);
        $commercial = User::find(5);

        // Soft delete: Mark as inactive
        DB::table('client_user')
            ->where('client_id', $client->id)
            ->where('user_id', $commercial->id)
            ->update(['active' => false]);

        // Hard delete: Completely remove
        // $client->assignedUsers()->detach($commercial->id);
    }

    /**
     * EXAMPLE 6: Get all assignments for a client (including history)
     */
    public function getClientAssignmentHistory(Client $client)
    {
        // Get all assignments including inactive
        $assignments = $client->allUserAssignments()
            ->withPivot(['assigned_by', 'assigned_at', 'role', 'active'])
            ->with('roles')
            ->get();

        $history = [];
        foreach ($assignments as $user) {
            $history[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role' => $user->pivot->role,
                'assigned_by' => $user->pivot->assigned_by,
                'assigned_at' => $user->pivot->assigned_at,
                'active' => $user->pivot->active,
            ];
        }

        return $history;
    }

    /**
     * EXAMPLE 7: Update assignment role
     */
    public function updateAssignmentRole()
    {
        $client = Client::find(1);
        $commercial = User::find(5);

        // Update pivot data
        $client->assignedUsers()->updateExistingPivot($commercial->id, [
            'role' => 'primary',
        ]);
    }

    /**
     * EXAMPLE 8: Get primary commercial for a client
     */
    public function getPrimaryCommercial(Client $client): ?User
    {
        return $client->assignedUsers()
            ->wherePivot('role', 'primary')
            ->first();
    }

    /**
     * EXAMPLE 9: Replace primary commercial
     */
    public function replacePrimaryCommercial(Client $client, User $newPrimary)
    {
        $assignedBy = auth()->user();

        // First, demote current primary to secondary
        DB::table('client_user')
            ->where('client_id', $client->id)
            ->where('role', 'primary')
            ->update(['role' => 'secondary']);

        // Then, check if new user is already assigned
        $exists = $client->assignedUsers()
            ->where('users.id', $newPrimary->id)
            ->exists();

        if ($exists) {
            // Update existing assignment to primary
            $client->assignedUsers()->updateExistingPivot($newPrimary->id, [
                'role' => 'primary',
            ]);
        } else {
            // Create new assignment as primary
            $client->assignedUsers()->attach($newPrimary->id, [
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
                'role' => 'primary',
                'active' => true,
            ]);
        }
    }

    /**
     * EXAMPLE 10: Get clients assigned by a specific manager
     */
    public function getClientsAssignedByManager(User $manager)
    {
        return Client::whereHas('assignedUsers', function ($query) use ($manager) {
            $query->where('client_user.assigned_by', $manager->id);
        })->get();
    }

    /**
     * EXAMPLE 11: API Controller Example (already implemented)
     */
    public function apiGetClients(Request $request)
    {
        $user = $request->user();

        $clients = Client::query()
            ->forUser($user) // Role-based access
            ->search($request->search)
            ->filterByType($request->type)
            ->filterByCity($request->city)
            ->filterByZone($request->zone_id)
            ->filterByCommercial($request->commercial_id)
            ->filterByAlert($request->has_alert)
            ->updatedAfter($request->updated_after)
            ->paginate($request->limit ?? 20);

        return ClientResource::collection($clients);
    }

    /**
     * EXAMPLE 12: Bulk assign commercials to multiple clients
     */
    public function bulkAssignCommercials(array $clientIds, array $commercialIds)
    {
        $assignedBy = auth()->user();
        $timestamp = now();

        foreach ($clientIds as $clientId) {
            $client = Client::find($clientId);

            $assignments = [];
            foreach ($commercialIds as $commercialId) {
                $assignments[$commercialId] = [
                    'assigned_by' => $assignedBy->id,
                    'assigned_at' => $timestamp,
                    'role' => 'secondary',
                    'active' => true,
                ];
            }

            // Use syncWithoutDetaching to add without removing existing
            $client->assignedUsers()->syncWithoutDetaching($assignments);
        }
    }

    /**
     * EXAMPLE 13: Get assignment statistics
     */
    public function getAssignmentStatistics()
    {
        return [
            'total_clients' => Client::count(),
            'total_assignments' => DB::table('client_user')->where('active', true)->count(),
            'clients_without_assignment' => Client::doesntHave('assignedUsers')->count(),
            'commercials_with_clients' => User::has('assignedClients')->count(),
            'avg_clients_per_commercial' => DB::table('client_user')
                ->where('active', true)
                ->groupBy('user_id')
                ->selectRaw('COUNT(*) as count')
                ->avg('count'),
        ];
    }

    /**
     * EXAMPLE 14: Validate assignment before saving
     */
    public function validateClientHasMinimumOneCommercial(Client $client): bool
    {
        $activeAssignments = $client->assignedUsers()->count();

        if ($activeAssignments < 1) {
            throw new \Exception('Client must have at least one assigned commercial.');
        }

        return true;
    }

    /**
     * EXAMPLE 15: Get clients by zone and assigned commercial
     */
    public function getClientsInZoneForCommercial(int $zoneId, int $commercialId)
    {
        return Client::where('zone_id', $zoneId)
            ->whereHas('assignedUsers', function ($query) use ($commercialId) {
                $query->where('users.id', $commercialId)
                      ->where('client_user.active', true);
            })
            ->with(['zone', 'assignedUsers'])
            ->get();
    }
}
