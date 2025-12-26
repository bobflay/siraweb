<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List all active products
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Product::with('productCategory')
            ->where('is_active', true)
            ->orderBy('name', 'asc');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('product_category_id', $request->category_id);
        }

        // Search by name or SKU
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku_global', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'status' => true,
            'data' => $products
        ], 200);
    }

    /**
     * Get a single product
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $product = Product::with('productCategory')
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $product
        ], 200);
    }

    /**
     * List all active product categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(Request $request)
    {
        $query = ProductCategory::with('parent')
            ->where('is_active', true)
            ->orderBy('name', 'asc');

        // Filter top-level categories only
        if ($request->has('top_level') && $request->top_level) {
            $query->whereNull('parent_id');
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $categories = $query->get();

        return response()->json([
            'status' => true,
            'data' => $categories
        ], 200);
    }
}
