<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $reviews = $product->reviews()
            ->approved()
            ->with('user')
            ->latest()
            ->paginate(15);

        return response()->json(ReviewResource::collection($reviews)->response()->getData(true));
    }

    public function store(StoreReviewRequest $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $user = $request->user();

        $existing = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'You have already reviewed this product.'], 422);
        }

        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
            'is_approved'=> true,
        ]);

        $review->load('user');

        return response()->json(new ReviewResource($review), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'rating'  => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($request->only('rating', 'comment'));
        $review->load('user');

        return response()->json(new ReviewResource($review));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $user = $request->user();

        if ($review->user_id !== $user->id && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }

    public function adminIndex(): JsonResponse
    {
        $reviews = Review::with(['user', 'product'])->latest()->paginate(15);

        return response()->json(ReviewResource::collection($reviews)->response()->getData(true));
    }

    public function approve(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        $review->update(['is_approved' => ! $review->is_approved]);
        $review->load('user');

        return response()->json(new ReviewResource($review));
    }
}
