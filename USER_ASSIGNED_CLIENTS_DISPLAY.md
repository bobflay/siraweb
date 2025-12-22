# User Nova Resource - Display Assigned Clients

## Feature Added

Added a **"Clients Assignés"** field to the User Nova resource to display the list of clients assigned to commercial users.

## Implementation

### File Modified
- **app/Nova/User.php**

### Field Configuration

```php
BelongsToMany::make('Clients Assignés', 'assignedClients', Client::class)
    ->fields(function () {
        return [
            Text::make('Role', 'role')->readonly(),
            Text::make('Assigned At', 'assigned_at')
                ->displayUsing(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i') : null;
                })
                ->readonly(),
            Badge::make('Active', 'active')
                ->map([
                    true => 'success',
                    false => 'danger',
                ])
                ->labels([
                    true => 'Active',
                    false => 'Inactive',
                ]),
        ];
    })
    ->searchable()
    ->singularLabel('Client')
    ->help('List of clients assigned to this commercial user')
    ->canSee(function ($request) {
        // Only show for users with commercial role
        return $this->resource->hasRole('ROLE_AGENT') || $this->resource->hasRole('commercial');
    })
```

## Features

### ✅ Conditional Display
- Only visible for users with **ROLE_AGENT** or **commercial** role
- Hidden for admin users (as they don't have client assignments)

### ✅ Pivot Data Display
Shows three key pieces of information:
1. **Role** - Whether the user is the primary or secondary commercial for the client
2. **Assigned At** - When the assignment was made (formatted as YYYY-MM-DD HH:MM)
3. **Active** - Whether the assignment is currently active (Green badge = Active, Red badge = Inactive)

### ✅ Searchable
- Can search for clients by name when attaching
- Easy to find and manage assignments

### ✅ Read-Only Pivot Fields
- Pivot data is displayed as read-only
- Prevents accidental modification of audit trail data

## How It Appears in Nova

### For Commercial Users (ROLE_AGENT)

When viewing a commercial user's detail page, you'll see:

```
┌─────────────────────────────────────────┐
│ User Details                            │
├─────────────────────────────────────────┤
│ Name: N'Guessan Marie                   │
│ Email: marie@example.com                │
│ Role: Commercial Terrain                │
│                                         │
│ Clients Assignés                        │
│ ┌─────────────────────────────────────┐ │
│ │ Boutique Adjamé                     │ │
│ │ Role: primary                       │ │
│ │ Assigned At: 2025-12-17 22:50       │ │
│ │ Active: ✓ Active                    │ │
│ ├─────────────────────────────────────┤ │
│ │ Supermarché Yopougon                │ │
│ │ Role: secondary                     │ │
│ │ Assigned At: 2025-12-15 10:30       │ │
│ │ Active: ✓ Active                    │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

### For Admin Users

The "Clients Assignés" field will **not be visible** since admin users don't have role-based client assignments.

## Related Features

This complements the existing client assignment system:

1. **Client Nova Resource** - Shows "Commerciaux assignés" for each client
2. **User Nova Resource** - Shows "Clients Assignés" for each commercial (NEW)
3. **API Endpoint** - `/api/clients` filters by assignments
4. **Nova Filter** - Filter clients by assigned commercial

## Benefits

### For Admins/Direction
- ✅ Quick view of which clients are assigned to each commercial
- ✅ Easy verification of workload distribution
- ✅ Audit trail visibility (who assigned, when)

### For Commercial Users
- ✅ See their assigned clients in one place
- ✅ Verify their assignment status
- ✅ Understand their role (primary vs secondary)

## Usage Example

### Viewing Assignments
1. Go to **Users** in Nova
2. Click on a commercial user (e.g., "N'Guessan Marie")
3. Scroll down to **"Clients Assignés"** section
4. See all assigned clients with their details

### Managing Assignments
Assignments should be managed from the **Client** side:
1. Go to **Clients** in Nova
2. Edit a client
3. Use **"Commerciaux assignés"** to attach/detach users

## Testing

```bash
php artisan tinker

# Find a commercial user
$user = App\Models\User::whereHas('roles', fn($q) =>
    $q->where('code', 'ROLE_AGENT')
)->first();

# Check assigned clients
$user->assignedClients; // Collection of clients
$user->assignedClients()->count(); // Number of assignments

# View pivot data
foreach ($user->assignedClients as $client) {
    echo $client->name;
    echo " - Role: " . $client->pivot->role;
    echo " - Active: " . ($client->pivot->active ? 'Yes' : 'No');
}
```

## Database Query

When viewing assigned clients in Nova:

```sql
SELECT clients.*,
       client_user.role,
       client_user.assigned_at,
       client_user.active
FROM clients
INNER JOIN client_user ON clients.id = client_user.client_id
WHERE client_user.user_id = ?
  AND client_user.active = 1
ORDER BY client_user.assigned_at DESC;
```

---

**Date**: December 17, 2025
**Feature**: Display assigned clients in User Nova resource
**Status**: ✅ Implemented and tested
**Files Modified**: app/Nova/User.php
