<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\VisitPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
            'type' => 'nullable|string|in:Boutique,Supermarché,Demi-grossiste,Grossiste,Distributeur',
            'city' => 'nullable|string|max:255',
            'zone_id' => 'nullable|integer|exists:zones,id',
            'commercial_id' => 'nullable|integer|exists:users,id',
            'has_alert' => 'nullable|boolean',
            'updated_after' => 'nullable|date',
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

        // Validate request
        $validator = \Validator::make($request->all(), [
            'code' => 'required|string|unique:clients,code|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Boutique,Supermarché,Demi-grossiste,Grossiste,Distributeur,Autre',
            'potential' => 'required|in:A,B,C',
            'base_commerciale_id' => 'required|exists:bases_commerciales,id',
            'zone_id' => 'required|exists:zones,id',
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
            'base_commerciale_id' => $request->base_commerciale_id,
            'zone_id' => $request->zone_id,
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
            'type' => 'sometimes|required|in:Boutique,Supermarché,Demi-grossiste,Grossiste,Distributeur,Autre',
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
        $client = Client::findOrFail($clientId);

        // Check if user has access to this client
        $user = $request->user();
        $accessibleClients = Client::forUser($user)->pluck('id');

        if (!$accessibleClients->contains($client->id)) {
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
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedPhotos = [];

        foreach ($request->file('photos') as $photo) {
            // Store photo in public disk
            $path = $photo->store('client_photos', 'public');

            // Create photo record
            $clientPhoto = $client->photos()->create([
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
            ]);

            $uploadedPhotos[] = [
                'id' => $clientPhoto->id,
                'url' => Storage::url($path),
                'file_name' => $clientPhoto->file_name,
                'type' => $clientPhoto->type,
            ];
        }

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
        $client = Client::findOrFail($clientId);

        // Check if user has access to this client
        $user = $request->user();
        $accessibleClients = Client::forUser($user)->pluck('id');

        if (!$accessibleClients->contains($client->id)) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to delete photos from this client'
            ], 403);
        }

        $photo = $client->photos()->findOrFail($photoId);

        // Delete file from storage
        if (Storage::disk('public')->exists($photo->file_path)) {
            Storage::disk('public')->delete($photo->file_path);
        }

        // Delete database record
        $photo->delete();

        return response()->json([
            'status' => true,
            'message' => 'Photo deleted successfully'
        ], 200);
    }
}
