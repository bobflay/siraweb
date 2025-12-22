# Visit Report API Documentation

## Overview
API endpoint for creating and managing visit reports with photo uploads. Each visit can have one report that includes facade photos, shelves photos, supplementary photos, and various observations.

## Endpoints

### 1. Create/Update Visit Report
`POST /api/visits/{visitId}/report`

Creates or updates a visit report with photos and observations.

#### Headers
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

#### URL Parameters
- `visitId` (integer, required): The ID of the visit

#### Form Data Parameters

##### Basic Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `latitude` | decimal | Yes | GPS latitude where report was created |
| `longitude` | decimal | Yes | GPS longitude where report was created |
| `manager_present` | boolean | No | Was the manager present during visit? |
| `order_made` | boolean | No | Was an order placed during this visit? |
| `needs_order` | boolean | No | Does the client need to place an order? |
| `order_reference` | string | No | Reference number of the order (if made) |
| `order_estimated_amount` | decimal | No | Estimated amount of the order |
| `stock_shortage_observed` | boolean | No | Was stock shortage/rupture observed? |
| `stock_issues` | text | No | Details about stock issues/rupture |
| `competitor_activity_observed` | boolean | No | Was competitor activity observed? |
| `competitor_activity` | text | No | Details about competitor activity |
| `comments` | text | No | General comments about the visit |

##### Photo Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `photo_facade` | file[] | No | Array of facade photos (Photo de façade) |
| `photo_shelves` | file[] | No | Array of shelves photos (Photo rayons) |
| `photos_other` | file[] | No | Array of supplementary photos |

##### Photo GPS (Optional)
Each photo can have its own GPS coordinates. If not provided, the report's main GPS coordinates will be used.

| Field | Type | Description |
|-------|------|-------------|
| `photo_facade_gps` | array | GPS data for facade photos |
| `photo_facade_gps[0][latitude]` | decimal | Latitude for first facade photo |
| `photo_facade_gps[0][longitude]` | decimal | Longitude for first facade photo |
| `photo_shelves_gps` | array | GPS data for shelves photos |
| `photo_shelves_gps[0][latitude]` | decimal | Latitude for first shelves photo |
| `photo_shelves_gps[0][longitude]` | decimal | Longitude for first shelves photo |
| `photos_other_gps` | array | GPS data for other photos |
| `photos_other_gps[0][latitude]` | decimal | Latitude for first other photo |
| `photos_other_gps[0][longitude]` | decimal | Longitude for first other photo |

#### Photo Constraints
- **Formats**: JPEG, JPG, PNG
- **Max size**: 10MB per photo
- **Max total**: No hard limit, but recommended max 20 photos per report

#### Example Request (cURL)
```bash
curl -X POST https://api.example.com/api/visits/123/report \
  -H "Authorization: Bearer {token}" \
  -F "latitude=33.589886" \
  -F "longitude=-7.603869" \
  -F "manager_present=true" \
  -F "order_made=true" \
  -F "order_reference=ORD-2025-001" \
  -F "order_estimated_amount=15000" \
  -F "stock_shortage_observed=true" \
  -F "stock_issues=Rupture de stock sur les produits A et B" \
  -F "competitor_activity_observed=false" \
  -F "comments=Visite très productive" \
  -F "photo_facade[]=@facade1.jpg" \
  -F "photo_facade[]=@facade2.jpg" \
  -F "photo_shelves[]=@rayon1.jpg" \
  -F "photo_shelves[]=@rayon2.jpg" \
  -F "photos_other[]=@other1.jpg" \
  -F "photo_facade_gps[0][latitude]=33.589886" \
  -F "photo_facade_gps[0][longitude]=-7.603869"
```

#### Success Response (201 Created)
```json
{
  "status": true,
  "message": "Visit report created successfully",
  "uploaded_photos_count": 5,
  "data": {
    "id": 1,
    "visit_id": 123,
    "latitude": 33.589886,
    "longitude": -7.603869,
    "manager_present": true,
    "order_made": true,
    "needs_order": false,
    "order_reference": "ORD-2025-001",
    "order_estimated_amount": 15000.00,
    "stock_issues": "Rupture de stock sur les produits A et B",
    "stock_shortage_observed": true,
    "competitor_activity": null,
    "competitor_activity_observed": false,
    "comments": "Visite très productive",
    "validated_at": "2025-12-22T14:30:00Z",
    "is_validated": true,
    "photos": [
      {
        "id": 1,
        "url": "https://api.example.com/storage/visit_report_photos/abc123.jpg",
        "file_name": "facade1.jpg",
        "type": "facade",
        "title": "Photo de façade",
        "description": null,
        "latitude": 33.589886,
        "longitude": -7.603869,
        "taken_at": "2025-12-22T14:30:00Z"
      },
      {
        "id": 2,
        "url": "https://api.example.com/storage/visit_report_photos/def456.jpg",
        "file_name": "facade2.jpg",
        "type": "facade",
        "title": "Photo de façade",
        "description": null,
        "latitude": 33.589886,
        "longitude": -7.603869,
        "taken_at": "2025-12-22T14:30:00Z"
      },
      {
        "id": 3,
        "url": "https://api.example.com/storage/visit_report_photos/ghi789.jpg",
        "file_name": "rayon1.jpg",
        "type": "shelves",
        "title": "Photo rayons",
        "description": null,
        "latitude": 33.589886,
        "longitude": -7.603869,
        "taken_at": "2025-12-22T14:30:00Z"
      },
      {
        "id": 4,
        "url": "https://api.example.com/storage/visit_report_photos/jkl012.jpg",
        "file_name": "rayon2.jpg",
        "type": "shelves",
        "title": "Photo rayons",
        "description": null,
        "latitude": 33.589886,
        "longitude": -7.603869,
        "taken_at": "2025-12-22T14:30:00Z"
      },
      {
        "id": 5,
        "url": "https://api.example.com/storage/visit_report_photos/mno345.jpg",
        "file_name": "other1.jpg",
        "type": "other",
        "title": "Photo supplémentaire",
        "description": null,
        "latitude": 33.589886,
        "longitude": -7.603869,
        "taken_at": "2025-12-22T14:30:00Z"
      }
    ],
    "visit": {
      "id": 123,
      "client_id": 45,
      "status": "completed",
      "started_at": "2025-12-22T13:00:00Z",
      "ended_at": "2025-12-22T14:30:00Z"
    },
    "created_at": "2025-12-22T14:30:00Z",
    "updated_at": "2025-12-22T14:30:00Z"
  }
}
```

#### Error Responses

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**
```json
{
  "status": false,
  "message": "You are not authorized to create a report for this visit"
}
```

**422 Validation Error**
```json
{
  "status": false,
  "message": "Validation failed",
  "errors": {
    "latitude": ["The latitude field is required."],
    "longitude": ["The longitude field is required."],
    "photo_facade.0": ["The photo facade.0 must be an image."]
  }
}
```

---

### 2. Get Visit Report
`GET /api/visits/{visitId}/report`

Retrieves the report for a specific visit.

#### Headers
```
Authorization: Bearer {token}
```

#### Success Response (200 OK)
Same structure as create response.

#### Error Response (404 Not Found)
```json
{
  "status": false,
  "message": "No report found for this visit"
}
```

---

### 3. Delete Report Photo
`DELETE /api/visits/{visitId}/report/photos/{photoId}`

Deletes a specific photo from a visit report.

#### Headers
```
Authorization: Bearer {token}
```

#### Success Response (200 OK)
```json
{
  "status": true,
  "message": "Photo deleted successfully"
}
```

---

## Photo Types

| Type | Field Name | French Label | Description |
|------|-----------|--------------|-------------|
| `facade` | `photo_facade` | Photo de façade | Exterior/facade photos of the store |
| `shelves` | `photo_shelves` | Photo rayons | Photos of product shelves/displays |
| `other` | `photos_other` | Photos supplémentaires | Any additional photos |

---

## Business Rules

1. **One Report Per Visit**: Each visit can only have one report. Subsequent POST requests will update the existing report.

2. **Visit Status**: Reports can only be created for visits with status `started` or `completed`.

3. **Authorization**: Only the user who created the visit can create/view/modify its report.

4. **Auto-Validation**: Reports are automatically validated (validated_at set to current time) upon creation.

5. **GPS Requirements**:
   - Report must include main GPS coordinates
   - Each photo can optionally have its own GPS coordinates
   - If photo GPS is not provided, report GPS is used

6. **Photo Storage**: Photos are stored in the `public/visit_report_photos` directory using Laravel's storage system.

7. **Polymorphic Relationship**: Photos use Laravel's polymorphic relationship (`photoable_type` and `photoable_id`) to link to VisitReport.

---

## Implementation Examples

### JavaScript/React Native Example
```javascript
const createVisitReport = async (visitId, reportData, photos) => {
  const formData = new FormData();

  // Add text fields
  formData.append('latitude', reportData.latitude);
  formData.append('longitude', reportData.longitude);
  formData.append('manager_present', reportData.managerPresent ? '1' : '0');
  formData.append('order_made', reportData.orderMade ? '1' : '0');
  formData.append('needs_order', reportData.needsOrder ? '1' : '0');
  formData.append('stock_shortage_observed', reportData.stockShortage ? '1' : '0');
  formData.append('competitor_activity_observed', reportData.competitorActivity ? '1' : '0');

  if (reportData.orderReference) {
    formData.append('order_reference', reportData.orderReference);
  }
  if (reportData.orderAmount) {
    formData.append('order_estimated_amount', reportData.orderAmount);
  }
  if (reportData.stockIssues) {
    formData.append('stock_issues', reportData.stockIssues);
  }
  if (reportData.competitorActivityDetails) {
    formData.append('competitor_activity', reportData.competitorActivityDetails);
  }
  if (reportData.comments) {
    formData.append('comments', reportData.comments);
  }

  // Add facade photos
  photos.facade?.forEach((photo, index) => {
    formData.append(`photo_facade[]`, {
      uri: photo.uri,
      type: photo.type || 'image/jpeg',
      name: photo.name || `facade_${index}.jpg`
    });

    if (photo.latitude && photo.longitude) {
      formData.append(`photo_facade_gps[${index}][latitude]`, photo.latitude);
      formData.append(`photo_facade_gps[${index}][longitude]`, photo.longitude);
    }
  });

  // Add shelves photos
  photos.shelves?.forEach((photo, index) => {
    formData.append(`photo_shelves[]`, {
      uri: photo.uri,
      type: photo.type || 'image/jpeg',
      name: photo.name || `shelves_${index}.jpg`
    });

    if (photo.latitude && photo.longitude) {
      formData.append(`photo_shelves_gps[${index}][latitude]`, photo.latitude);
      formData.append(`photo_shelves_gps[${index}][longitude]`, photo.longitude);
    }
  });

  // Add other photos
  photos.other?.forEach((photo, index) => {
    formData.append(`photos_other[]`, {
      uri: photo.uri,
      type: photo.type || 'image/jpeg',
      name: photo.name || `other_${index}.jpg`
    });

    if (photo.latitude && photo.longitude) {
      formData.append(`photos_other_gps[${index}][latitude]`, photo.latitude);
      formData.append(`photos_other_gps[${index}][longitude]`, photo.longitude);
    }
  });

  const response = await fetch(`${API_URL}/visits/${visitId}/report`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
    body: formData
  });

  return await response.json();
};
```

### Flutter/Dart Example
```dart
Future<Map<String, dynamic>> createVisitReport(
  int visitId,
  VisitReportData reportData,
  List<File> facadePhotos,
  List<File> shelvesPhotos,
  List<File> otherPhotos,
) async {
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('$apiUrl/visits/$visitId/report'),
  );

  request.headers['Authorization'] = 'Bearer $token';

  // Add text fields
  request.fields['latitude'] = reportData.latitude.toString();
  request.fields['longitude'] = reportData.longitude.toString();
  request.fields['manager_present'] = reportData.managerPresent ? '1' : '0';
  request.fields['order_made'] = reportData.orderMade ? '1' : '0';
  request.fields['needs_order'] = reportData.needsOrder ? '1' : '0';
  request.fields['stock_shortage_observed'] = reportData.stockShortage ? '1' : '0';
  request.fields['competitor_activity_observed'] = reportData.competitorActivity ? '1' : '0';

  if (reportData.orderReference != null) {
    request.fields['order_reference'] = reportData.orderReference!;
  }
  if (reportData.comments != null) {
    request.fields['comments'] = reportData.comments!;
  }

  // Add facade photos
  for (var i = 0; i < facadePhotos.length; i++) {
    request.files.add(await http.MultipartFile.fromPath(
      'photo_facade[]',
      facadePhotos[i].path,
    ));
  }

  // Add shelves photos
  for (var i = 0; i < shelvesPhotos.length; i++) {
    request.files.add(await http.MultipartFile.fromPath(
      'photo_shelves[]',
      shelvesPhotos[i].path,
    ));
  }

  // Add other photos
  for (var i = 0; i < otherPhotos.length; i++) {
    request.files.add(await http.MultipartFile.fromPath(
      'photos_other[]',
      otherPhotos[i].path,
    ));
  }

  var response = await request.send();
  var responseBody = await response.stream.bytesToString();
  return json.decode(responseBody);
}
```

---

## Database Schema

### visit_reports table
```sql
CREATE TABLE visit_reports (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  visit_id BIGINT UNSIGNED UNIQUE,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  manager_present BOOLEAN DEFAULT FALSE,
  order_made BOOLEAN DEFAULT FALSE,
  needs_order BOOLEAN DEFAULT FALSE,
  order_reference VARCHAR(255) NULL,
  order_estimated_amount DECIMAL(12,2) NULL,
  stock_issues TEXT NULL,
  stock_shortage_observed BOOLEAN DEFAULT FALSE,
  competitor_activity TEXT NULL,
  competitor_activity_observed BOOLEAN DEFAULT FALSE,
  comments TEXT NULL,
  validated_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
);
```

### visit_photos table (polymorphic)
```sql
CREATE TABLE visit_photos (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  photoable_type VARCHAR(255) NOT NULL,  -- 'App\Models\VisitReport'
  photoable_id BIGINT UNSIGNED NOT NULL,  -- visit_report.id
  file_path VARCHAR(255) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  type ENUM('facade','shelves','stock','anomaly','other') NOT NULL,
  title VARCHAR(255) NULL,
  description TEXT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  taken_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  INDEX (photoable_type, photoable_id),
  INDEX (type)
);
```

---

## Files Created/Modified

1. **Migration**: `database/migrations/2025_12_22_152000_update_visit_reports_table_add_new_fields.php`
2. **Model**: `app/Models/VisitReport.php` (updated)
3. **Resource**: `app/Http/Resources/VisitReportResource.php` (new)
4. **Controller**: `app/Http/Controllers/API/VisitReportController.php` (new)
5. **Routes**: `routes/api.php` (updated)

---

## Testing Checklist

- [ ] Create report with all fields
- [ ] Create report with minimal required fields (latitude, longitude)
- [ ] Upload facade photos only
- [ ] Upload shelves photos only
- [ ] Upload supplementary photos only
- [ ] Upload mix of all photo types
- [ ] Include GPS data for each photo
- [ ] Update existing report (POST to same visit ID)
- [ ] Retrieve report
- [ ] Delete a photo from report
- [ ] Test authorization (different user trying to access)
- [ ] Test with invalid visit ID
- [ ] Test with non-image file
- [ ] Test with file exceeding size limit
- [ ] Test without authentication
