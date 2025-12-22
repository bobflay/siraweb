# Role Naming Convention Fix

## Issue

The application was showing "No Client matched the given criteria" in Nova because the code was checking for role codes without the `ROLE_` prefix (e.g., `super_admin`), but the database stored roles with the prefix (e.g., `ROLE_SUPER_ADMIN`).

## Root Cause

**Database roles:**
- `ROLE_SUPER_ADMIN`
- `ROLE_COMMERCIAL_ADMIN`
- `ROLE_BASE_MANAGER`
- `ROLE_AGENT`
- `ROLE_FINANCE`

**Code was checking:**
- `super_admin`
- `admin`
- `direction`
- `responsable_base`
- `commercial`

## Solution

Updated all authorization checks to support **both naming conventions** for backward compatibility:

### Files Updated

1. **app/Models/Client.php** - `scopeForUser()`
2. **app/Policies/ClientPolicy.php** - All policy methods
3. **app/Nova/Client.php** - `indexQuery()` and `authorizedToCreate()`
4. **app/Http/Controllers/API/ClientController.php** - API authorization

### Role Mapping

| Database Role | Alternative Names |
|--------------|-------------------|
| `ROLE_SUPER_ADMIN` | `super_admin`, `admin` |
| `ROLE_COMMERCIAL_ADMIN` | `admin`, `direction` |
| `ROLE_BASE_MANAGER` | `responsable_base` |
| `ROLE_AGENT` | `commercial` |

## Example Code Pattern

**Before:**
```php
if ($user->hasRole('super_admin')) {
    return $query;
}
```

**After:**
```php
if ($user->hasRole('ROLE_SUPER_ADMIN')
    || $user->hasRole('ROLE_COMMERCIAL_ADMIN')
    || $user->hasRole('super_admin')
    || $user->hasRole('admin')
    || $user->hasRole('direction')) {
    return $query;
}
```

## Verification

```bash
php artisan tinker

$user = App\Models\User::first();
$client = App\Models\Client::first();

# Check roles
$user->roles->pluck('code'); // ["ROLE_SUPER_ADMIN"]
$user->hasRole('ROLE_SUPER_ADMIN'); // true

# Check access
App\Models\Client::forUser($user)->count(); // All clients (50)
$user->can('view', $client); // true
$user->can('update', $client); // true
```

## Status

✅ **Fixed** - Super Admin and all other roles now have correct access to clients in:
- Laravel Nova
- REST API
- Eloquent queries
- Authorization policies

---

**Date**: December 2025
**Impact**: All role-based authorization
**Testing**: Verified in Tinker and Nova
