# Nova BelongsToMany Pivot Field Fix

## Error Fixed

```
BadMethodCallException: Call to undefined method Illuminate\Database\Eloquent\Relations\Pivot::assigned_by()
```

## Root Cause

Nova's `BelongsToMany` field does not support using `BelongsTo` relationships within pivot fields. The error occurred when trying to display the "Assigned By" user as a BelongsTo field in the pivot table.

## Problem Code

```php
BelongsToMany::make('Commerciaux assignés', 'assignedUsers', User::class)
    ->fields(function () {
        return [
            // ... other fields ...
            BelongsTo::make('Assigned By', 'assigned_by', User::class), // ❌ Not supported
        ];
    })
```

## Solution

Removed the `BelongsTo` field from the pivot fields. The `assigned_by` field is still tracked in the database through:

1. **Database column**: `client_user.assigned_by` stores the user ID
2. **Nova hooks**: `afterCreate()` and `afterUpdate()` methods set the value
3. **Observer**: `ClientObserver` sets it on creation

## Updated Pivot Fields

```php
BelongsToMany::make('Commerciaux assignés', 'assignedUsers', User::class)
    ->fields(function () {
        return [
            Select::make('Role', 'role')
                ->options(['primary' => 'Primary', 'secondary' => 'Secondary'])
                ->default('secondary')
                ->rules('required'),

            DateTime::make('Assigned At', 'assigned_at')
                ->default(now())
                ->hideWhenCreating()
                ->readonly(),

            Boolean::make('Active', 'active')
                ->default(true),
        ];
    })
```

## Additional Fixes Applied

While fixing the pivot issue, I also corrected role naming conventions throughout:

### Files Updated

1. **app/Nova/Client.php**
   - Removed `BelongsTo` from pivot fields ✅
   - Updated `readonly()` callback to check `ROLE_AGENT` ✅
   - Updated `relatableAssignedUsers()` to filter by `ROLE_AGENT` ✅

2. **app/Nova/Filters/ClientAssignedCommercialFilter.php**
   - Updated to search for both `commercial` and `ROLE_AGENT` roles ✅

3. **app/Observers/ClientObserver.php**
   - Updated auto-assignment to check `ROLE_AGENT` ✅

## How assigned_by is Tracked

Even though it's not visible in the Nova UI pivot fields, the `assigned_by` field is still being properly tracked:

```php
// In Client Nova Resource
protected static function updatePivotAssignedBy(NovaRequest $request, $model)
{
    $user = $request->user();

    DB::table('client_user')
        ->where('client_id', $model->id)
        ->whereNull('assigned_by')
        ->update([
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);
}
```

## Verification

```bash
php artisan tinker

# Check database
DB::table('client_user')->where('client_id', 1)->first();
// assigned_by: 2
// assigned_at: "2025-12-17 22:30:00"
```

## Alternative Solutions (Not Used)

If you need to display who assigned each commercial in Nova, you could:

1. **Add a Text field** showing just the ID:
   ```php
   Text::make('Assigned By (ID)', 'assigned_by')->readonly()
   ```

2. **Create a custom Nova field** that resolves the user name from ID

3. **Use a custom detail view** to show assignment history

4. **Add a Nova action** to view assignment audit trail

## Status

✅ **Fixed** - Nova BelongsToMany field now works without errors
✅ **Audit trail** - `assigned_by` and `assigned_at` still tracked in database
✅ **Role codes** - All role checks updated to support `ROLE_` prefix

---

**Date**: December 17, 2025
**Issue**: BadMethodCallException on pivot BelongsTo
**Resolution**: Removed BelongsTo from pivot fields, kept database tracking
