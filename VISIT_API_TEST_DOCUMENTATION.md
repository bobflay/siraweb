# Visit API Management Tests

## Overview
Comprehensive unit tests for Visit API endpoints (start visit and terminate visit).

## Test File
- **Location**: [tests/Feature/VisitManagementTest.php](tests/Feature/VisitManagementTest.php)
- **Total Tests**: 22
- **Status**: ✅ All Passing

## Test Coverage

### Start Visit Tests (POST /api/visits)

#### Successful Creation Tests
1. **test_visit_can_be_started_with_valid_data**
   - Validates that a visit can be successfully created with all required fields
   - Verifies the response structure and database record
   - Checks that status is set to 'started'

2. **test_visit_can_be_created_with_routing_item**
   - Tests creation with optional routing_item_id parameter

3. **test_visit_can_be_created_with_custom_started_at**
   - Tests creation with custom started_at timestamp

4. **test_visit_inherits_base_and_zone_from_client**
   - Verifies that base_commerciale_id and zone_id are inherited from the client

#### Authentication & Authorization Tests
5. **test_visit_creation_requires_authentication**
   - Ensures unauthenticated requests are rejected with 401 status

#### Validation Tests
6. **test_visit_creation_fails_with_missing_required_fields**
   - Validates that requests without required fields fail with 422 status

7. **test_visit_creation_fails_with_nonexistent_client**
   - Tests rejection of non-existent client_id

8. **test_visit_creation_fails_with_invalid_latitude**
   - Validates latitude range (-90 to 90)

9. **test_visit_creation_fails_with_invalid_longitude**
   - Validates longitude range (-180 to 180)

#### Business Logic Tests
10. **test_visit_creation_fails_when_user_too_far_from_client**
    - Tests GPS proximity check (must be within 300 meters)
    - Uses Haversine formula for distance calculation

11. **test_user_cannot_start_multiple_visits_simultaneously**
    - Ensures user cannot have more than one active (started) visit at a time

### Terminate Visit Tests (POST /api/visits/{id}/terminate)

#### Successful Termination Tests
12. **test_visit_can_be_completed_successfully**
    - Tests successful visit completion
    - Verifies ended_at and duration_seconds are calculated

13. **test_visit_can_be_aborted_successfully**
    - Tests successful visit abort
    - Verifies status changes to 'aborted'

14. **test_visit_duration_is_calculated_correctly**
    - Validates duration calculation in seconds

15. **test_both_completed_and_aborted_statuses_are_accepted**
    - Tests both valid termination statuses

#### Authentication & Authorization Tests
16. **test_visit_termination_requires_authentication**
    - Ensures unauthenticated requests are rejected with 401 status

17. **test_user_cannot_terminate_another_users_visit**
    - Verifies users can only terminate their own visits

#### Validation Tests
18. **test_visit_termination_fails_with_missing_required_fields**
    - Validates required fields: status, latitude, longitude

19. **test_visit_termination_fails_with_invalid_status**
    - Tests rejection of invalid status values (must be 'completed' or 'aborted')

20. **test_visit_termination_fails_with_nonexistent_visit**
    - Tests 404 response for non-existent visit ID

#### Business Logic Tests
21. **test_cannot_terminate_already_terminated_visit**
    - Ensures visits can only be terminated once

22. **test_visit_termination_succeeds_with_warning_when_outside_range**
    - Tests that visits CAN be terminated outside 300m range
    - Verifies warning message is included in response
    - Confirms distance and flag are logged for monitoring

## API Endpoints

### Start Visit
- **URL**: `POST /api/visits`
- **Authentication**: Required (Sanctum)
- **Success Status**: 201 Created
- **Error Status**: 401 (Unauthorized), 403 (Forbidden), 422 (Validation Error)

#### Required Fields
- `client_id` (integer, exists in clients table)
- `latitude` (numeric, -90 to 90)
- `longitude` (numeric, -180 to 180)

#### Optional Fields
- `routing_item_id` (integer, exists in routing_items table)
- `started_at` (datetime, defaults to now)

#### Business Rules
1. User must be within 300 meters of client location (GPS proximity check)
2. User must have access to the client (role-based access control)
3. User cannot have multiple active visits simultaneously
4. Visit automatically inherits base_commerciale_id and zone_id from client

### Terminate Visit
- **URL**: `POST /api/visits/{id}/terminate`
- **Authentication**: Required (Sanctum)
- **Success Status**: 200 OK
- **Error Status**: 401 (Unauthorized), 403 (Forbidden), 404 (Not Found), 422 (Validation Error)

#### Required Fields
- `status` (enum: 'completed' or 'aborted')
- `latitude` (numeric, -90 to 90)
- `longitude` (numeric, -180 to 180)

#### Business Rules
1. User must be the owner of the visit
2. Visit must be in 'started' status (cannot terminate already terminated visits)
3. GPS Distance Handling:
   - **Within 300 meters**: Visit terminates normally without warning
   - **Beyond 300 meters**: Visit still terminates successfully BUT:
     - Returns a warning message in the response
     - Logs the event with full details (distance, coordinates, user, client)
     - Sets `terminated_outside_range` flag to `true`
     - Records the actual `termination_distance` in meters
4. System automatically calculates:
   - `ended_at` (set to current time)
   - `duration_seconds` (difference between started_at and ended_at)
   - `termination_distance` (actual distance from client in meters)
   - `terminated_outside_range` (boolean flag)

## Response Structure

### Start Visit Response
```json
{
  "status": true,
  "message": "Visit created successfully",
  "data": {
    "id": 1,
    "client_id": 1,
    "user_id": 1,
    "base_commerciale_id": 1,
    "zone_id": 1,
    "routing_item_id": null,
    "started_at": "2025-12-22T12:00:00Z",
    "ended_at": null,
    "duration_seconds": null,
    "status": "started",
    "latitude": 33.589886,
    "longitude": -7.603869,
    "client": {
      "id": 1,
      "name": "Test Client",
      "type": "Boutique",
      "city": "Casablanca"
    },
    "user": {
      "id": 1,
      "name": "John Doe"
    },
    "created_at": "2025-12-22T12:00:00Z",
    "updated_at": "2025-12-22T12:00:00Z"
  }
}
```

### Terminate Visit Response (Within Range)
```json
{
  "status": true,
  "message": "Visit terminated successfully",
  "data": {
    "id": 1,
    "client_id": 1,
    "user_id": 1,
    "base_commerciale_id": 1,
    "zone_id": 1,
    "routing_item_id": null,
    "started_at": "2025-12-22T12:00:00Z",
    "ended_at": "2025-12-22T14:00:00Z",
    "duration_seconds": 7200,
    "status": "completed",
    "latitude": 33.589886,
    "longitude": -7.603869,
    "termination_distance": 5.23,
    "terminated_outside_range": false,
    "client": {
      "id": 1,
      "name": "Test Client",
      "type": "Boutique",
      "city": "Casablanca"
    },
    "user": {
      "id": 1,
      "name": "John Doe"
    },
    "created_at": "2025-12-22T12:00:00Z",
    "updated_at": "2025-12-22T14:00:00Z"
  }
}
```

### Terminate Visit Response (Outside Range - With Warning)
```json
{
  "status": true,
  "message": "Visit terminated successfully",
  "warning": "Warning: Visit was terminated 450.25 meters away from the client location. The allowed range is 300 meters.",
  "data": {
    "id": 1,
    "client_id": 1,
    "user_id": 1,
    "base_commerciale_id": 1,
    "zone_id": 1,
    "routing_item_id": null,
    "started_at": "2025-12-22T12:00:00Z",
    "ended_at": "2025-12-22T14:00:00Z",
    "duration_seconds": 7200,
    "status": "completed",
    "latitude": 33.593000,
    "longitude": -7.608000,
    "termination_distance": 450.25,
    "terminated_outside_range": true,
    "client": {
      "id": 1,
      "name": "Test Client",
      "type": "Boutique",
      "city": "Casablanca"
    },
    "user": {
      "id": 1,
      "name": "John Doe"
    },
    "created_at": "2025-12-22T12:00:00Z",
    "updated_at": "2025-12-22T14:00:00Z"
  }
}
```

## Factory Files Created
1. **[database/factories/VisitFactory.php](database/factories/VisitFactory.php)** - Factory for Visit model
   - Includes states: `completed()` and `aborted()`
2. **[database/factories/RoleFactory.php](database/factories/RoleFactory.php)** - Factory for Role model
   - Includes states: `agent()` and `superAdmin()`

## Model Updates
Added `HasFactory` trait to:
- [app/Models/Visit.php](app/Models/Visit.php)
- [app/Models/Role.php](app/Models/Role.php)

## Test Configuration
- **Database**: MySQL (testing database)
- **Configuration**: [phpunit.xml](phpunit.xml)
- **Test Environment**: RefreshDatabase trait used for database isolation
- **GPS Test Coordinates**: 33.589886, -7.603869 (Casablanca, Morocco)

## GPS Proximity Validation
The API uses the Haversine formula to calculate the distance between the user's GPS coordinates and the client's location.

### Starting a Visit
- **Requirement**: User MUST be within **300 meters** of client location
- **If outside range**: Visit creation fails with 422 error

### Terminating a Visit (Updated Behavior)
- **Within 300 meters**: Visit terminates normally without any warning
- **Beyond 300 meters**: Visit STILL terminates successfully, BUT:
  - A `warning` field is added to the JSON response
  - The termination is logged to Laravel logs with full details
  - `terminated_outside_range` is set to `true` in the database
  - The actual distance is stored in `termination_distance` field

**Rationale**: Users who forget to close visits become blocked from creating new visits. Allowing termination outside range (with logging and warnings) prevents users from being locked out while still maintaining visibility into potential issues.

The distance calculation is implemented in `VisitController::calculateDistance()`.

## Running the Tests

### Run all visit management tests:
```bash
./vendor/bin/phpunit --filter=VisitManagementTest
```

### Run a specific test:
```bash
./vendor/bin/phpunit --filter=VisitManagementTest::test_visit_can_be_started_with_valid_data
```

### Run all tests together:
```bash
./vendor/bin/phpunit --filter=ClientCreationTest,VisitManagementTest
```

## Visit Status Flow
```
[No Visit]
    ↓ (POST /api/visits)
[started]
    ↓ (POST /api/visits/{id}/terminate with status=completed)
[completed]

OR

[started]
    ↓ (POST /api/visits/{id}/terminate with status=aborted)
[aborted]
```

## Related Models
- **Visit** - Main visit model
- **Client** - The client being visited
- **User** - The user performing the visit
- **BaseCommerciale** - Commercial base (inherited from client)
- **Zone** - Zone (inherited from client)
- **RoutingItem** - Optional routing/planning reference

## Visit Model Methods
- `start()` - Marks visit as started
- `complete()` - Completes visit and calculates duration
- `abort()` - Aborts visit and calculates duration
