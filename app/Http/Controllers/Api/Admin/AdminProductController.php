<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'primaryImage', 'vendor'])->withTrashed();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true)->whereNull('deleted_at');
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false)->whereNull('deleted_at');
            } elseif ($request->status === 'deleted') {
                $query->whereNotNull('deleted_at');
            }
        }

        $sortBy  = in_array($request->sort_by, ['price', 'name', 'created_at', 'quantity']) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage  = min((int) $request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'images', 'videos', 'primaryImage', 'vendor'])
            ->withTrashed()
            ->findOrFail($id);

        return response()->json(new ProductResource($product));
    }

    public function toggleActive(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->update(['is_active' => ! $product->is_active]);

        return response()->json([
            'message'   => 'Product status updated.',
            'is_active' => $product->is_active,
        ]);
    }

    public function toggleFeatured(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->update(['is_featured' => ! $product->is_featured]);

        return response()->json([
            'message'     => 'Product featured status updated.',
            'is_featured' => $product->is_featured,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
