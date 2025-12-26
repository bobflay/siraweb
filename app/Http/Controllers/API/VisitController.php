<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\Client;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VisitController extends Controller
{
    /**
     * Maximum allowed distance (in meters) between user and client location for visits.
     */
    private const MAX_ALLOWED_DISTANCE = 300;

    /**
     * Predefined reasons for exceeding distance limit.
     */
    public const DISTANCE_EXCEED_REASONS = [
        'client_moved' => 'Le client a déménagé',
        'gps_error' => 'Erreur GPS / Signal faible',
        'client_outside' => 'Client rencontré à l\'extérieur',
        'wrong_coordinates' => 'Coordonnées client incorrectes',
        'other' => 'Autres',
    ];

    /**
     * Store a newly created visit for a specific client.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'routing_item_id' => 'nullable|exists:routing_items,id',
            'started_at' => 'nullable|date',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user has an unterminated visit
        $unterminatedVisit = Visit::where('user_id', $user->id)
            ->where('status', 'started')
            ->first();

        if ($unterminatedVisit) {
            return response()->json([
                'status' => false,
                'message' => 'You have an unterminated visit. Please complete or abort it before starting a new one.',
                'errors' => [
                    'visit' => ["Unterminated visit ID: {$unterminatedVisit->id}"]
                ]
            ], 422);
        }

        // Get the client
        $client = Client::findOrFail($request->client_id);

        // Check GPS proximity (must be within 300 meters of the client)
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $client->latitude,
            $client->longitude
        );

        if ($distance > 300) {
            return response()->json([
                'status' => false,
                'message' => 'Vous devez être à moins de 300 mètres du client pour créer une visite',
                'errors' => [
                    'proximity' => ["Distance actuelle: {$distance} mètres"]
                ]
            ], 422);
        }

        // Check if user has access to this client
        $accessibleClients = Client::forUser($user)->pluck('id');

        if (!$accessibleClients->contains($client->id)) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to create a visit for this client'
            ], 403);
        }

        // Create the visit
        $visit = Visit::create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'base_commerciale_id' => $client->base_commerciale_id,
            'zone_id' => $client->zone_id,
            'routing_item_id' => $request->routing_item_id,
            'started_at' => $request->started_at ?? now(),
            'status' => 'started',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Load relationships
        $visit->load(['client', 'user', 'baseCommerciale', 'zone']);

        return response()->json([
            'status' => true,
            'message' => 'Visit created successfully',
            'data' => new VisitResource($visit)
        ], 201);
    }

    /**
     * Terminate a visit (complete or abort).
     */
    public function terminate(Request $request, $id)
    {
        $user = $request->user();

        $visit = Visit::with('client')->findOrFail($id);

        // Check if user owns the visit
        if ($visit->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to terminate this visit'
            ], 403);
        }

        // Check if visit is already terminated
        if ($visit->status !== 'started') {
            return response()->json([
                'status' => false,
                'message' => 'This visit is already terminated',
                'errors' => [
                    'status' => ["Current status: {$visit->status}"]
                ]
            ], 422);
        }

        // Calculate distance first to determine validation rules
        $distance = $this->calculateDistance(
            $request->latitude ?? 0,
            $request->longitude ?? 0,
            $visit->client->latitude,
            $visit->client->longitude
        );

        $isOutsideRange = $distance > self::MAX_ALLOWED_DISTANCE;

        // Build validation rules - require reason if outside range
        $validationRules = [
            'status' => 'required|in:completed,aborted',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];

        if ($isOutsideRange) {
            $validReasons = implode(',', array_keys(self::DISTANCE_EXCEED_REASONS));
            $validationRules['distance_exceed_reason'] = "required|string|in:{$validReasons}";
            $validationRules['distance_exceed_reason_other'] = 'required_if:distance_exceed_reason,other|nullable|string|max:500';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];

            // If outside range and missing reason, indicate that reason is required
            if ($isOutsideRange && !$request->has('distance_exceed_reason')) {
                $response['requires_reason'] = true;
                $response['distance'] = $distance;
                $response['max_allowed_distance'] = self::MAX_ALLOWED_DISTANCE;
                $response['available_reasons'] = self::DISTANCE_EXCEED_REASONS;
            }

            return response()->json($response, 422);
        }

        $warning = null;

        // Log the termination distance
        $visit->termination_distance = $distance;
        $visit->terminated_outside_range = $isOutsideRange;

        if ($isOutsideRange) {
            // Store the reason
            $visit->distance_exceed_reason = $request->distance_exceed_reason;
            if ($request->distance_exceed_reason === 'other') {
                $visit->distance_exceed_reason_other = $request->distance_exceed_reason_other;
            }

            // Log the event for monitoring
            \Log::warning('Visit terminated outside allowed range', [
                'visit_id' => $visit->id,
                'user_id' => $user->id,
                'client_id' => $visit->client_id,
                'distance' => $distance,
                'allowed_range' => self::MAX_ALLOWED_DISTANCE,
                'status' => $request->status,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'client_latitude' => $visit->client->latitude,
                'client_longitude' => $visit->client->longitude,
                'distance_exceed_reason' => $request->distance_exceed_reason,
                'distance_exceed_reason_other' => $request->distance_exceed_reason_other,
            ]);

            $warning = "Warning: Visit was terminated {$distance} meters away from the client location. The allowed range is " . self::MAX_ALLOWED_DISTANCE . " meters.";
        }

        // Terminate the visit
        if ($request->status === 'completed') {
            $visit->complete();
        } else {
            $visit->abort();
        }

        $visit->load(['client', 'user', 'baseCommerciale', 'zone']);

        $response = [
            'status' => true,
            'message' => 'Visit terminated successfully',
            'data' => new VisitResource($visit)
        ];

        if ($warning) {
            $response['warning'] = $warning;
        }

        return response()->json($response, 200);
    }

    /**
     * Get available reasons for distance exceed.
     */
    public function distanceExceedReasons()
    {
        return response()->json([
            'status' => true,
            'data' => self::DISTANCE_EXCEED_REASONS
        ]);
    }

    /**
     * Get the active (started) visit for the authenticated user.
     */
    public function active(Request $request)
    {
        $user = $request->user();

        $activeVisit = Visit::where('user_id', $user->id)
            ->where('status', 'started')
            ->with(['client', 'user', 'baseCommerciale', 'zone'])
            ->first();

        if (!$activeVisit) {
            return response()->json([
                'status' => true,
                'message' => 'No active visit found',
                'data' => null
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Active visit found',
            'data' => new VisitResource($activeVisit)
        ], 200);
    }

    /**
     * Delete a visit (superadmin only).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // Check if user is superadmin
        if (!$user->hasRole('ROLE_SUPER_ADMIN') && !$user->hasRole('super_admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Only superadmins can delete visits'
            ], 403);
        }

        $visit = Visit::findOrFail($id);
        $visit->delete();

        return response()->json([
            'status' => true,
            'message' => 'Visit deleted successfully'
        ], 200);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula.
     *
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @return float Distance in meters
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
