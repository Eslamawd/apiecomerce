<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->with(['product.primaryImage', 'product.category'])
            ->get();

        $products = $wishlists->map(fn($w) => new ProductResource($w->product));

        return response()->json(['data' => $products]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = $request->user()->id;
        $productId = $request->product_id;

        $existing = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['message' => 'Product removed from wishlist.', 'in_wishlist' => false]);
        }

        Wishlist::create(['user_id' => $userId, 'product_id' => $productId]);

        return response()->json(['message' => 'Product added to wishlist.', 'in_wishlist' => true], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->firstOrFail();

        $wishlist->delete();

        return response()->json(['message' => 'Product removed from wishlist.']);
    }
}
