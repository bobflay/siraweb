# Client API Creation Tests

## Overview
Comprehensive unit tests for the Client API creation endpoint (`POST /api/clients`).

## Test File
- **Location**: [tests/Feature/ClientCreationTest.php](tests/Feature/ClientCreationTest.php)
- **Total Tests**: 18
- **Status**: ✅ All Passing

## Test Coverage

### Successful Creation Tests
1. **test_client_can_be_created_with_valid_data**
   - Validates that a client can be successfully created with all required fields
   - Verifies the response structure and database record

2. **test_client_can_be_created_with_all_optional_fields**
   - Tests creation with all optional fields provided (manager_name, whatsapp, email, district, etc.)

3. **test_all_valid_client_types_are_accepted**
   - Tests all valid client types: Boutique, Supermarché, Demi-grossiste, Grossiste, Distributeur, Autre

4. **test_all_valid_visit_frequencies_are_accepted**
   - Tests all valid visit frequencies: weekly, biweekly, monthly, other

### Authentication & Authorization Tests
5. **test_client_creation_requires_authentication**
   - Ensures unauthenticated requests are rejected with 401 status

### Validation Tests
6. **test_client_creation_fails_with_missing_required_fields**
   - Validates that requests without required fields fail with 422 status

7. **test_client_creation_fails_with_invalid_type**
   - Tests rejection of invalid client types

8. **test_client_creation_fails_with_invalid_potential**
   - Tests rejection of invalid potential values (must be A, B, or C)

9. **test_client_creation_fails_with_duplicate_code**
   - Ensures unique constraint on client code is enforced

10. **test_client_creation_fails_with_invalid_latitude**
    - Validates latitude range (-90 to 90)

11. **test_client_creation_fails_with_invalid_longitude**
    - Validates longitude range (-180 to 180)

12. **test_client_creation_fails_with_invalid_email**
    - Tests email format validation

13. **test_client_creation_fails_with_invalid_visit_frequency**
    - Tests rejection of invalid visit frequency values

14. **test_client_creation_fails_with_invalid_base_commerciale**
    - Ensures foreign key constraint for base_commerciale_id

15. **test_client_creation_fails_with_invalid_zone**
    - Ensures foreign key constraint for zone_id

### Business Logic Tests
16. **test_client_created_by_field_is_set_to_authenticated_user**
    - Verifies that created_by is automatically set to the authenticated user's ID

17. **test_client_is_created_with_default_is_active_value**
    - Ensures is_active defaults to true when not provided

18. **test_client_response_includes_photos_array**
    - Verifies response includes empty photos array for new clients

## Required Fields
- code (unique)
- name
- type (Boutique|Supermarché|Demi-grossiste|Grossiste|Distributeur|Autre)
- potential (A|B|C)
- base_commerciale_id (foreign key)
- zone_id (foreign key)
- phone
- city
- latitude (-90 to 90)
- longitude (-180 to 180)
- visit_frequency (weekly|biweekly|monthly|other)

## Optional Fields
- manager_name
- whatsapp
- email
- district
- address_description
- is_active (defaults to true)

## Factory Files Created
1. **database/factories/BaseCommercialeFactory.php** - Factory for BaseCommerciale model
2. **database/factories/ZoneFactory.php** - Factory for Zone model
3. **database/factories/ClientFactory.php** - Factory for Client model

## Model Updates
Added `HasFactory` trait to:
- [app/Models/BaseCommerciale.php](app/Models/BaseCommerciale.php)
- [app/Models/Zone.php](app/Models/Zone.php)
- [app/Models/Client.php](app/Models/Client.php)

## Test Configuration
- **Database**: MySQL (testing database)
- **Configuration**: [phpunit.xml](phpunit.xml)
- **Test Environment**: RefreshDatabase trait used for database isolation

## Running the Tests

### Run all client creation tests:
```bash
./vendor/bin/phpunit --filter=ClientCreationTest
```

### Run a specific test:
```bash
./vendor/bin/phpunit --filter=ClientCreationTest::test_client_can_be_created_with_valid_data
```

## API Endpoint
- **URL**: `POST /api/clients`
- **Authentication**: Required (Sanctum)
- **Response Format**: JSON
- **Success Status**: 201 Created
- **Error Status**: 401 (Unauthorized), 422 (Validation Error)

## Response Structure
```json
{
  "status": true,
  "message": "Client created successfully",
  "data": {
    "id": 1,
    "name": "Test Client",
    "type": "Boutique",
    "manager_name": "John Doe",
    "email": "test@example.com",
    "phones": ["+1234567890"],
    "city": "Test City",
    "district": "Test District",
    "address": "Test Address",
    "latitude": 12.345678,
    "longitude": -12.345678,
    "zone_id": 1,
    "commercial_id": 1,
    "potential": "A",
    "visit_frequency": "weekly",
    "last_visit_date": null,
    "has_open_alert": false,
    "photos": [],
    "created_at": "2025-12-22T12:00:00Z",
    "updated_at": "2025-12-22T12:00:00Z"
  }
}
```
