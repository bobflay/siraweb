<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VisitPhoto;
use App\Services\InvoiceOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InvoiceOcrController extends Controller
{
    protected InvoiceOcrService $ocrService;

    public function __construct(InvoiceOcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Process OCR on uploaded invoice image(s) directly
     * Accepts single image via 'image' field or multiple images via 'images[]' field
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processImage(Request $request)
    {
        // Check if single image or multiple images
        $hasMultiple = $request->hasFile('images');
        $hasSingle = $request->hasFile('image');

        if (!$hasMultiple && !$hasSingle) {
            return response()->json([
                'status' => false,
                'message' => 'No image file uploaded. Use "image" for single file or "images[]" for multiple files.',
            ], 422);
        }

        // Validate based on what was uploaded
        if ($hasMultiple) {
            $validator = Validator::make($request->all(), [
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $paths = [];
            $photoIds = [];
            $files = $hasMultiple ? $request->file('images') : [$request->file('image')];
            $user = $request->user();

            // Store all images permanently as VisitPhoto records
            foreach ($files as $index => $file) {
                if (!$file || !$file->isValid()) {
                    // Clean up already stored files and photos
                    foreach ($paths as $path) {
                        Storage::disk('public')->delete($path);
                    }
                    VisitPhoto::whereIn('id', $photoIds)->delete();
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid image file at position ' . ($index + 1)
                    ], 422);
                }

                // Store in permanent invoice_photos folder
                $path = $file->store('invoice_photos', 'public');

                \Log::info('Processing OCR request - storing image', [
                    'page' => $index + 1,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ]);

                if (!$path) {
                    // Clean up already stored files and photos
                    foreach ($paths as $p) {
                        Storage::disk('public')->delete($p);
                    }
                    VisitPhoto::whereIn('id', $photoIds)->delete();
                    return response()->json([
                        'status' => false,
                        'message' => 'Failed to store uploaded image at position ' . ($index + 1)
                    ], 500);
                }

                // Create VisitPhoto record (without photoable yet - will be attached when invoice is created)
                $photo = VisitPhoto::create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'type' => 'invoice',
                ]);

                $paths[] = $path;
                $photoIds[] = $photo->id;
            }

            \Log::info('Processing OCR request', [
                'total_pages' => count($paths),
                'paths' => $paths,
                'photo_ids' => $photoIds,
            ]);

            // Process OCR with all images
            $ocrData = $this->ocrService->processMultipleImages($paths, 'public');

            if ($ocrData) {
                // Return OCR data with photo_ids for user validation
                $responseData = [
                    'pages_processed' => count($paths),
                    'photo_ids' => $photoIds,
                    'ocr_data' => $ocrData,
                ];

                \Log::info('OCR API Response to mobile app', [
                    'endpoint' => '/api/ocr/invoice',
                    'user_id' => $request->user()->id,
                    'pages_processed' => count($paths),
                    'photo_ids' => $photoIds,
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Invoice data extracted successfully. Please validate and submit to create invoice.',
                    'data' => $responseData
                ], 200);
            }

            // OCR failed - clean up photos
            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }
            VisitPhoto::whereIn('id', $photoIds)->delete();

            return response()->json([
                'status' => false,
                'message' => 'Failed to extract data from invoice'
            ], 422);

        } catch (\Exception $e) {
            // Clean up photos on exception
            if (!empty($paths)) {
                foreach ($paths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
            if (!empty($photoIds)) {
                VisitPhoto::whereIn('id', $photoIds)->delete();
            }

            \Log::error('Direct OCR processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to process invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process OCR on an existing visit photo
     *
     * @param Request $request
     * @param int $photoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPhoto(Request $request, $photoId)
    {
        $user = $request->user();

        $photo = VisitPhoto::with('visit')->findOrFail($photoId);

        // Check authorization
        if ($photo->visit && $photo->visit->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to process this photo'
            ], 403);
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($photo->file_path)) {
            return response()->json([
                'status' => false,
                'message' => 'Photo file not found'
            ], 404);
        }

        try {
            \Log::info('Processing OCR for existing photo', [
                'photo_id' => $photo->id,
            ]);

            $photo->update(['ocr_status' => 'processing']);

            $ocrData = $this->ocrService->processInvoice($photo->file_path, 'public');

            if ($ocrData) {
                // Store OCR data on photo for reference, but don't create invoice yet
                $photo->update([
                    'ocr_data' => $ocrData,
                    'ocr_status' => 'completed',
                    'ocr_processed_at' => now(),
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Invoice data extracted successfully. Please validate and submit to create invoice.',
                    'data' => [
                        'photo_id' => $photo->id,
                        'ocr_data' => $ocrData,
                        'ocr_status' => 'completed',
                        'ocr_processed_at' => now()->toIso8601String(),
                    ]
                ], 200);
            }

            $photo->update(['ocr_status' => 'failed']);

            return response()->json([
                'status' => false,
                'message' => 'Failed to extract data from invoice'
            ], 422);

        } catch (\Exception $e) {
            $photo->update(['ocr_status' => 'failed']);

            \Log::error('OCR processing failed for photo', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to process invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear OCR cache (for debugging)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache(Request $request)
    {
        $cleared = $this->ocrService->clearCache();

        return response()->json([
            'status' => $cleared,
            'message' => $cleared ? 'OCR cache cleared successfully' : 'Failed to clear OCR cache'
        ], $cleared ? 200 : 500);
    }

    /**
     * Get OCR result for a photo
     *
     * @param Request $request
     * @param int $photoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOcrResult(Request $request, $photoId)
    {
        $user = $request->user();

        $photo = VisitPhoto::with('visit')->findOrFail($photoId);

        // Check authorization
        if ($photo->visit && $photo->visit->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to view this photo'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'photo_id' => $photo->id,
                'ocr_status' => $photo->ocr_status,
                'ocr_data' => $photo->ocr_data,
                'ocr_processed_at' => $photo->ocr_processed_at?->toIso8601String(),
            ]
        ], 200);
    }
}
