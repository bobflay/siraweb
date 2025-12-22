# Client-User Assignment Implementation

## Overview
This document describes the many-to-many relationship implementation between Clients and Users (Commercials) with full audit trail, Laravel Nova integration, and API enforcement.

---

## Database Schema

### Pivot Table: `client_user`

```sql
- id (primary key)
- client_id (foreign key → clients)
- user_id (foreign key → users)
- assigned_by (foreign key → users, nullable)
- assigned_at (timestamp)
- role (enum: 'primary' | 'secondary')
- active (boolean, default: true)
- created_at
- updated_at

Unique constraint: [client_id, user_id, active]
Indexes: [client_id, active], [user_id, active]
```

**Migration**: `database/migrations/2025_12_15_092822_create_client_user_table.php`

---

## Eloquent Models

### Client Model (`app/Models/Client.php`)

**Relationships:**
```php
// Active assignments only
public function assignedUsers(): BelongsToMany

// All assignments including inactive (for audit)
public function allUserAssignments(): BelongsToMany
```

**Query Scopes:**
```php
// Role-based access control
public function scopeForUser(Builder $query, User $user): Builder
```

- **Admin/Direction**: See all clients
- **Responsable de base**: See clients in their commercial bases
- **Commercial**: See only assigned clients (via pivot)

---

### User Model (`app/Models/User.php`)

**Relationships:**
```php
// Active client assignments
public function assignedClients(): BelongsToMany

// All assignments including inactive (for audit)
public function allClientAssignments(): BelongsToMany
```

---

## Authorization (ClientPolicy)

**File**: `app/Policies/ClientPolicy.php`

### Policy Methods:
- `viewAny()` - All authenticated users (filtered by role)
- `view()` - Admin/Direction: all | Responsable: their bases | Commercial: assigned only
- `create()` - Commercial, Admin, Direction
- `update()` - Admin/Direction: all | Commercial: assigned only
- `delete()` - Admin/Direction only
- `attachAnyUser()` - Admin/Direction only
- `detachUser()` - Admin/Direction only

**Registration**: `app/Providers/AppServiceProvider.php`

---

## Audit Trail (ClientObserver)

**File**: `app/Observers/ClientObserver.php`

### Auto-Assignment on Client Creation:
When a commercial creates a client:
```php
public function created(Client $client): void
{
    // Auto-assign creator as primary commercial
    $client->assignedUsers()->attach($user->id, [
        'assigned_by' => $user->id,
        'assigned_at' => now(),
        'role' => 'primary',
        'active' => true,
    ]);
}
```

**Registration**: `app/Providers/AppServiceProvider.php`

---

## Laravel Nova Integration

### Client Resource (`app/Nova/Client.php`)

#### BelongsToMany Field:
```php
BelongsToMany::make('Commerciaux assignés', 'assignedUsers', User::class)
    ->fields(function () {
        return [
            Select::make('Role', 'role')
                ->options(['primary' => 'Primary', 'secondary' => 'Secondary'])
                ->default('secondary')
                ->rules('required'),
            DateTime::make('Assigned At', 'assigned_at'),
            BelongsTo::make('Assigned By', 'assigned_by', User::class),
            Boolean::make('Active', 'active')->default(true),
        ];
    })
    ->searchable()
    ->rules('required', 'min:1') // At least one commercial required
    ->readonly(fn($request) => $request->user()->hasRole('commercial'))
```

#### Features:
- ✅ Searchable commercial selection
- ✅ Filtered to show only users with 'commercial' role
- ✅ Read-only for commercial users
- ✅ Editable by Admin/Direction
- ✅ Tracks assigned_by and assigned_at
- ✅ Supports primary/secondary roles
- ✅ Validation: minimum 1 commercial

#### Index Query (Role-Based):
```php
public static function indexQuery(NovaRequest $request, $query)
{
    // Admin/Direction: all clients
    // Responsable de base: their base's clients
    // Commercial: only assigned clients (pivot-based)
}
```

#### Relatable Query Filter:
```php
public static function relatableAssignedUsers(NovaRequest $request, $query)
{
    // Only show users with 'commercial' role
    return $query->whereHas('roles', fn($q) => $q->where('code', 'commercial'));
}
```

#### Audit Trail Hooks:
```php
public static function afterCreate(NovaRequest $request, $model)
{
    // Set assigned_by for new attachments
}

public static function afterUpdate(NovaRequest $request, $model)
{
    // Set assigned_by for new attachments
}
```

---

### Nova Filter (`app/Nova/Filters/ClientAssignedCommercialFilter.php`)

**Purpose**: Filter clients by assigned commercial

**Usage**: Appears in Nova client list filters
```php
public function apply(NovaRequest $request, $query, $value)
{
    return $query->whereHas('assignedUsers', function ($q) use ($value) {
        $q->where('users.id', $value)
          ->where('client_user.active', true);
    });
}
```

---

## API Enforcement

### Endpoint: `GET /api/clients`

**File**: `app/Http/Controllers/API/ClientController.php`

**Implementation**:
```php
public function index(Request $request)
{
    $user = $request->user();

    $query = Client::query()
        ->forUser($user) // ← Enforces pivot-based access
        // ... filters ...
        ->paginate($limit);

    return ClientResource::collection($clients);
}
```

**Access Rules**:
- **Admin/Direction**: All clients
- **Responsable de base**: Clients in their bases
- **Commercial**: Only assigned clients (via `client_user` pivot, `active = true`)

---

## Example Usage

### Assign a Commercial to a Client

**Via Eloquent:**
```php
$client = Client::find(1);
$commercial = User::find(5);

$client->assignedUsers()->attach($commercial->id, [
    'assigned_by' => auth()->id(),
    'assigned_at' => now(),
    'role' => 'secondary',
    'active' => true,
]);
```

**Via Nova**: Use the "Commerciaux assignés" field when creating/editing a client

---

### Remove Assignment (Soft Delete)

```php
// Mark as inactive (keep history)
DB::table('client_user')
    ->where('client_id', 1)
    ->where('user_id', 5)
    ->update(['active' => false]);

// Hard delete
$client->assignedUsers()->detach($commercial->id);
```

---

### Query Clients for a Commercial

```php
// Via User model
$commercialClients = auth()->user()->assignedClients;

// Via Client model
$clients = Client::whereHas('assignedUsers', function ($q) {
    $q->where('users.id', auth()->id())
      ->where('client_user.active', true);
})->get();

// Via scope
$clients = Client::forUser(auth()->user())->get();
```

---

### API Request Example

```bash
# Commercial user (sees only assigned clients)
GET /api/clients
Authorization: Bearer {token}

Response:
{
  "success": true,
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 15
  },
  "data": [
    {
      "id": 12,
      "name": "Boutique Chez Awa",
      "commercial_id": 8,
      ...
    }
  ]
}
```

---

## Business Rules Compliance

| Requirement | Implementation |
|-------------|----------------|
| Client can be assigned to many users | ✅ Many-to-many via `client_user` |
| User can have many clients | ✅ Many-to-many via `client_user` |
| Only assigned users see client (API) | ✅ `scopeForUser()` + API controller |
| Admin/Direction manage assignments | ✅ ClientPolicy + Nova readonly logic |
| Commercial cannot modify assignments | ✅ Nova field `readonly()` callback |
| Minimum 1 assigned user | ✅ Nova field `rules('required', 'min:1')` |
| Track assigned_by and assigned_at | ✅ Pivot fields + Observer + Nova hooks |
| Assignment history (no hard delete) | ✅ `active` boolean flag |
| Auto-assign creator | ✅ ClientObserver `created()` |
| Filter by assigned commercial | ✅ Nova filter |

---

## Testing Checklist

- [ ] Commercial creating a client → auto-assigned as primary
- [ ] Admin attaching commercial to client → `assigned_by` tracked
- [ ] Commercial viewing client list → sees only assigned clients
- [ ] Admin viewing client list → sees all clients
- [ ] Commercial trying to edit assignments → blocked (read-only)
- [ ] Admin detaching last commercial → prevented (min:1 validation)
- [ ] API `/api/clients` for commercial → returns only assigned clients
- [ ] Nova filter by commercial → works correctly
- [ ] Soft delete assignment (active=false) → hidden from queries

---

## Files Modified/Created

### Database:
- `database/migrations/2025_12_15_092822_create_client_user_table.php`

### Models:
- `app/Models/Client.php` (relationships + scopes)
- `app/Models/User.php` (relationships)

### Policies:
- `app/Policies/ClientPolicy.php`

### Observers:
- `app/Observers/ClientObserver.php`

### Providers:
- `app/Providers/AppServiceProvider.php` (registration)

### Nova Resources:
- `app/Nova/Client.php` (BelongsToMany field + hooks)
- `app/Nova/Filters/ClientAssignedCommercialFilter.php`

### API:
- `app/Http/Controllers/API/ClientController.php` (already using pivot-based scopes)
- `app/Models/Client.php` (scopeForUser updated)

---

## Notes

1. **Soft Delete Assignments**: Use `active = false` instead of hard deleting pivot records to maintain audit trail
2. **Performance**: Indexes on `[client_id, active]` and `[user_id, active]` ensure fast queries
3. **Validation**: Nova enforces minimum 1 commercial, database ensures unique active assignments
4. **Audit Trail**: All assignments track who (`assigned_by`) and when (`assigned_at`)
5. **Role-Based Access**: Consistently enforced in Nova, API, and Policies

---

Generated: 2025-12-15
Laravel Version: 12
Nova Version: 4.x
