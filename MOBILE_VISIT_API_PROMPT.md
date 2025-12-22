# Implement Visit Start/End Feature

## API Endpoints

**Base URL:** `{API_BASE_URL}/api`

**Authentication:** All requests require `Authorization: Bearer {token}` header.

---

## 1. Start a Visit

```
POST /visits
```

**Request Body:**
```json
{
    "client_id": 50,
    "latitude": 33.5731104,
    "longitude": -7.5898434,
    "routing_item_id": null
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| client_id | integer | Yes | The ID of the client being visited |
| latitude | float | Yes | User's current GPS latitude (-90 to 90) |
| longitude | float | Yes | User's current GPS longitude (-180 to 180) |
| routing_item_id | integer | No | Optional routing item reference |

**Success Response (201):**
```json
{
    "status": true,
    "message": "Visit created successfully",
    "data": {
        "id": 1,
        "client_id": 50,
        "user_id": 2,
        "base_commerciale_id": 5,
        "zone_id": 14,
        "status": "started",
        "started_at": "2025-12-21T00:15:00+00:00",
        "latitude": 33.5731104,
        "longitude": -7.5898434,
        "client": {
            "id": 50,
            "name": "Client Name",
            "type": "Boutique",
            "city": "Casablanca"
        },
        "user": {
            "id": 2,
            "name": "Commercial Name"
        }
    }
}
```

**Possible Errors:**

| Code | Error Key | Message | Reason |
|------|-----------|---------|--------|
| 422 | `proximity` | "You must be within 15 meters of the client location to create a visit" | User is more than 15 meters from client GPS location |
| 422 | `visit` | "You have an unterminated visit. Please complete or abort it before starting a new one." | User already has an active visit with status "started" |
| 422 | `client_id` | "The selected client_id is invalid." | Client does not exist |
| 403 | - | "You are not authorized to create a visit for this client" | User doesn't have access to this client |

**Error Response Example:**
```json
{
    "status": false,
    "message": "You must be within 15 meters of the client location to create a visit",
    "errors": {
        "proximity": ["Current distance: 127.45 meters"]
    }
}
```

---

## 2. Terminate a Visit (Complete or Abort)

```
POST /visits/{visit_id}/terminate
```

**Request Body:**
```json
{
    "status": "completed",
    "latitude": 33.5731104,
    "longitude": -7.5898434
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| status | string | Yes | Either "completed" or "aborted" |
| latitude | float | Yes | User's current GPS latitude (-90 to 90) |
| longitude | float | Yes | User's current GPS longitude (-180 to 180) |

**Success Response (200):**
```json
{
    "status": true,
    "message": "Visit terminated successfully",
    "data": {
        "id": 1,
        "client_id": 50,
        "user_id": 2,
        "status": "completed",
        "started_at": "2025-12-21T00:15:00+00:00",
        "ended_at": "2025-12-21T00:45:00+00:00",
        "duration_seconds": 1800,
        "latitude": 33.5731104,
        "longitude": -7.5898434
    }
}
```

**Possible Errors:**

| Code | Error Key | Message | Reason |
|------|-----------|---------|--------|
| 422 | `proximity` | "You must be within 15 meters of the client location to terminate the visit" | User is more than 15 meters from client GPS location |
| 422 | `status` | "This visit is already terminated" | Visit status is not "started" |
| 403 | - | "You are not authorized to terminate this visit" | User doesn't own this visit |
| 404 | - | "Not Found" | Visit ID doesn't exist |

---

## Implementation Requirements

### 1. GPS is Mandatory
- Get device location before calling either endpoint
- Both `latitude` and `longitude` are required fields
- Handle GPS permission denied gracefully with user message

### 2. Proximity Check (15 meters)
- User must be within **15 meters** of the client's GPS location to:
  - Start a visit
  - End a visit
- If rejected, display the current distance from error response
- Consider showing a map with user position and client position

### 3. Single Active Visit Rule
- User can only have **one active visit** at a time
- Store the active visit ID in local storage after successful creation
- Before starting new visit, check if there's an active one
- Clear active visit from storage after successful termination

### 4. User Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     START VISIT FLOW                        │
├─────────────────────────────────────────────────────────────┤
│ 1. User selects a client from list                          │
│ 2. User taps "Start Visit" button                           │
│ 3. App requests GPS location                                │
│ 4. App calls POST /visits with client_id + GPS              │
│ 5. On success: Save visit.id locally, show visit in progress│
│ 6. On error: Display error message with distance if proximity│
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                      END VISIT FLOW                         │
├─────────────────────────────────────────────────────────────┤
│ 1. User taps "End Visit" button                             │
│ 2. App shows option: "Complete" or "Abort"                  │
│ 3. App requests GPS location                                │
│ 4. App calls POST /visits/{id}/terminate with status + GPS  │
│ 5. On success: Clear local visit, show success message      │
│ 6. On error: Display error message with distance if proximity│
└─────────────────────────────────────────────────────────────┘
```

### 5. UI States

| State | UI Display |
|-------|------------|
| No active visit | Show "Start Visit" button on client detail |
| Active visit | Show "End Visit" button, visit timer, disable starting new visits |
| GPS loading | Show loading indicator while getting location |
| Too far | Show distance and message, optionally show map |

### 6. Abort vs Complete
- **Complete**: Normal end of visit (successful visit)
- **Abort**: Cancel visit (couldn't complete, client unavailable, etc.)
- Both require GPS proximity check

### 7. Error Handling

```javascript
// Example error handling
if (response.status === 422) {
    if (response.errors.proximity) {
        // Show distance: "You are X meters away from the client"
        showProximityError(response.errors.proximity[0]);
    } else if (response.errors.visit) {
        // Show: "Please end your current visit first"
        showActiveVisitError(response.errors.visit[0]);
    }
} else if (response.status === 403) {
    showUnauthorizedError(response.message);
}
```

### 8. Offline Considerations
- Cache client GPS coordinates for proximity calculation
- Queue visit actions if offline, sync when back online
- Show clear offline indicator to user
