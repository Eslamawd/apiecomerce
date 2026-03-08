<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getOrCreateCart(Request $request): Cart
    {
        return Cart::firstOrCreate(['user_id' => $request->user()->id]);
    }

    private function loadCart(Cart $cart): Cart
    {
        return $cart->load(['items.product.primaryImage']);
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        return response()->json(new CartResource($this->loadCart($cart)));
    }

    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->is_active) {
            return response()->json(['message' => 'Product is not available.'], 422);
        }

        if ($product->quantity < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock.'], 422);
        }

        $cart = $this->getOrCreateCart($request);

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $newQty = $item->quantity + $request->quantity;

            if ($product->quantity < $newQty) {
                return response()->json(['message' => 'Insufficient stock.'], 422);
            }

            $item->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity'   => $request->quantity,
            ]);
        }

        return response()->json(new CartResource($this->loadCart($cart->fresh())), 201);
    }

    public function updateItem(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->where('product_id', $productId)->firstOrFail();

        $product = $item->product;

        if ($product->quantity < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock.'], 422);
        }

        $item->update(['quantity' => $request->quantity]);

        return response()->json(new CartResource($this->loadCart($cart->fresh())));
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->where('product_id', $productId)->firstOrFail();
        $item->delete();

        return response()->json(new CartResource($this->loadCart($cart->fresh())));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json(new CartResource($this->loadCart($cart->fresh())));
    }
}
