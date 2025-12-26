<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitReportResource;
use App\Models\Visit;
use App\Models\VisitReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\InvoiceOcrService;

class VisitReportController extends Controller
{
    /**
     * Store or update a visit report with photos.
     * Photo types: facade, shelves, other
     */
    public function store(Request $request)
    {
        $user = $request->user();

        \Log::info('=== Visit Report Store Started ===', [
            'user_id' => $user->id,
            'visit_id' => $request->visit_id,
            'has_photo_facade' => $request->hasFile('photo_facade'),
            'has_photo_shelves' => $request->hasFile('photo_shelves'),
            'has_photos_other' => $request->hasFile('photos_other'),
            'all_request_keys' => array_keys($request->all()),
            'files_keys' => array_keys($request->allFiles()),
        ]);

        $validator = Validator::make($request->all(), [
            'visit_id' => 'required|exists:visits,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'manager_present' => 'nullable|boolean',
            'order_made' => 'nullable|boolean',
            'needs_order' => 'nullable|boolean',
            'order_reference' => 'nullable|string|max:255',
            'order_estimated_amount' => 'nullable|numeric|min:0',
            'stock_issues' => 'nullable|string',
            'stock_shortage_observed' => 'nullable|boolean',
            'competitor_activity' => 'nullable|string',
            'competitor_activity_observed' => 'nullable|boolean',
            'comments' => 'nullable|string',

            // Photo validation
            'photo_facade' => 'nullable|array',
            'photo_facade.*' => 'image|mimes:jpeg,jpg,png|max:10240',
            'photo_shelves' => 'nullable|array',
            'photo_shelves.*' => 'image|mimes:jpeg,jpg,png|max:10240',
            'photos_other' => 'nullable|array',
            'photos_other.*' => 'image|mimes:jpeg,jpg,png|max:10240',
            'photos_facture' => 'nullable|array',
            'photos_facture.*' => 'image|mimes:jpeg,jpg,png|max:10240',

            // GPS for each photo group (optional, defaults to report GPS)
            'photo_facade_gps' => 'nullable|array',
            'photo_facade_gps.*.latitude' => 'nullable|numeric|between:-90,90',
            'photo_facade_gps.*.longitude' => 'nullable|numeric|between:-180,180',
            'photo_shelves_gps' => 'nullable|array',
            'photo_shelves_gps.*.latitude' => 'nullable|numeric|between:-90,90',
            'photo_shelves_gps.*.longitude' => 'nullable|numeric|between:-180,180',
            'photos_other_gps' => 'nullable|array',
            'photos_other_gps.*.latitude' => 'nullable|numeric|between:-90,90',
            'photos_other_gps.*.longitude' => 'nullable|numeric|between:-180,180',
            'photos_facture_gps' => 'nullable|array',
            'photos_facture_gps.*.latitude' => 'nullable|numeric|between:-90,90',
            'photos_facture_gps.*.longitude' => 'nullable|numeric|between:-180,180',

            // Custom titles for other photos (for OCR detection)
            'photos_other_titles' => 'nullable|array',
            'photos_other_titles.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            \Log::error('Visit Report Validation Failed', [
                'errors' => $validator->errors()->toArray(),
                'visit_id' => $request->visit_id,
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info('Visit Report Validation Passed');

        // Get the visit and check authorization
        $visit = Visit::findOrFail($request->visit_id);

        if ($visit->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to create a report for this visit'
            ], 403);
        }

        // Check if visit is started (not terminated yet is OK for creating report)
        if ($visit->status !== 'started' && $visit->status !== 'completed') {
            return response()->json([
                'status' => false,
                'message' => 'You can only create reports for active or completed visits'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create or update the visit report
            $report = VisitReport::updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'manager_present' => $request->input('manager_present', false),
                    'order_made' => $request->input('order_made', false),
                    'needs_order' => $request->input('needs_order', false),
                    'order_reference' => $request->order_reference,
                    'order_estimated_amount' => $request->order_estimated_amount,
                    'stock_issues' => $request->stock_issues,
                    'stock_shortage_observed' => $request->input('stock_shortage_observed', false),
                    'competitor_activity' => $request->competitor_activity,
                    'competitor_activity_observed' => $request->input('competitor_activity_observed', false),
                    'comments' => $request->comments,
                    'validated_at' => now(), // Auto-validate on creation
                ]
            );

            // Handle photo uploads
            $uploadedPhotos = [];

            \Log::info('Starting photo uploads', [
                'report_id' => $report->id,
                'visit_id' => $visit->id,
            ]);

            // Process facade photos
            if ($request->hasFile('photo_facade')) {
                \Log::info('Processing facade photos', [
                    'count' => count($request->file('photo_facade'))
                ]);
                foreach ($request->file('photo_facade') as $index => $photo) {
                    $gps = $request->input("photo_facade_gps.$index", []);
                    \Log::info('Uploading facade photo', [
                        'index' => $index,
                        'filename' => $photo->getClientOriginalName(),
                        'size' => $photo->getSize(),
                    ]);
                    $uploadedPhotos[] = $this->uploadPhoto(
                        $photo,
                        $report,
                        $visit,
                        'facade',
                        'Photo de façade',
                        $gps['latitude'] ?? $request->latitude,
                        $gps['longitude'] ?? $request->longitude
                    );
                }
            }

            // Process shelves photos
            if ($request->hasFile('photo_shelves')) {
                \Log::info('Processing shelves photos', [
                    'count' => count($request->file('photo_shelves'))
                ]);
                foreach ($request->file('photo_shelves') as $index => $photo) {
                    $gps = $request->input("photo_shelves_gps.$index", []);
                    \Log::info('Uploading shelves photo', [
                        'index' => $index,
                        'filename' => $photo->getClientOriginalName(),
                        'size' => $photo->getSize(),
                    ]);
                    $uploadedPhotos[] = $this->uploadPhoto(
                        $photo,
                        $report,
                        $visit,
                        'shelves',
                        'Photo rayons',
                        $gps['latitude'] ?? $request->latitude,
                        $gps['longitude'] ?? $request->longitude
                    );
                }
            }

            // Process other photos
            if ($request->hasFile('photos_other')) {
                \Log::info('Processing other photos', [
                    'count' => count($request->file('photos_other'))
                ]);
                foreach ($request->file('photos_other') as $index => $photo) {
                    $gps = $request->input("photos_other_gps.$index", []);
                    $title = $request->input("photos_other_titles.$index", 'Photo supplémentaire');
                    \Log::info('Uploading other photo', [
                        'index' => $index,
                        'filename' => $photo->getClientOriginalName(),
                        'size' => $photo->getSize(),
                        'title' => $title,
                    ]);
                    $uploadedPhotos[] = $this->uploadPhoto(
                        $photo,
                        $report,
                        $visit,
                        'other',
                        $title,
                        $gps['latitude'] ?? $request->latitude,
                        $gps['longitude'] ?? $request->longitude
                    );
                }
            }

            // Process facture photos (invoice photos with OCR)
            if ($request->hasFile('photos_facture')) {
                \Log::info('Processing facture photos', [
                    'count' => count($request->file('photos_facture'))
                ]);
                foreach ($request->file('photos_facture') as $index => $photo) {
                    $gps = $request->input("photos_facture_gps.$index", []);
                    \Log::info('Uploading facture photo', [
                        'index' => $index,
                        'filename' => $photo->getClientOriginalName(),
                        'size' => $photo->getSize(),
                    ]);
                    $uploadedPhotos[] = $this->uploadPhoto(
                        $photo,
                        $report,
                        $visit,
                        'facture',
                        'Facture',
                        $gps['latitude'] ?? $request->latitude,
                        $gps['longitude'] ?? $request->longitude,
                        true // Process OCR
                    );
                }
            }

            \Log::info('Photo uploads completed', [
                'total_uploaded' => count($uploadedPhotos)
            ]);

            DB::commit();

            \Log::info('Visit report transaction committed', [
                'report_id' => $report->id,
                'photos_count' => count($uploadedPhotos),
            ]);

            // Load relationships
            $report->load(['photos', 'visit']);

            return response()->json([
                'status' => true,
                'message' => 'Visit report created successfully',
                'data' => new VisitReportResource($report),
                'uploaded_photos_count' => count($uploadedPhotos)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Visit report creation failed', [
                'error' => $e->getMessage(),
                'visit_id' => $request->visit_id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create visit report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to upload a photo
     */
    private function uploadPhoto($file, $report, $visit, $type, $title, $latitude, $longitude, $processOcr = false)
    {
        \Log::info('uploadPhoto called', [
            'report_id' => $report->id,
            'visit_id' => $visit->id,
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'processOcr' => $processOcr,
        ]);

        try {
            $path = $file->store('visit_report_photos', 'public');

            \Log::info('File stored successfully', [
                'path' => $path,
            ]);

            // Check if OCR should be processed based on title containing 'facture'
            $ocrService = app(InvoiceOcrService::class);
            $shouldProcessOcr = $processOcr || $ocrService->isInvoicePhoto($title);

            $photoData = [
                'visit_id' => $visit->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'type' => $type,
                'title' => $title,
                'description' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'taken_at' => now(),
                'ocr_status' => $shouldProcessOcr ? 'pending' : null,
            ];

            \Log::info('Creating photo record', ['data' => $photoData]);

            $photo = $report->photos()->create($photoData);

            \Log::info('Photo created successfully', [
                'photo_id' => $photo->id,
            ]);

            // Process OCR if needed
            if ($shouldProcessOcr) {
                \Log::info('Processing OCR for facture photo', [
                    'photo_id' => $photo->id,
                    'title' => $title,
                ]);

                try {
                    $photo->update(['ocr_status' => 'processing']);
                    $ocrData = $ocrService->processInvoice($path, 'public');

                    if ($ocrData) {
                        $photo->update([
                            'ocr_data' => $ocrData,
                            'ocr_status' => 'completed',
                            'ocr_processed_at' => now(),
                        ]);
                        \Log::info('OCR completed successfully', [
                            'photo_id' => $photo->id,
                            'invoice_number' => $ocrData['invoice']['invoice_number'] ?? 'N/A',
                        ]);
                    } else {
                        $photo->update(['ocr_status' => 'failed']);
                        \Log::warning('OCR returned no data', ['photo_id' => $photo->id]);
                    }
                } catch (\Exception $ocrException) {
                    $photo->update(['ocr_status' => 'failed']);
                    \Log::error('OCR processing failed', [
                        'photo_id' => $photo->id,
                        'error' => $ocrException->getMessage(),
                    ]);
                }

                // Refresh the photo to get updated data
                $photo->refresh();
            }

            return $photo;

        } catch (\Exception $e) {
            \Log::error('Photo upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a visit report
     */
    public function show($visitId)
    {
        $user = request()->user();

        $visit = Visit::findOrFail($visitId);

        // Check authorization
        if ($visit->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to view this report'
            ], 403);
        }

        $report = VisitReport::with(['photos', 'visit'])
            ->where('visit_id', $visitId)
            ->first();

        if (!$report) {
            return response()->json([
                'status' => false,
                'message' => 'No report found for this visit'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => new VisitReportResource($report)
        ], 200);
    }

    /**
     * Get all reports created by the authenticated user for a specific client
     */
    public function indexByClient(Request $request, $clientId)
    {
        $user = $request->user();

        $reports = VisitReport::with(['photos', 'visit.client'])
            ->whereHas('visit', function ($query) use ($user, $clientId) {
                $query->where('user_id', $user->id)
                      ->where('client_id', $clientId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => VisitReportResource::collection($reports)
        ], 200);
    }

    /**
     * Delete a photo from visit report
     */
    public function deletePhoto(Request $request, $visitId, $photoId)
    {
        $user = $request->user();

        $visit = Visit::findOrFail($visitId);

        // Check authorization
        if ($visit->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to delete photos from this report'
            ], 403);
        }

        $report = VisitReport::where('visit_id', $visitId)->firstOrFail();
        $photo = $report->photos()->findOrFail($photoId);

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
