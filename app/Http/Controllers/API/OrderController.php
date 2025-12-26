<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * List all orders for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::with(['client', 'orderItems.product', 'visit'])
            ->where('user_id', $user->id)
            ->orderBy('ordered_at', 'desc');

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('ordered_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('ordered_at', '<=', $request->to_date);
        }

        $orders = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $orders
        ], 200);
    }

    /**
     * Get a single order with items
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with(['client', 'orderItems.product', 'visit', 'baseCommerciale', 'zone'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $order
        ], 200);
    }

    /**
     * Create a new order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'base_commerciale_id' => 'required|exists:bases_commerciales,id',
            'zone_id' => 'nullable|exists:zones,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
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

            // Generate order reference
            $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Calculate total from items
            $totalAmount = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Use provided unit_price, or fall back to product price
                $unitPrice = $item['unit_price'] ?? $product->price ?? 0;
                $lineTotal = $unitPrice * $item['quantity'];
                $totalAmount += $lineTotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku_global,
                    'unit_snapshot' => $product->unit,
                    'packaging_snapshot' => $product->packaging,
                    'unit_price_snapshot' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            // Get zone_id from request or fall back to user's first zone
            $zoneId = $request->zone_id ?? $user->zones()->first()?->id;

            // Create order
            $order = Order::create([
                'reference' => $reference,
                'client_id' => $request->client_id,
                'user_id' => $user->id,
                'visit_id' => $request->visit_id,
                'base_commerciale_id' => $request->base_commerciale_id,
                'zone_id' => $zoneId,
                'total_amount' => $totalAmount,
                'currency' => 'XOF',
                'status' => 'draft',
                'ordered_at' => now(),
            ]);

            // Create order items
            foreach ($itemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            DB::commit();

            // Load relationships for response
            $order->load(['client', 'orderItems.product', 'visit', 'baseCommerciale', 'zone']);

            \Log::info('Order created', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'user_id' => $user->id,
                'client_id' => $order->client_id,
                'total_amount' => $order->total_amount,
                'items_count' => $order->orderItems->count(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create order', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
