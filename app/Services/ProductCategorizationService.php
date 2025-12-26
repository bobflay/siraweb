<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Log;
use Anthropic\Laravel\Facades\Anthropic;

class ProductCategorizationService
{
    /**
     * Categorize multiple products in a single AI call
     *
     * @param array $products Array of products with 'reference' and 'designation'
     * @return array Map of reference => category_id
     */
    public function categorizeProducts(array $products): array
    {
        if (empty($products)) {
            return [];
        }

        try {
            // Get all existing categories from database
            $existingCategories = ProductCategory::where('is_active', true)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'code' => $category->code,
                        'name' => $category->name,
                        'parent_id' => $category->parent_id,
                    ];
                })
                ->toArray();

            // Filter products that need categorization (exclude already categorized ones)
            $productsToCategize = array_filter($products, function ($product) {
                return !empty($product['reference']) || !empty($product['designation']);
            });

            if (empty($productsToCategize)) {
                return [];
            }

            // Build prompt for AI
            $prompt = $this->buildCategorizationPrompt($productsToCategize, $existingCategories);

            Log::info('Calling Claude API for product categorization', [
                'products_count' => count($productsToCategize),
                'categories_count' => count($existingCategories),
            ]);

            // Call Claude API
            $response = Anthropic::messages()->create([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $textContent = $response->content[0]->text ?? null;

            if (!$textContent) {
                Log::error('Product categorization: No response from Claude');
                return [];
            }

            // Parse the response
            $categorizations = $this->parseCategorizationResponse($textContent);

            Log::info('Product categorization completed', [
                'products_processed' => count($productsToCategize),
                'categorizations_received' => count($categorizations),
            ]);

            // Process categorizations and create missing categories
            return $this->processCategorizations($categorizations, $existingCategories);

        } catch (\Exception $e) {
            Log::error('Product categorization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Build the prompt for product categorization
     */
    private function buildCategorizationPrompt(array $products, array $existingCategories): string
    {
        $productsList = "";
        foreach ($products as $index => $product) {
            $ref = $product['reference'] ?? 'N/A';
            $designation = $product['designation'] ?? 'N/A';
            $productsList .= "- Product #{$index}: Reference: \"{$ref}\", Designation: \"{$designation}\"\n";
        }

        $categoriesList = "";
        if (!empty($existingCategories)) {
            foreach ($existingCategories as $cat) {
                $parent = $cat['parent_id'] ? " (parent_id: {$cat['parent_id']})" : "";
                $categoriesList .= "- ID: {$cat['id']}, Code: \"{$cat['code']}\", Name: \"{$cat['name']}\"{$parent}\n";
            }
        } else {
            $categoriesList = "No existing categories. You should suggest new ones.\n";
        }

        return <<<EOT
You are a product categorization assistant. Analyze the following products and assign each to the most appropriate category.

EXISTING CATEGORIES:
{$categoriesList}

PRODUCTS TO CATEGORIZE:
{$productsList}

INSTRUCTIONS:
1. For each product, analyze the reference and designation to determine the most appropriate category
2. If an existing category fits well, use its ID
3. If no existing category fits, suggest a new category with a code and name
4. Common product categories include: Boissons, Alimentaire, Hygiène, Entretien, Cosmétique, Épicerie, Produits laitiers, Conserves, etc.
5. Be consistent - similar products should be in the same category

Return ONLY a valid JSON array (no markdown, no explanation) with this structure:
[
  {
    "product_index": 0,
    "category_id": 5,
    "new_category": null
  },
  {
    "product_index": 1,
    "category_id": null,
    "new_category": {
      "code": "BOISSONS",
      "name": "Boissons",
      "parent_code": null
    }
  }
]

Rules:
- Use "category_id" if matching an existing category, set "new_category" to null
- Use "new_category" if suggesting a new category, set "category_id" to null
- "parent_code" in new_category can reference an existing category code if this should be a subcategory
- Category codes should be UPPERCASE with underscores (e.g., "PRODUITS_LAITIERS")
EOT;
    }

    /**
     * Parse the AI response for categorizations
     */
    private function parseCategorizationResponse(string $response): array
    {
        // Try to parse directly as JSON
        $decoded = json_decode($response, true);
        if ($decoded !== null && is_array($decoded)) {
            return $decoded;
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try to find JSON array in the response
        if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('Failed to parse categorization response', [
            'response' => $response,
        ]);

        return [];
    }

    /**
     * Process categorizations and create missing categories
     *
     * @return array Map of product_index => category_id
     */
    private function processCategorizations(array $categorizations, array $existingCategories): array
    {
        $result = [];
        $newCategoriesCache = []; // Cache for newly created categories by code

        // Build a map of existing category codes to IDs
        $codeToId = [];
        foreach ($existingCategories as $cat) {
            $codeToId[$cat['code']] = $cat['id'];
        }

        foreach ($categorizations as $categorization) {
            $productIndex = $categorization['product_index'] ?? null;

            if ($productIndex === null) {
                continue;
            }

            $categoryId = $categorization['category_id'] ?? null;

            // If we have an existing category ID, use it
            if ($categoryId !== null) {
                $result[$productIndex] = $categoryId;
                continue;
            }

            // If we need to create a new category
            $newCategory = $categorization['new_category'] ?? null;
            if ($newCategory && !empty($newCategory['code'])) {
                $code = $newCategory['code'];

                // Check if we already created this category in this batch
                if (isset($newCategoriesCache[$code])) {
                    $result[$productIndex] = $newCategoriesCache[$code];
                    continue;
                }

                // Check if category with this code already exists
                if (isset($codeToId[$code])) {
                    $result[$productIndex] = $codeToId[$code];
                    $newCategoriesCache[$code] = $codeToId[$code];
                    continue;
                }

                // Find parent ID if specified
                $parentId = null;
                if (!empty($newCategory['parent_code']) && isset($codeToId[$newCategory['parent_code']])) {
                    $parentId = $codeToId[$newCategory['parent_code']];
                }

                // Create the new category
                $createdCategory = ProductCategory::create([
                    'code' => $code,
                    'name' => $newCategory['name'] ?? $code,
                    'parent_id' => $parentId,
                    'is_active' => true,
                ]);

                Log::info('Created new product category from AI suggestion', [
                    'category_id' => $createdCategory->id,
                    'code' => $code,
                    'name' => $createdCategory->name,
                    'parent_id' => $parentId,
                ]);

                $result[$productIndex] = $createdCategory->id;
                $newCategoriesCache[$code] = $createdCategory->id;
                $codeToId[$code] = $createdCategory->id;
            }
        }

        return $result;
    }

    /**
     * Categorize products from invoice items and return category mappings
     *
     * @param array $items Array of invoice items with 'reference' and 'designation'
     * @return array Map of reference => category_id
     */
    public function categorizeInvoiceItems(array $items): array
    {
        // Convert items to the format expected by categorizeProducts
        $products = [];
        $referenceToIndex = [];

        foreach ($items as $index => $item) {
            $reference = $item['reference'] ?? null;
            if (empty($reference)) {
                continue;
            }

            // Skip if we already have this reference
            if (isset($referenceToIndex[$reference])) {
                continue;
            }

            $products[] = [
                'reference' => $reference,
                'designation' => $item['designation'] ?? null,
            ];
            $referenceToIndex[$reference] = count($products) - 1;
        }

        if (empty($products)) {
            return [];
        }

        // Get categorizations by index
        $indexToCategoryId = $this->categorizeProducts($products);

        // Convert back to reference => category_id mapping
        $result = [];
        foreach ($referenceToIndex as $reference => $index) {
            if (isset($indexToCategoryId[$index])) {
                $result[$reference] = $indexToCategoryId[$index];
            }
        }

        return $result;
    }
}
