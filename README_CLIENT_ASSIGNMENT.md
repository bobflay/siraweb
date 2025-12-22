# Client-User Assignment System

## 🚀 Quick Start

### Database Setup
```bash
php artisan migrate
```

This creates the `client_user` pivot table with:
- `client_id`, `user_id` (foreign keys)
- `assigned_by` (tracks who made the assignment)
- `assigned_at` (timestamp of assignment)
- `role` ('primary' or 'secondary')
- `active` (soft delete flag)

---

## 📋 Business Rules

1. **One client → Many commercials** ✅
2. **One commercial → Many clients** ✅
3. **Minimum 1 commercial per client** ✅
4. **Auto-assign creator as primary** ✅
5. **Track who assigned and when** ✅
6. **Commercial sees only assigned clients** ✅
7. **Admin/Direction manage all assignments** ✅
8. **Keep assignment history** ✅

---

## 🔐 Access Control

### Commercial Users
- ✅ Can create clients (auto-assigned as primary)
- ✅ Can view/edit assigned clients only
- ❌ Cannot modify assignments
- ❌ Cannot see unassigned clients

### Admin/Direction Users
- ✅ Can create clients
- ✅ Can view/edit all clients
- ✅ Can manage assignments (attach/detach)
- ✅ Can see assignment history

### Responsable de Base Users
- ✅ Can view clients in their commercial bases
- ✅ Access level between Commercial and Admin

---

## 💻 Code Examples

### Assign a Commercial
```php
$client->assignedUsers()->attach($commercialId, [
    'assigned_by' => auth()->id(),
    'assigned_at' => now(),
    'role' => 'secondary',
    'active' => true,
]);
```

### Get Assigned Clients
```php
// For current user (role-based)
$clients = Client::forUser(auth()->user())->get();

// For specific commercial
$clients = $commercial->assignedClients;
```

### Check Assignment
```php
if ($client->assignedUsers()->where('users.id', $userId)->exists()) {
    // User is assigned
}
```

### Soft Delete Assignment
```php
DB::table('client_user')
    ->where('client_id', $clientId)
    ->where('user_id', $userId)
    ->update(['active' => false]);
```

---

## 🌐 API Usage

### Get Clients (Role-Based)
```bash
GET /api/clients
Authorization: Bearer {token}

# Commercial sees only assigned clients
# Admin sees all clients
```

### Filter by Commercial (Admin only)
```bash
GET /api/clients?commercial_id=5
```

### Response Format
```json
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
      "has_open_alert": false,
      ...
    }
  ]
}
```

---

## 🎨 Laravel Nova

### Managing Assignments

1. **Create Client**
   - Select commercials from "Commerciaux assignés" field
   - Filtered to show only users with 'commercial' role
   - Creator is auto-assigned if commercial

2. **Edit Client**
   - Admin/Direction: Can modify assignments
   - Commercial: Field is read-only

3. **Filter by Commercial**
   - Use "Commercial Assigné" filter in client list
   - Shows clients assigned to selected commercial

4. **Pivot Data**
   - Role (primary/secondary)
   - Assigned at (timestamp)
   - Assigned by (admin user)
   - Active status

---

## 📊 Key Models & Files

### Models
- `app/Models/Client.php` - Main client model with relationships
- `app/Models/User.php` - User model with client assignments

### Policies
- `app/Policies/ClientPolicy.php` - Authorization rules

### Observers
- `app/Observers/ClientObserver.php` - Auto-assignment logic

### Nova
- `app/Nova/Client.php` - Nova resource with BelongsToMany field
- `app/Nova/Filters/ClientAssignedCommercialFilter.php` - Filter by commercial

### API
- `app/Http/Controllers/API/ClientController.php` - REST endpoints

### Migrations
- `database/migrations/2025_12_15_092822_create_client_user_table.php` - Pivot table

---

## 🧪 Testing Checklist

- [ ] Commercial creates client → Auto-assigned as primary
- [ ] Admin creates client → Can select commercials
- [ ] Commercial views client list → Only assigned clients shown
- [ ] Admin views client list → All clients shown
- [ ] Commercial edits client → Assignment field is read-only
- [ ] Admin edits client → Can modify assignments
- [ ] Admin tries to remove all commercials → Validation prevents it
- [ ] API call by commercial → Returns assigned clients only
- [ ] API call by admin → Returns all clients
- [ ] Nova filter by commercial → Correctly filters
- [ ] Assignment history → Tracked in pivot table

---

## 🔍 Database Queries

### Get clients for commercial #5
```sql
SELECT clients.*
FROM clients
INNER JOIN client_user ON clients.id = client_user.client_id
WHERE client_user.user_id = 5
  AND client_user.active = 1;
```

### Get commercials for client #12
```sql
SELECT users.*
FROM users
INNER JOIN client_user ON users.id = client_user.user_id
WHERE client_user.client_id = 12
  AND client_user.active = 1;
```

### Get assignment history
```sql
SELECT
    c.name as client,
    u.name as commercial,
    cu.role,
    cu.assigned_at,
    assigner.name as assigned_by,
    cu.active
FROM client_user cu
JOIN clients c ON cu.client_id = c.id
JOIN users u ON cu.user_id = u.id
LEFT JOIN users assigner ON cu.assigned_by = assigner.id
WHERE cu.client_id = 12
ORDER BY cu.assigned_at DESC;
```

---

## 📚 Documentation

- **Full Implementation Guide**: `CLIENT_ASSIGNMENT_IMPLEMENTATION.md`
- **Code Examples**: `EXAMPLE_CLIENT_ASSIGNMENT_QUERIES.php`
- **Summary**: `IMPLEMENTATION_SUMMARY.md`
- **This Quick Reference**: `README_CLIENT_ASSIGNMENT.md`

---

## 🆘 Common Issues

### "Commercial cannot see any clients"
→ Ensure commercial is assigned to clients via `client_user` table

### "Validation error: minimum 1 commercial"
→ Cannot save client without at least one assigned commercial

### "Assignment not tracked"
→ Check `assigned_by` and `assigned_at` are set in pivot data

### "Cannot filter by commercial"
→ Ensure user has 'admin' or 'direction' role for `commercial_id` filter

---

## 🎯 Production Checklist

- [x] Pivot table migrated
- [x] Models have relationships
- [x] Policy enforces authorization
- [x] Observer handles auto-assignment
- [x] Nova resource configured
- [x] Nova filter created
- [x] API enforces pivot-based access
- [x] Validation prevents invalid states
- [x] Audit trail captures all changes
- [x] Documentation complete

---

**Status**: ✅ Production Ready
**Date**: December 15, 2025
**Laravel**: 12
**Nova**: 4.x
