<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\VisitPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    /**
     * Display a listing of clients with role-based access and filtering.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        // Validate query parameters
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:Boutique,Supermarché,Demi-grossiste,Grossiste,Distributeur,Autre,Mamie marché,Etalage,Boulangerie',
            'city' => 'nullable|string|max:255',
            'zone_id' => 'nullable|integer|exists:zones,id',
            'commercial_id' => 'nullable|integer|exists:users,id',
            'has_alert' => 'nullable|boolean',
            'updated_after' => 'nullable|date',
            // Map bounds filtering (optional) - for filtering clients visible on map viewport
            'map_north' => 'nullable|numeric|between:-90,90',
            'map_south' => 'nullable|numeric|between:-90,90',
            'map_east' => 'nullable|numeric|between:-180,180',
            'map_west' => 'nullable|numeric|between:-180,180',
        ]);


        // Check if user can filter by commercial_id (admin-only feature)
        if (!empty($validated['commercial_id'])) {
            if (!$user->hasRole('ROLE_SUPER_ADMIN')
                && !$user->hasRole('ROLE_COMMERCIAL_ADMIN')
                && !$user->hasRole('super_admin')
                && !$user->hasRole('admin')
                && !$user->hasRole('direction')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to filter by commercial_id'
                ], 403);
            }
        }

        $limit = $validated['limit'] ?? 20;

        // Build query with role-based access
        $query = Client::query()
            ->with('photos') // Eager load photos
            ->forUser($user)
            ->search($validated['search'] ?? null)
            ->filterByType($validated['type'] ?? null)
            ->filterByCity($validated['city'] ?? null)
            ->filterByZone($validated['zone_id'] ?? null)
            ->filterByCommercial($validated['commercial_id'] ?? null)
            ->filterByAlert($validated['has_alert'] ?? null)
            ->updatedAfter($validated['updated_after'] ?? null)
            ->withinMapBounds(
                $validated['map_north'] ?? null,
                $validated['map_south'] ?? null,
                $validated['map_east'] ?? null,
                $validated['map_west'] ?? null
            )
            ->orderBy('updated_at', 'desc');

        // Paginate results
        $clients = $query->paginate($limit);

        return ClientResource::collection($clients)->additional([
            'success' => true,
            'meta' => [
                'page' => $clients->currentPage(),
                'limit' => $clients->perPage(),
                'total' => $clients->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Log incoming request for client creation
        Log::info('Client creation request received', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'ip_address' => $request->ip(),
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Get the user's zone
        $userZone = $user->zones()->first();

        if (!$userZone) {
            return response()->json([
                'status' => false,
                'message' => 'You must be assigned to a zone to create a client'
            ], 403);
        }

        // Validate request
        $validator = \Validator::make($request->all(), [
            'code' => 'required|string|unique:clients,code|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Boutique,Supermarché,Demi-grossiste,Grossiste,Distributeur,Autre,Mamie marché,Etalage,Boulangerie',
            'potential' => 'required|in:A,B,C',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'required|string|max:255',
            'district' => 'nullable|string|max:255',
            'address_description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'visit_frequency' => 'required|in:weekly,biweekly,monthly,other',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create client
        $client = Client::create([
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'potential' => $request->potential,
            'base_commerciale_id' => $userZone->base_commerciale_id,
            'zone_id' => $userZone->id,
            'created_by' => $user->id,
            'manager_name' => $request->manager_name,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email,
            'city' => $request->city,
            'district' => $request->district,
            'address_description' => $request->address_description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'visit_frequency' => $request->visit_frequency,
            'is_active' => $request->is_active ?? true,
        ]);

        // Load relationships
        $client->load(['baseCommerciale', 'zone', 'creator', 'photos']);

        return response()->json([
            'status' => true,
            'message' => 'Client created successfully',
            'data' => new ClientResource($client)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        // Find client
        $client = Client::findOrFail($id);

        // Check if user has access to this client
        $accessibleClients = Client::forUser($user)->pluck('id');

        if (!$accessibleClients->contains($client->id)) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to update this client'
            ], 403);
        }

        // Validate request
        $validator = \Validator::make($request->all(), [
            'code' => 'sometimes|required|string|unique:clients,code,' . $id . '|max:255',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:Boutique,Supermarché,Demi-grossiste,Grossiste,Distributeur,Autre,Mamie marché,Etalage,Boulangerie',
            'potential' => 'sometimes|required|in:A,B,C',
            'base_commerciale_id' => 'sometimes|required|exists:bases_commerciales,id',
            'zone_id' => 'sometimes|required|exists:zones,id',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'sometimes|required|string|max:255',
            'district' => 'nullable|string|max:255',
            'address_description' => 'nullable|string',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'visit_frequency' => 'sometimes|required|in:weekly,biweekly,monthly,other',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update client with only provided fields
        $client->update($request->only([
            'code',
            'name',
            'type',
            'potential',
            'base_commerciale_id',
            'zone_id',
            'manager_name',
            'phone',
            'whatsapp',
            'email',
            'city',
            'district',
            'address_description',
            'latitude',
            'longitude',
            'visit_frequency',
            'is_active',
        ]));

        // Load relationships
        $client->load(['baseCommerciale', 'zone', 'creator', 'photos']);

        return response()->json([
            'status' => true,
            'message' => 'Client updated successfully',
            'data' => new ClientResource($client)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Upload photos for a client
     */
    public function uploadPhotos(Request $request, $clientId)
    {
        $user = $request->user();

        \Log::info('=== Client Photo Upload Started ===', [
            'user_id' => $user->id,
            'client_id' => $clientId,
            'has_photos' => $request->hasFile('photos'),
            'photos_count' => $request->hasFile('photos') ? count($request->file('photos')) : 0,
            'type' => $request->input('type'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'all_request_keys' => array_keys($request->all()),
            'files_keys' => array_keys($request->allFiles()),
        ]);

        $client = Client::findOrFail($clientId);

        // Check if user has access to this client
        $accessibleClients = Client::forUser($user)->pluck('id');

        if (!$accessibleClients->contains($client->id)) {
            \Log::warning('Client photo upload unauthorized', [
                'user_id' => $user->id,
                'client_id' => $clientId,
            ]);
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to add photos to this client'
            ], 403);
        }

        // Validate request
        $validator = \Validator::make($request->all(), [
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|image|mimes:jpeg,jpg,png|max:10240', // 10MB max
            'type' => 'nullable|in:facade,shelves,stock,anomaly,other',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            \Log::error('Client photo upload validation failed', [
                'user_id' => $user->id,
                'client_id' => $clientId,
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info('Client photo upload validation passed', [
            'client_id' => $clientId,
            'client_name' => $client->name,
        ]);

        $uploadedPhotos = [];

        foreach ($request->file('photos') as $index => $photo) {
            \Log::info('Processing client photo', [
                'index' => $index,
                'client_id' => $clientId,
                'filename' => $photo->getClientOriginalName(),
                'size' => $photo->getSize(),
                'mime_type' => $photo->getMimeType(),
            ]);

            try {
                // Store photo in public disk
                $path = $photo->store('client_photos', 'public');

                \Log::info('Client photo stored successfully', [
                    'index' => $index,
                    'path' => $path,
                ]);

                // Create photo record
                $photoData = [
                    'visit_id' => null, // No visit association for client photos
                    'file_path' => $path,
                    'file_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                    'type' => $request->input('type', 'other'),
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'latitude' => $request->input('latitude', 0),
                    'longitude' => $request->input('longitude', 0),
                    'taken_at' => now(),
                ];

                \Log::info('Creating client photo record', [
                    'index' => $index,
                    'data' => $photoData,
                ]);

                $clientPhoto = $client->photos()->create($photoData);

                \Log::info('Client photo record created', [
                    'index' => $index,
                    'photo_id' => $clientPhoto->id,
                    'latitude' => $clientPhoto->latitude,
                    'longitude' => $clientPhoto->longitude,
                ]);

                $uploadedPhotos[] = [
                    'id' => $clientPhoto->id,
                    'url' => Storage::url($path),
                    'file_name' => $clientPhoto->file_name,
                    'type' => $clientPhoto->type,
                ];
            } catch (\Exception $e) {
                \Log::error('Client photo upload failed', [
                    'index' => $index,
                    'client_id' => $clientId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        \Log::info('=== Client Photo Upload Completed ===', [
            'client_id' => $clientId,
            'total_uploaded' => count($uploadedPhotos),
            'photo_ids' => array_column($uploadedPhotos, 'id'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Photos uploaded successfully',
            'data' => $uploadedPhotos
        ], 201);
    }

    /**
     * Delete a client photo
     */
    public function deletePhoto(Request $request, $clientId, $photoId)
    {
        $user = $request->user();

        \Log::info('=== Client Photo Delete Started ===', [
            'user_id' => $user->id,
            'client_id' => $clientId,
            'photo_id' => $photoId,
        ]);

        $client = Client::findOrFail($clientId);

        // Check if user has access to this client
        $accessibleClients = Client::forUser($user)->pluck('id');

        if (!$accessibleClients->contains($client->id)) {
            \Log::warning('Client photo delete unauthorized', [
                'user_id' => $user->id,
                'client_id' => $clientId,
                'photo_id' => $photoId,
            ]);
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to delete photos from this client'
            ], 403);
        }

        $photo = $client->photos()->findOrFail($photoId);

        \Log::info('Client photo found for deletion', [
            'photo_id' => $photo->id,
            'file_path' => $photo->file_path,
            'latitude' => $photo->latitude,
            'longitude' => $photo->longitude,
        ]);

        // Delete file from storage
        if (Storage::disk('public')->exists($photo->file_path)) {
            Storage::disk('public')->delete($photo->file_path);
            \Log::info('Client photo file deleted from storage', [
                'photo_id' => $photoId,
                'file_path' => $photo->file_path,
            ]);
        } else {
            \Log::warning('Client photo file not found in storage', [
                'photo_id' => $photoId,
                'file_path' => $photo->file_path,
            ]);
        }

        // Delete database record
        $photo->delete();

        \Log::info('=== Client Photo Delete Completed ===', [
            'user_id' => $user->id,
            'client_id' => $clientId,
            'photo_id' => $photoId,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Photo deleted successfully'
        ], 200);
    }
}
