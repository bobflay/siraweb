# Mobile App Prompt: Visit Termination with Outside Range Handling

## Context
I need you to update the mobile app to handle the new visit termination behavior. The API has been updated to allow users to close visits even when they're outside the 300-meter allowed range. This prevents users from being locked out when they forget to close a visit before leaving the client location.

## API Changes

### Endpoint
`POST /api/visits/{visitId}/terminate`

### Request (Unchanged)
```json
{
  "status": "completed",  // or "aborted"
  "latitude": 33.593000,
  "longitude": -7.608000
}
```

### Response - Scenario 1: Within Range (< 300 meters)
```json
{
  "status": true,
  "message": "Visit terminated successfully",
  "data": {
    "id": 1,
    "client_id": 1,
    "user_id": 1,
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
      "name": "Client Name",
      "type": "Boutique",
      "city": "Casablanca"
    },
    "created_at": "2025-12-22T12:00:00Z",
    "updated_at": "2025-12-22T14:00:00Z"
  }
}
```

### Response - Scenario 2: Outside Range (> 300 meters) - NEW BEHAVIOR
```json
{
  "status": true,
  "message": "Visit terminated successfully",
  "warning": "Warning: Visit was terminated 450.25 meters away from the client location. The allowed range is 300 meters.",
  "data": {
    "id": 1,
    "client_id": 1,
    "user_id": 1,
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
      "name": "Client Name",
      "type": "Boutique",
      "city": "Casablanca"
    },
    "created_at": "2025-12-22T12:00:00Z",
    "updated_at": "2025-12-22T14:00:00Z"
  }
}
```

## Required Changes

### 1. Update API Response Type/Interface
Add the optional `warning` field to your visit termination response type:

**TypeScript Example:**
```typescript
interface VisitTerminationResponse {
  status: boolean;
  message: string;
  warning?: string;  // NEW - Optional warning message
  data: {
    id: number;
    client_id: number;
    user_id: number;
    started_at: string;
    ended_at: string;
    duration_seconds: number;
    status: 'completed' | 'aborted';
    latitude: number;
    longitude: number;
    termination_distance: number | null;  // NEW
    terminated_outside_range: boolean;     // NEW
    client: {
      id: number;
      name: string;
      type: string;
      city: string;
    };
    created_at: string;
    updated_at: string;
  };
}
```

**Dart/Flutter Example:**
```dart
class VisitTerminationResponse {
  final bool status;
  final String message;
  final String? warning;  // NEW - Optional warning
  final VisitData data;

  VisitTerminationResponse({
    required this.status,
    required this.message,
    this.warning,  // Optional
    required this.data,
  });

  factory VisitTerminationResponse.fromJson(Map<String, dynamic> json) {
    return VisitTerminationResponse(
      status: json['status'],
      message: json['message'],
      warning: json['warning'],  // Can be null
      data: VisitData.fromJson(json['data']),
    );
  }
}

class VisitData {
  // ... existing fields ...
  final double? terminationDistance;     // NEW
  final bool terminatedOutsideRange;     // NEW

  VisitData({
    // ... existing parameters ...
    this.terminationDistance,
    this.terminatedOutsideRange = false,
  });

  factory VisitData.fromJson(Map<String, dynamic> json) {
    return VisitData(
      // ... existing fields ...
      terminationDistance: json['termination_distance']?.toDouble(),
      terminatedOutsideRange: json['terminated_outside_range'] ?? false,
    );
  }
}
```

### 2. Update Visit Termination Logic
Modify your visit termination function to handle the warning:

**React Native Example:**
```javascript
const terminateVisit = async (visitId, status, latitude, longitude) => {
  try {
    setLoading(true);

    const response = await api.post(`/visits/${visitId}/terminate`, {
      status,
      latitude,
      longitude
    });

    if (response.data.status) {
      // Visit was terminated successfully

      if (response.data.warning) {
        // Show warning dialog to user
        Alert.alert(
          'Visit Closed with Warning',
          response.data.warning,
          [
            {
              text: 'OK',
              onPress: () => {
                // Navigate back or update UI
                navigation.goBack();
              }
            }
          ],
          { cancelable: false }
        );
      } else {
        // Normal success - no warning
        showToast('Visit closed successfully', 'success');
        navigation.goBack();
      }
    }
  } catch (error) {
    Alert.alert('Error', 'Failed to terminate visit');
  } finally {
    setLoading(false);
  }
};
```

**Flutter Example:**
```dart
Future<void> terminateVisit(int visitId, String status, double latitude, double longitude) async {
  try {
    setState(() => isLoading = true);

    final response = await apiService.terminateVisit(
      visitId: visitId,
      status: status,
      latitude: latitude,
      longitude: longitude,
    );

    if (response.status) {
      if (response.warning != null) {
        // Show warning dialog
        await showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => AlertDialog(
            title: Text('Visit Closed with Warning'),
            content: Text(response.warning!),
            icon: Icon(Icons.warning_amber, color: Colors.orange, size: 48),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.of(context).pop();
                  Navigator.of(context).pop(); // Go back to visits list
                },
                child: Text('OK'),
              ),
            ],
          ),
        );
      } else {
        // Normal success
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Visit closed successfully')),
        );
        Navigator.of(context).pop();
      }
    }
  } catch (e) {
    showErrorDialog('Failed to terminate visit');
  } finally {
    setState(() => isLoading = false);
  }
}
```

### 3. UI/UX Design for Warning Dialog

#### Design Specifications:
- **Icon**: Warning icon (⚠️) in orange/amber color
- **Title**: "Visit Closed with Warning"
- **Message**: Display the warning text from API
- **Button**: Single "OK" or "Understood" button
- **Style**: Different from error dialogs (use warning colors, not error red)

#### Example Warning Dialog Design:
```
┌─────────────────────────────────────┐
│        ⚠️ Warning                   │
│                                     │
│  Visit Closed with Warning          │
│                                     │
│  Visit was terminated 450.25        │
│  meters away from the client        │
│  location. The allowed range is     │
│  300 meters.                        │
│                                     │
│          [ Understood ]             │
└─────────────────────────────────────┘
```

### 4. Optional: Show Distance Badge in Visit Details
Consider showing a visual indicator when viewing completed visits that were terminated outside range:

```dart
// Example Flutter widget
if (visit.terminatedOutsideRange) {
  Container(
    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: Colors.orange.shade100,
      borderRadius: BorderRadius.circular(12),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(Icons.location_off, size: 16, color: Colors.orange),
        SizedBox(width: 4),
        Text(
          'Closed ${visit.terminationDistance?.toStringAsFixed(0)}m away',
          style: TextStyle(
            color: Colors.orange.shade900,
            fontSize: 12,
          ),
        ),
      ],
    ),
  )
}
```

## Important Notes

1. **No Error Handling Needed**: The API will ALWAYS succeed (return 200), so you don't need to handle 422 errors for distance anymore

2. **Backward Compatibility**: Old app versions will still work - they'll just ignore the `warning` field

3. **User Experience**: The warning should be informative but not alarming - the visit WAS closed successfully

4. **Testing**: Test with:
   - GPS coordinates within 300m of client (should NOT show warning)
   - GPS coordinates beyond 300m of client (SHOULD show warning)

## Testing Checklist

- [ ] Visit termination within 300m shows success without warning
- [ ] Visit termination beyond 300m shows success WITH warning dialog
- [ ] Warning dialog displays the message correctly
- [ ] Warning dialog styling is distinct from error dialogs (orange/amber theme)
- [ ] User can dismiss warning and return to visits list
- [ ] Optional: Distance badge shows on completed visits in list view
- [ ] App doesn't crash if `warning` field is missing (backward compatibility)
- [ ] App doesn't crash if `termination_distance` is null

## Questions to Consider

1. **Do you want to show the distance in the visits list view?** If yes, we can add a badge/indicator showing visits closed outside range.

2. **Do you want analytics/tracking?** Consider logging warning events to analytics (Firebase, Mixpanel, etc.) for monitoring.

3. **Should supervisors see different UI?** Admins/supervisors might want to see a list of all visits closed outside range.

## API Base URL
The endpoint remains the same:
`POST {baseUrl}/api/visits/{visitId}/terminate`

## Sample Test Data
Use these coordinates for testing:

**Client Location**: 33.589886, -7.603869 (Casablanca)

**Within Range** (< 300m):
- 33.589886, -7.603869 (same location - 0m)
- 33.590100, -7.604000 (~30m away)

**Outside Range** (> 300m):
- 33.593000, -7.608000 (~450m away) ← Use this for testing warning
- 33.595000, -7.610000 (~700m away)

## Summary
The key change is: **visits can now ALWAYS be terminated**, but the app should display a warning popup when the user is far from the client. The visit still closes successfully - the warning is just informational and helps maintain data quality awareness.

Implement this and users will never be locked out of creating new visits again! 🎉
