<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Anthropic\Laravel\Facades\Anthropic;
use Intervention\Image\Facades\Image;

class InvoiceOcrService
{
    /**
     * Maximum base64 size allowed by Claude API (5MB)
     */
    private const MAX_BASE64_SIZE = 5 * 1024 * 1024;

    /**
     * Target base64 size to aim for after compression (3.5MB to have margin)
     */
    private const TARGET_BASE64_SIZE = 3.5 * 1024 * 1024;

    /**
     * Process an invoice image and extract structured data using Claude API
     *
     * @param string $imagePath Path to the image in storage
     * @param string $disk Storage disk name
     * @return array|null Structured invoice data or null on failure
     */
    public function processInvoice(string $imagePath, string $disk = 'public'): ?array
    {
        return $this->processMultipleImages([$imagePath], $disk);
    }

    /**
     * Process multiple invoice images (pages) and extract structured data using Claude API
     *
     * @param array $imagePaths Array of paths to the images in storage
     * @param string $disk Storage disk name
     * @return array|null Structured invoice data or null on failure
     */
    public function processMultipleImages(array $imagePaths, string $disk = 'public'): ?array
    {
        try {
            $imageContents = [];

            // Process each image
            foreach ($imagePaths as $index => $imagePath) {
                $imageContent = Storage::disk($disk)->get($imagePath);

                // Compress image if needed to stay under Claude's 5MB base64 limit
                $processedImage = $this->compressImageIfNeeded($imageContent, $imagePath, $disk);

                Log::info('Preparing image for Claude API', [
                    'page' => $index + 1,
                    'image_path' => $imagePath,
                    'original_size' => strlen($imageContent),
                    'base64_size' => strlen($processedImage['base64']),
                    'was_compressed' => $processedImage['was_compressed'],
                    'mime_type' => $processedImage['mime_type'],
                ]);

                $imageContents[] = [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $processedImage['mime_type'],
                        'data' => $processedImage['base64'],
                    ],
                ];
            }

            // Build the prompt for Claude (multi-page aware)
            $prompt = $this->buildPrompt(count($imagePaths) > 1);

            // Add the text prompt at the end
            $imageContents[] = [
                'type' => 'text',
                'text' => $prompt,
            ];

            // Call Claude API with vision
            Log::info('Calling Claude API for OCR', [
                'model' => 'claude-sonnet-4-20250514',
                'image_count' => count($imagePaths),
            ]);

            $response = Anthropic::messages()->create([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 8192,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $imageContents,
                    ],
                ],
            ]);

            // Log full response for debugging
            Log::info('Claude API raw response', [
                'response_type' => get_class($response),
                'response_data' => json_encode($response),
            ]);

            // Extract the text response
            $textContent = $response->content[0]->text ?? null;

            Log::info('Claude API text content', [
                'text_content' => $textContent,
                'content_count' => count($response->content ?? []),
            ]);

            if (!$textContent) {
                Log::error('Invoice OCR: No text response from Claude', [
                    'full_response' => json_encode($response),
                ]);
                return null;
            }

            // Parse JSON from response
            $jsonData = $this->extractJsonFromResponse($textContent);

            if (!$jsonData) {
                Log::error('Invoice OCR: Failed to parse JSON from response', [
                    'response' => $textContent,
                ]);
                return null;
            }

            Log::info('Invoice OCR completed successfully', [
                'invoice_number' => $jsonData['invoice']['invoice_number'] ?? 'N/A',
                'pages_processed' => count($imagePaths),
            ]);

            return $jsonData;

        } catch (\Exception $e) {
            Log::error('Invoice OCR failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Clear OCR cache for a specific image or all OCR cache
     */
    public function clearCache(?string $imagePath = null, ?string $disk = 'public'): bool
    {
        try {
            if ($imagePath) {
                $imageContent = Storage::disk($disk)->get($imagePath);
                $imageHash = md5($imageContent);
                $cacheKey = "ocr_invoice_{$imageHash}";
                Cache::store('redis')->forget($cacheKey);
                Log::info('OCR cache cleared for specific image', ['cache_key' => $cacheKey]);
            } else {
                // Clear all OCR cache keys using pattern
                // Use raw Redis command to avoid prefix issues
                $redis = \Illuminate\Support\Facades\Redis::connection();

                // Search for keys with ocr_invoice pattern
                $pattern = '*ocr_invoice_*';

                Log::info('Searching for OCR cache keys', ['pattern' => $pattern]);

                // Use raw command to avoid prefix
                $keys = $redis->command('keys', [$pattern]);

                Log::info('Found OCR cache keys', ['keys' => $keys, 'count' => count($keys)]);

                $deletedCount = 0;
                foreach ($keys as $key) {
                    // Use raw DEL command with the full key name
                    $result = $redis->command('del', [$key]);
                    if ($result > 0) {
                        $deletedCount++;
                    }
                }

                Log::info('All OCR cache cleared', ['keys_deleted' => $deletedCount]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear OCR cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Build the prompt for invoice extraction
     *
     * @param bool $isMultiPage Whether this is a multi-page invoice
     */
    private function buildPrompt(bool $isMultiPage = false): string
    {
        $multiPageInstructions = $isMultiPage
            ? "\n\nIMPORTANT: These images are PAGES of the SAME invoice. Combine all information from ALL pages into a SINGLE JSON response. Extract ALL line items from ALL pages and merge them into one items array."
            : '';

        return <<<EOT
Analyze this invoice image and extract all information into the following JSON structure.
Be precise with numbers and dates. If a field is not visible or not applicable, use null.{$multiPageInstructions}

Return ONLY valid JSON (no markdown, no explanation), following this exact structure:

{
  "invoice": {
    "supplier": "string - Company/supplier name at the top",
    "document_type": "string - Type of document (Facture, Invoice, etc.)",
    "invoice_number": "string - Invoice/document number",
    "date": "string - Date in YYYY-MM-DD format",
    "print_time": "string - Print time if visible (HH:MM:SS)",
    "operator": "string - Operator code if visible"
  },
  "client": {
    "name": "string - Client name",
    "code": "string - Client code/ID",
    "reference": "string - Client reference if different from name"
  },
  "items": [
    {
      "reference": "string - Product reference/code",
      "designation": "string - Product description",
      "quantity": "number - Quantity ordered",
      "unit_price_ttc": "number - Unit price including tax",
      "total_ttc": "number - Total price including tax",
      "depot": "string - Depot/warehouse name if visible"
    }
  ],
  "taxes": [
    {
      "code": "string - Tax code",
      "base": "number - Tax base amount",
      "rate": "number - Tax rate percentage (just the number, e.g., 9 for 9%)",
      "tax_amount": "number - Tax amount"
    }
  ],
  "totals": {
    "total_ht": "number - Total excluding tax",
    "total_tax": "number - Total tax amount",
    "total_ttc": "number - Total including tax",
    "port_ht": "number - Shipping cost excluding tax (0 if none)",
    "net_to_pay": "number - Net amount to pay",
    "net_to_pay_words": "string - Amount in words if visible"
  },
  "logistics": {
    "packages_count": "number - Number of packages/colis",
    "total_weight": "number - Total weight in kg"
  }
}

Important:
- All monetary values should be numbers without currency symbols or spaces
- Dates must be in YYYY-MM-DD format
- Extract ALL line items visible in the invoice
- For tax rates, use the number only (e.g., 18 not "18%")
EOT;
    }

    /**
     * Extract JSON from Claude's response
     */
    private function extractJsonFromResponse(string $response): ?array
    {
        // Try to parse the response directly as JSON
        $decoded = json_decode($response, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Try to find JSON object in the response
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Compress image if it exceeds Claude's base64 size limit
     *
     * @param string $imageContent Raw image content
     * @param string $imagePath Original image path (for extension detection)
     * @param string $disk Storage disk
     * @return array Contains 'base64', 'mime_type', and 'was_compressed'
     */
    private function compressImageIfNeeded(string $imageContent, string $imagePath, string $disk): array
    {
        $base64 = base64_encode($imageContent);
        $originalMimeType = $this->getMimeType($imagePath);

        // If already under limit, return as-is
        if (strlen($base64) <= self::MAX_BASE64_SIZE) {
            return [
                'base64' => $base64,
                'mime_type' => $originalMimeType,
                'was_compressed' => false,
            ];
        }

        Log::info('Image exceeds size limit, compressing', [
            'original_base64_size' => strlen($base64),
            'max_allowed' => self::MAX_BASE64_SIZE,
        ]);

        // Load image with Intervention Image (v2 syntax)
        $image = Image::make($imageContent);

        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Calculate target dimensions (reduce by percentage until under limit)
        $quality = 85;
        $scale = 1.0;
        $attempts = 0;
        $maxAttempts = 5;
        $compressedBase64 = null;

        while ($attempts < $maxAttempts) {
            $attempts++;

            // Calculate new dimensions
            $newWidth = (int) ($originalWidth * $scale);
            $newHeight = (int) ($originalHeight * $scale);

            // Resize image (v2 syntax)
            $resizedImage = Image::make($imageContent);
            if ($scale < 1.0) {
                $resizedImage->resize($newWidth, $newHeight);
            }

            // Encode as JPEG (better compression than PNG) - v2 syntax
            $compressedContent = (string) $resizedImage->encode('jpg', $quality);
            $compressedBase64 = base64_encode($compressedContent);

            Log::info('Compression attempt', [
                'attempt' => $attempts,
                'scale' => $scale,
                'quality' => $quality,
                'dimensions' => "{$newWidth}x{$newHeight}",
                'base64_size' => strlen($compressedBase64),
            ]);

            // Check if we're under the target size
            if (strlen($compressedBase64) <= self::TARGET_BASE64_SIZE) {
                Log::info('Image compression successful', [
                    'original_base64_size' => strlen($base64),
                    'compressed_base64_size' => strlen($compressedBase64),
                    'reduction_percent' => round((1 - strlen($compressedBase64) / strlen($base64)) * 100, 1),
                ]);

                return [
                    'base64' => $compressedBase64,
                    'mime_type' => 'image/jpeg',
                    'was_compressed' => true,
                ];
            }

            // Reduce scale and quality for next attempt
            $scale *= 0.75;
            $quality = max(60, $quality - 10);
        }

        // If we still can't get under the limit, return the last attempt
        Log::warning('Image compression reached max attempts', [
            'final_base64_size' => strlen($compressedBase64 ?? $base64),
        ]);

        return [
            'base64' => $compressedBase64 ?? $base64,
            'mime_type' => 'image/jpeg',
            'was_compressed' => true,
        ];
    }

    /**
     * Get MIME type from file path
     */
    private function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Check if the title indicates this is an invoice photo
     */
    public function isInvoicePhoto(string $title): bool
    {
        $title = strtolower($title);
        $keywords = ['facture', 'invoice', 'bon de commande', 'bon de livraison', 'reçu', 'receipt'];

        foreach ($keywords as $keyword) {
            if (str_contains($title, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
