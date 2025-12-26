<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductCategorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * List all invoices for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $invoices = Invoice::with(['items', 'photos'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $invoices
        ], 200);
    }

    /**
     * Get a single invoice with items
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $invoice = Invoice::with(['items', 'photos'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $invoice
        ], 200);
    }

    /**
     * Create a new invoice with items
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Invoice info
            'supplier' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'print_time' => 'nullable|string|max:10',
            'operator' => 'nullable|string|max:255',

            // Client info
            'client_name' => 'nullable|string|max:255',
            'client_code' => 'nullable|string|max:255',
            'client_reference' => 'nullable|string|max:255',

            // Totals
            'total_ht' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'total_ttc' => 'nullable|numeric|min:0',
            'port_ht' => 'nullable|numeric|min:0',
            'net_to_pay' => 'nullable|numeric|min:0',
            'net_to_pay_words' => 'nullable|string|max:500',

            // Logistics
            'packages_count' => 'nullable|integer|min:0',
            'total_weight' => 'nullable|numeric|min:0',

            // Taxes (JSON array)
            'taxes' => 'nullable|array',
            'taxes.*.code' => 'nullable|string',
            'taxes.*.base' => 'nullable|numeric',
            'taxes.*.rate' => 'nullable|numeric',
            'taxes.*.tax_amount' => 'nullable|numeric',

            // Photo IDs to attach (optional)
            'photo_ids' => 'nullable|array',
            'photo_ids.*' => 'integer|exists:visit_photos,id',

            // Items (required array)
            'items' => 'required|array|min:1',
            'items.*.reference' => 'nullable|string|max:255',
            'items.*.designation' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price_ttc' => 'required|numeric|min:0',
            'items.*.total_ttc' => 'nullable|numeric|min:0',
            'items.*.depot' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();

            // Get AI categorization for all products in one call
            $categorizationService = new ProductCategorizationService();
            $categoryMappings = $categorizationService->categorizeInvoiceItems($request->items);

            // Create invoice
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'supplier' => $request->supplier,
                'document_type' => $request->document_type,
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'print_time' => $request->print_time,
                'operator' => $request->operator,
                'client_name' => $request->client_name,
                'client_code' => $request->client_code,
                'client_reference' => $request->client_reference,
                'total_ht' => $request->total_ht,
                'total_tax' => $request->total_tax,
                'total_ttc' => $request->total_ttc,
                'port_ht' => $request->port_ht,
                'net_to_pay' => $request->net_to_pay,
                'net_to_pay_words' => $request->net_to_pay_words,
                'packages_count' => $request->packages_count,
                'total_weight' => $request->total_weight,
                'taxes' => $request->taxes,
            ]);

            // Attach photos to invoice if provided
            \Log::info('Invoice creation - checking photo_ids', [
                'has_photo_ids' => $request->has('photo_ids'),
                'photo_ids' => $request->photo_ids,
            ]);

            if ($request->has('photo_ids') && !empty($request->photo_ids)) {
                \App\Models\VisitPhoto::whereIn('id', $request->photo_ids)
                    ->update([
                        'photoable_type' => Invoice::class,
                        'photoable_id' => $invoice->id,
                    ]);
            }

            // Create invoice items and link/create products
            foreach ($request->items as $item) {
                $reference = $item['reference'] ?? null;
                $categoryId = $reference ? ($categoryMappings[$reference] ?? null) : null;

                $productId = $this->findOrCreateProduct($item, $categoryId);

                $invoice->items()->create([
                    'product_id' => $productId,
                    'reference' => $reference,
                    'designation' => $item['designation'],
                    'quantity' => $item['quantity'],
                    'unit_price_ttc' => $item['unit_price_ttc'],
                    'total_ttc' => $item['total_ttc'] ?? ($item['quantity'] * $item['unit_price_ttc']),
                    'depot' => $item['depot'] ?? null,
                ]);
            }

            DB::commit();

            // Load items and photos for response
            $invoice->load(['items', 'photos']);

            // Add stock from invoice items (products loaded in vehicle)
            $stockService = new \App\Services\StockService();
            $stockService->addFromInvoice($invoice);

            \Log::info('Invoice created manually', [
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'items_count' => $invoice->items->count(),
                'photos_count' => $invoice->photos->count(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Invoice created successfully',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'supplier' => $invoice->supplier,
                    'client_name' => $invoice->client_name,
                    'total_ttc' => $invoice->total_ttc,
                    'items_count' => $invoice->items->count(),
                    'photos_count' => $invoice->photos->count(),
                    'items' => $invoice->items->map(fn($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'reference' => $item->reference,
                        'designation' => $item->designation,
                        'quantity' => $item->quantity,
                        'unit_price_ttc' => $item->unit_price_ttc,
                        'total_ttc' => $item->total_ttc,
                        'depot' => $item->depot,
                    ]),
                    'photos' => $invoice->photos->map(fn($photo) => [
                        'id' => $photo->id,
                        'file_path' => $photo->file_path,
                        'file_name' => $photo->file_name,
                    ]),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create invoice', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing invoice with items
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $invoice = Invoice::where('user_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            // Invoice info
            'supplier' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'print_time' => 'nullable|string|max:10',
            'operator' => 'nullable|string|max:255',

            // Client info
            'client_name' => 'nullable|string|max:255',
            'client_code' => 'nullable|string|max:255',
            'client_reference' => 'nullable|string|max:255',

            // Totals
            'total_ht' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'total_ttc' => 'nullable|numeric|min:0',
            'port_ht' => 'nullable|numeric|min:0',
            'net_to_pay' => 'nullable|numeric|min:0',
            'net_to_pay_words' => 'nullable|string|max:500',

            // Logistics
            'packages_count' => 'nullable|integer|min:0',
            'total_weight' => 'nullable|numeric|min:0',

            // Taxes (JSON array)
            'taxes' => 'nullable|array',

            // Items (optional on update)
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|integer|exists:invoice_items,id',
            'items.*.reference' => 'nullable|string|max:255',
            'items.*.designation' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price_ttc' => 'required|numeric|min:0',
            'items.*.total_ttc' => 'nullable|numeric|min:0',
            'items.*.depot' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update invoice fields
            $invoice->update([
                'supplier' => $request->supplier ?? $invoice->supplier,
                'document_type' => $request->document_type ?? $invoice->document_type,
                'invoice_number' => $request->invoice_number ?? $invoice->invoice_number,
                'invoice_date' => $request->invoice_date ?? $invoice->invoice_date,
                'print_time' => $request->print_time ?? $invoice->print_time,
                'operator' => $request->operator ?? $invoice->operator,
                'client_name' => $request->client_name ?? $invoice->client_name,
                'client_code' => $request->client_code ?? $invoice->client_code,
                'client_reference' => $request->client_reference ?? $invoice->client_reference,
                'total_ht' => $request->total_ht ?? $invoice->total_ht,
                'total_tax' => $request->total_tax ?? $invoice->total_tax,
                'total_ttc' => $request->total_ttc ?? $invoice->total_ttc,
                'port_ht' => $request->port_ht ?? $invoice->port_ht,
                'net_to_pay' => $request->net_to_pay ?? $invoice->net_to_pay,
                'net_to_pay_words' => $request->net_to_pay_words ?? $invoice->net_to_pay_words,
                'packages_count' => $request->packages_count ?? $invoice->packages_count,
                'total_weight' => $request->total_weight ?? $invoice->total_weight,
                'taxes' => $request->has('taxes') ? $request->taxes : $invoice->taxes,
            ]);

            // Update items if provided
            if ($request->has('items')) {
                // Get AI categorization for all products in one call
                $categorizationService = new ProductCategorizationService();
                $categoryMappings = $categorizationService->categorizeInvoiceItems($request->items);

                // Delete existing items and recreate
                $invoice->items()->delete();

                foreach ($request->items as $item) {
                    $reference = $item['reference'] ?? null;
                    $categoryId = $reference ? ($categoryMappings[$reference] ?? null) : null;

                    $productId = $this->findOrCreateProduct($item, $categoryId);

                    $invoice->items()->create([
                        'product_id' => $productId,
                        'reference' => $reference,
                        'designation' => $item['designation'],
                        'quantity' => $item['quantity'],
                        'unit_price_ttc' => $item['unit_price_ttc'],
                        'total_ttc' => $item['total_ttc'] ?? ($item['quantity'] * $item['unit_price_ttc']),
                        'depot' => $item['depot'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $invoice->load('items');

            return response()->json([
                'status' => true,
                'message' => 'Invoice updated successfully',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'supplier' => $invoice->supplier,
                    'client_name' => $invoice->client_name,
                    'total_ttc' => $invoice->total_ttc,
                    'items_count' => $invoice->items->count(),
                    'items' => $invoice->items->map(fn($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'reference' => $item->reference,
                        'designation' => $item->designation,
                        'quantity' => $item->quantity,
                        'unit_price_ttc' => $item->unit_price_ttc,
                        'total_ttc' => $item->total_ttc,
                        'depot' => $item->depot,
                    ]),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an invoice
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $invoice = Invoice::where('user_id', $user->id)->findOrFail($id);

        try {
            $invoice->delete(); // Items will be cascade deleted

            return response()->json([
                'status' => true,
                'message' => 'Invoice deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Failed to delete invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark an invoice as delivered and deduct stock
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliver(Request $request, $id)
    {
        $user = $request->user();

        $invoice = Invoice::with('items')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        // Check if invoice can be delivered
        if ($invoice->isDelivered()) {
            return response()->json([
                'status' => false,
                'message' => 'Invoice is already delivered'
            ], 422);
        }

        if ($invoice->isCancelled()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot deliver a cancelled invoice'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update invoice status to delivered
            $invoice->update([
                'status' => Invoice::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            DB::commit();

            // Deduct stock after delivery
            $stockService = new \App\Services\StockService();
            $stockService->deductFromInvoice($invoice);

            // Reload for response
            $invoice->load('items');

            \Log::info('Invoice delivered', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'user_id' => $user->id,
                'items_count' => $invoice->items->count(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Invoice marked as delivered',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'delivered_at' => $invoice->delivered_at,
                    'items_count' => $invoice->items->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to deliver invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to deliver invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find or create a product based on invoice item data
     *
     * @param array $item Invoice item data with reference and designation
     * @param int|null $categoryId Category ID from AI categorization
     * @return int|null Product ID or null if no reference
     */
    private function findOrCreateProduct(array $item, ?int $categoryId = null): ?int
    {
        $reference = $item['reference'] ?? null;
        $designation = $item['designation'] ?? null;
        $unitPrice = $item['unit_price_ttc'] ?? null;

        // If no reference, we can't match/create a product
        if (empty($reference)) {
            return null;
        }

        // Try to find existing product by SKU (reference)
        $product = Product::where('sku_global', $reference)->first();

        if ($product) {
            $updates = [];

            // Update category if AI provided one and product has default category
            if ($categoryId !== null) {
                $defaultCategory = ProductCategory::where('code', 'OCR_IMPORT')->first();
                if ($defaultCategory && $product->product_category_id === $defaultCategory->id) {
                    $updates['product_category_id'] = $categoryId;
                    \Log::info('Updated product category from AI', [
                        'product_id' => $product->id,
                        'old_category_id' => $defaultCategory->id,
                        'new_category_id' => $categoryId,
                    ]);
                }
            }

            // Update price if provided (always update to latest price from invoice)
            if ($unitPrice !== null) {
                $updates['price'] = $unitPrice;
                $updates['price_updated_at'] = now();
            }

            if (!empty($updates)) {
                $product->update($updates);
            }

            return $product->id;
        }

        // Determine category ID for new product
        if ($categoryId === null) {
            // Fall back to default OCR_IMPORT category if AI didn't provide one
            $defaultCategory = ProductCategory::firstOrCreate(
                ['code' => 'OCR_IMPORT'],
                [
                    'name' => 'Produits importés (OCR)',
                    'is_active' => true,
                ]
            );
            $categoryId = $defaultCategory->id;
        }

        // Create new product with AI-assigned or default category
        $product = Product::create([
            'sku_global' => $reference,
            'name' => $designation ?? $reference,
            'product_category_id' => $categoryId,
            'unit' => 'unité',
            'price' => $unitPrice,
            'price_updated_at' => $unitPrice ? now() : null,
            'is_active' => true,
        ]);

        \Log::info('Created new product from invoice OCR', [
            'product_id' => $product->id,
            'sku' => $reference,
            'name' => $product->name,
            'category_id' => $categoryId,
            'price' => $unitPrice,
        ]);

        return $product->id;
    }
}
