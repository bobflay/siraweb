<?php

namespace Tests\Feature;

use App\Models\BaseCommerciale;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected BaseCommerciale $base;
    protected Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base and zone
        $this->base = BaseCommerciale::factory()->create();
        $this->zone = Zone::factory()->create([
            'base_commerciale_id' => $this->base->id,
        ]);

        // Create a user for testing
        $this->user = User::factory()->create();

        // Create agent role and assign to user
        $agentRole = Role::factory()->agent()->create();
        $this->user->roles()->attach($agentRole->id);

        // Create a client with known GPS coordinates
        $this->client = Client::factory()->create([
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
            'created_by' => $this->user->id,
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ]);

        // Assign the client to the user so they have access
        $this->client->assignedUsers()->attach($this->user->id, [
            'assigned_by' => $this->user->id,
            'assigned_at' => now(),
            'role' => 'primary',
            'active' => true,
        ]);
    }

    // ==================== START VISIT TESTS ====================

    /**
     * Test successful visit creation (start visit)
     */
    public function test_visit_can_be_started_with_valid_data(): void
    {
        $visitData = [
            'client_id' => $this->client->id,
            'latitude' => 33.589886, // Same as client
            'longitude' => -7.603869, // Same as client
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'client_id',
                    'user_id',
                    'base_commerciale_id',
                    'zone_id',
                    'started_at',
                    'ended_at',
                    'duration_seconds',
                    'status',
                    'latitude',
                    'longitude',
                    'client',
                    'user',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'status' => true,
                'message' => 'Visit created successfully',
                'data' => [
                    'client_id' => $this->client->id,
                    'user_id' => $this->user->id,
                    'status' => 'started',
                ]
            ]);

        // Assert database has the visit
        $this->assertDatabaseHas('visits', [
            'client_id' => $this->client->id,
            'user_id' => $this->user->id,
            'status' => 'started',
        ]);
    }

    /**
     * Test visit creation requires authentication
     */
    public function test_visit_creation_requires_authentication(): void
    {
        $visitData = [
            'client_id' => $this->client->id,
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->postJson('/api/visits', $visitData);

        $response->assertStatus(401);
    }

    /**
     * Test visit creation fails with missing required fields
     */
    public function test_visit_creation_fails_with_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    'client_id',
                    'latitude',
                    'longitude',
                ]
            ])
            ->assertJson([
                'status' => false,
                'message' => 'Validation failed',
            ]);
    }

    /**
     * Test visit creation fails with non-existent client
     */
    public function test_visit_creation_fails_with_nonexistent_client(): void
    {
        $visitData = [
            'client_id' => 99999,
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_id']);
    }

    /**
     * Test visit creation fails when user is too far from client (GPS proximity check)
     */
    public function test_visit_creation_fails_when_user_too_far_from_client(): void
    {
        $visitData = [
            'client_id' => $this->client->id,
            'latitude' => 33.593000, // More than 300 meters away (~450m)
            'longitude' => -7.608000,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'You must be within 300 meters of the client location to create a visit',
            ]);
    }

    /**
     * Test user cannot start multiple visits simultaneously
     */
    public function test_user_cannot_start_multiple_visits_simultaneously(): void
    {
        // Create first visit
        Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
        ]);

        // Try to create another visit
        $visitData = [
            'client_id' => $this->client->id,
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'You have an unterminated visit. Please complete or abort it before starting a new one.',
            ]);
    }

    /**
     * Test visit creation with optional routing_item_id
     */
    public function test_visit_can_be_created_with_routing_item(): void
    {
        $visitData = [
            'client_id' => $this->client->id,
            'routing_item_id' => null, // Would be a real routing item ID in production
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(201);
    }

    /**
     * Test visit creation with custom started_at timestamp
     */
    public function test_visit_can_be_created_with_custom_started_at(): void
    {
        $customStartTime = now()->subMinutes(5)->toIso8601String();

        $visitData = [
            'client_id' => $this->client->id,
            'started_at' => $customStartTime,
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(201);
    }

    /**
     * Test visit sets correct base_commerciale_id and zone_id from client
     */
    public function test_visit_inherits_base_and_zone_from_client(): void
    {
        $visitData = [
            'client_id' => $this->client->id,
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'base_commerciale_id' => $this->base->id,
                    'zone_id' => $this->zone->id,
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'client_id' => $this->client->id,
            'base_commerciale_id' => $this->base->id,
            'zone_id' => $this->zone->id,
        ]);
    }

    /**
     * Test invalid latitude validation
     */
    public function test_visit_creation_fails_with_invalid_latitude(): void
    {
        $visitData = [
            'client_id' => $this->client->id,
            'latitude' => 100, // Invalid
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }

    /**
     * Test invalid longitude validation
     */
    public function test_visit_creation_fails_with_invalid_longitude(): void
    {
        $visitData = [
            'client_id' => $this->client->id,
            'latitude' => 33.589886,
            'longitude' => 200, // Invalid
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits', $visitData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['longitude']);
    }

    // ==================== TERMINATE VISIT TESTS ====================

    /**
     * Test successful visit completion (stop visit)
     */
    public function test_visit_can_be_completed_successfully(): void
    {
        $visit = Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
            'started_at' => now()->subHours(1),
        ]);

        $terminateData = [
            'status' => 'completed',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Visit terminated successfully',
                'data' => [
                    'id' => $visit->id,
                    'status' => 'completed',
                ]
            ])
            ->assertJsonMissing(['warning']); // No warning when within range

        // Assert database updated
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'completed',
        ]);

        // Assert ended_at and duration_seconds are set
        $visit->refresh();
        $this->assertNotNull($visit->ended_at);
        $this->assertNotNull($visit->duration_seconds);
        $this->assertGreaterThan(0, $visit->duration_seconds);
        $this->assertFalse($visit->terminated_outside_range); // Within range
        $this->assertLessThan(300, $visit->termination_distance); // Distance logged
    }

    /**
     * Test successful visit abort
     */
    public function test_visit_can_be_aborted_successfully(): void
    {
        $visit = Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
            'started_at' => now()->subHours(1),
        ]);

        $terminateData = [
            'status' => 'aborted',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Visit terminated successfully',
                'data' => [
                    'id' => $visit->id,
                    'status' => 'aborted',
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'aborted',
        ]);
    }

    /**
     * Test visit termination requires authentication
     */
    public function test_visit_termination_requires_authentication(): void
    {
        $visit = Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
        ]);

        $terminateData = [
            'status' => 'completed',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(401);
    }

    /**
     * Test user cannot terminate another user's visit
     */
    public function test_user_cannot_terminate_another_users_visit(): void
    {
        $otherUser = User::factory()->create();

        $visit = Visit::factory()->create([
            'user_id' => $otherUser->id,
            'client_id' => $this->client->id,
            'status' => 'started',
        ]);

        $terminateData = [
            'status' => 'completed',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => 'You are not authorized to terminate this visit',
            ]);
    }

    /**
     * Test cannot terminate already terminated visit
     */
    public function test_cannot_terminate_already_terminated_visit(): void
    {
        $visit = Visit::factory()->completed()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $terminateData = [
            'status' => 'completed',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'This visit is already terminated',
            ]);
    }

    /**
     * Test visit termination fails with missing required fields
     */
    public function test_visit_termination_fails_with_missing_required_fields(): void
    {
        $visit = Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status', 'latitude', 'longitude']);
    }

    /**
     * Test visit termination fails with invalid status
     */
    public function test_visit_termination_fails_with_invalid_status(): void
    {
        $visit = Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
        ]);

        $terminateData = [
            'status' => 'invalid_status',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test visit termination succeeds with warning when user is outside range
     */
    public function test_visit_termination_succeeds_with_warning_when_outside_range(): void
    {
        $visit = Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
        ]);

        $terminateData = [
            'status' => 'completed',
            'latitude' => 33.593000, // More than 300 meters away (~450m)
            'longitude' => -7.608000,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Visit terminated successfully',
            ])
            ->assertJsonStructure([
                'warning',
                'data' => [
                    'termination_distance',
                    'terminated_outside_range',
                ]
            ]);

        // Verify the visit was terminated with the warning flags
        $visit->refresh();
        $this->assertEquals('completed', $visit->status);
        $this->assertTrue($visit->terminated_outside_range);
        $this->assertGreaterThan(300, $visit->termination_distance);
    }

    /**
     * Test visit termination with non-existent visit ID
     */
    public function test_visit_termination_fails_with_nonexistent_visit(): void
    {
        $terminateData = [
            'status' => 'completed',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/visits/99999/terminate', $terminateData);

        $response->assertStatus(404);
    }

    /**
     * Test visit duration is calculated correctly
     */
    public function test_visit_duration_is_calculated_correctly(): void
    {
        $startedAt = now()->subHours(2);

        $visit = Visit::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => 'started',
            'started_at' => $startedAt,
        ]);

        $terminateData = [
            'status' => 'completed',
            'latitude' => 33.589886,
            'longitude' => -7.603869,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

        $response->assertStatus(200);

        $visit->refresh();

        // Should be approximately 2 hours (7200 seconds), allowing some variance
        $this->assertGreaterThan(7190, $visit->duration_seconds);
        $this->assertLessThan(7210, $visit->duration_seconds);
    }

    /**
     * Test both completed and aborted statuses are accepted
     */
    public function test_both_completed_and_aborted_statuses_are_accepted(): void
    {
        $statuses = ['completed', 'aborted'];

        foreach ($statuses as $status) {
            $visit = Visit::factory()->create([
                'user_id' => $this->user->id,
                'client_id' => $this->client->id,
                'status' => 'started',
            ]);

            $terminateData = [
                'status' => $status,
                'latitude' => 33.589886,
                'longitude' => -7.603869,
            ];

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson("/api/visits/{$visit->id}/terminate", $terminateData);

            $response->assertStatus(200);

            $this->assertDatabaseHas('visits', [
                'id' => $visit->id,
                'status' => $status,
            ]);
        }
    }
}
