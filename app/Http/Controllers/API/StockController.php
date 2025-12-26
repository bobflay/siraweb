<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get current stock for the authenticated agent
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $stock = $this->stockService->getAgentStock($user);

        return response()->json([
            'status' => true,
            'data' => $stock->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? null,
                'product_sku' => $item->product->sku_global ?? null,
                'price' => $item->product->price ?? null,
                'quantity' => $item->quantity,
                'updated_at' => $item->updated_at,
            ])
        ], 200);
    }

    /**
     * Get stock movements history for the authenticated agent
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function movements(Request $request)
    {
        $user = $request->user();
        $limit = $request->get('limit', 50);

        $movements = $this->stockService->getAgentMovements($user, $limit);

        return response()->json([
            'status' => true,
            'data' => $movements->map(fn($movement) => [
                'id' => $movement->id,
                'product_id' => $movement->product_id,
                'product_name' => $movement->product->name ?? null,
                'movement_type' => $movement->movement_type,
                'quantity' => $movement->quantity,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'notes' => $movement->notes,
                'created_at' => $movement->created_at,
            ])
        ], 200);
    }
}
