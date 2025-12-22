<?php

namespace Tests\Feature;

use App\Models\BaseCommerciale;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BaseCommerciale $base;
    protected Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary dependencies
        $this->base = BaseCommerciale::factory()->create();
        $this->zone = Zone::factory()->create();

        // Create a user for testing
        $this->user = User::factory()->create();
    }

    /**
     * Test successful client creation with valid data
     */
    public function test_client_can_be_created_with_valid_data(): void
    {
        $clientData = [
            'code' => 'CLI-001',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'manager_name' => 'John Doe',
            'phone' => '+1234567890',
            'whatsapp' => '+1234567890',
            'email' => 'test@example.com',
            'city' => 'Test City',
            'district' => 'Test District',
            'address_description' => 'Test Address',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'manager_name',
                    'email',
                    'phones',
                    'city',
                    'district',
                    'address',
                    'latitude',
                    'longitude',
                    'zone_id',
                    'commercial_id',
                    'potential',
                    'visit_frequency',
                    'last_visit_date',
                    'has_open_alert',
                    'photos',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'status' => true,
                'message' => 'Client created successfully',
                'data' => [
                    'name' => 'Test Client',
                    'type' => 'Boutique',
                    'potential' => 'A',
                ]
            ]);

        // Assert database has the client
        $this->assertDatabaseHas('clients', [
            'code' => 'CLI-001',
            'name' => 'Test Client',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test client creation fails without authentication
     */
    public function test_client_creation_requires_authentication(): void
    {
        $clientData = [
            'code' => 'CLI-002',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->postJson('/api/clients', $clientData);

        $response->assertStatus(401);
    }

    /**
     * Test validation fails with missing required fields
     */
    public function test_client_creation_fails_with_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    'code',
                    'name',
                    'type',
                    'potential',
                    'base_commerciale_id',
                    'zone_id',
                    'phone',
                    'city',
                    'latitude',
                    'longitude',
                    'visit_frequency',
                ]
            ])
            ->assertJson([
                'status' => false,
                'message' => 'Validation failed',
            ]);
    }

    /**
     * Test validation fails with invalid client type
     */
    public function test_client_creation_fails_with_invalid_type(): void
    {
        $clientData = [
            'code' => 'CLI-003',
            'name' => 'Test Client',
            'type' => 'InvalidType',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * Test validation fails with invalid potential
     */
    public function test_client_creation_fails_with_invalid_potential(): void
    {
        $clientData = [
            'code' => 'CLI-004',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'Z',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['potential']);
    }

    /**
     * Test validation fails with duplicate client code
     */
    public function test_client_creation_fails_with_duplicate_code(): void
    {
        // Create an existing client
        Client::factory()->create([
            'code' => 'CLI-005',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'created_by' => $this->user->id,
        ]);

        $clientData = [
            'code' => 'CLI-005', // Duplicate code
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /**
     * Test validation fails with invalid latitude
     */
    public function test_client_creation_fails_with_invalid_latitude(): void
    {
        $clientData = [
            'code' => 'CLI-006',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 100, // Invalid: > 90
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }

    /**
     * Test validation fails with invalid longitude
     */
    public function test_client_creation_fails_with_invalid_longitude(): void
    {
        $clientData = [
            'code' => 'CLI-007',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => 200, // Invalid: > 180
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['longitude']);
    }

    /**
     * Test validation fails with invalid email format
     */
    public function test_client_creation_fails_with_invalid_email(): void
    {
        $clientData = [
            'code' => 'CLI-008',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'email' => 'invalid-email',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test validation fails with invalid visit frequency
     */
    public function test_client_creation_fails_with_invalid_visit_frequency(): void
    {
        $clientData = [
            'code' => 'CLI-009',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'invalid_frequency',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visit_frequency']);
    }

    /**
     * Test validation fails with non-existent base commerciale
     */
    public function test_client_creation_fails_with_invalid_base_commerciale(): void
    {
        $clientData = [
            'code' => 'CLI-010',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => 99999, // Non-existent
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['base_commerciale_id']);
    }

    /**
     * Test validation fails with non-existent zone
     */
    public function test_client_creation_fails_with_invalid_zone(): void
    {
        $clientData = [
            'code' => 'CLI-011',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => 99999, // Non-existent
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['zone_id']);
    }

    /**
     * Test client is created with all optional fields
     */
    public function test_client_can_be_created_with_all_optional_fields(): void
    {
        $clientData = [
            'code' => 'CLI-012',
            'name' => 'Test Client',
            'type' => 'Supermarché',
            'potential' => 'B',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'manager_name' => 'Jane Smith',
            'phone' => '+1234567890',
            'whatsapp' => '+9876543210',
            'email' => 'jane@example.com',
            'city' => 'Test City',
            'district' => 'Downtown',
            'address_description' => 'Near the central market',
            'latitude' => 33.123456,
            'longitude' => -117.654321,
            'visit_frequency' => 'monthly',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('clients', [
            'code' => 'CLI-012',
            'manager_name' => 'Jane Smith',
            'whatsapp' => '+9876543210',
            'email' => 'jane@example.com',
            'district' => 'Downtown',
            'is_active' => false,
        ]);
    }

    /**
     * Test client created_by field is set to authenticated user
     */
    public function test_client_created_by_field_is_set_to_authenticated_user(): void
    {
        $clientData = [
            'code' => 'CLI-013',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'C',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(201);

        $client = Client::where('code', 'CLI-013')->first();
        $this->assertEquals($this->user->id, $client->created_by);
    }

    /**
     * Test client is created with default is_active value
     */
    public function test_client_is_created_with_default_is_active_value(): void
    {
        $clientData = [
            'code' => 'CLI-014',
            'name' => 'Test Client',
            'type' => 'Grossiste',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'biweekly',
            // is_active not provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(201);

        $client = Client::where('code', 'CLI-014')->first();
        $this->assertTrue($client->is_active);
    }

    /**
     * Test all valid client types
     */
    public function test_all_valid_client_types_are_accepted(): void
    {
        $validTypes = ['Boutique', 'Supermarché', 'Demi-grossiste', 'Grossiste', 'Distributeur', 'Autre'];

        foreach ($validTypes as $index => $type) {
            $clientData = [
                'code' => 'CLI-TYPE-' . $index,
                'name' => 'Test Client ' . $type,
                'type' => $type,
                'potential' => 'A',
                'base_commerciale_id' => $this->base->id,
                'zone_id' => $this->zone->id,
                'phone' => '+1234567890',
                'city' => 'Test City',
                'latitude' => 12.345678,
                'longitude' => -12.345678,
                'visit_frequency' => 'weekly',
            ];

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/clients', $clientData);

            $response->assertStatus(201);
            $this->assertDatabaseHas('clients', [
                'code' => 'CLI-TYPE-' . $index,
                'type' => $type,
            ]);
        }
    }

    /**
     * Test all valid visit frequencies
     */
    public function test_all_valid_visit_frequencies_are_accepted(): void
    {
        $validFrequencies = ['weekly', 'biweekly', 'monthly', 'other'];

        foreach ($validFrequencies as $index => $frequency) {
            $clientData = [
                'code' => 'CLI-FREQ-' . $index,
                'name' => 'Test Client Frequency',
                'type' => 'Boutique',
                'potential' => 'A',
                'base_commerciale_id' => $this->base->id,
                'zone_id' => $this->zone->id,
                'phone' => '+1234567890',
                'city' => 'Test City',
                'latitude' => 12.345678,
                'longitude' => -12.345678,
                'visit_frequency' => $frequency,
            ];

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/clients', $clientData);

            $response->assertStatus(201);
            $this->assertDatabaseHas('clients', [
                'code' => 'CLI-FREQ-' . $index,
                'visit_frequency' => $frequency,
            ]);
        }
    }

    /**
     * Test client response includes photos array
     */
    public function test_client_response_includes_photos_array(): void
    {
        $clientData = [
            'code' => 'CLI-015',
            'name' => 'Test Client',
            'type' => 'Boutique',
            'potential' => 'A',
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'phone' => '+1234567890',
            'city' => 'Test City',
            'latitude' => 12.345678,
            'longitude' => -12.345678,
            'visit_frequency' => 'weekly',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'photos',
                ]
            ])
            ->assertJson([
                'data' => [
                    'photos' => []
                ]
            ]);
    }
}
