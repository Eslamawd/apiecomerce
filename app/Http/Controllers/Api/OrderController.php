<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        foreach ($cart->items as $item) {
            if (! $item->product || ! $item->product->is_active) {
                return response()->json(['message' => "Product '{$item->product?->name}' is not available."], 422);
            }

            if ($item->product->quantity < $item->quantity) {
                return response()->json(['message' => "Insufficient stock for '{$item->product->name}'."], 422);
            }
        }

        $subtotal = $cart->items->sum(fn($item) => $item->product->price * $item->quantity);

        $discount = 0;
        $coupon = null;

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();

            if (! $coupon || ! $coupon->isValid()) {
                return response()->json(['message' => 'Invalid or expired coupon.'], 422);
            }

            $discount = $coupon->calculateDiscount($subtotal);
        }

        $shippingCost = 0;
        $total = $subtotal - $discount + $shippingCost;

        $order = DB::transaction(function () use ($request, $user, $cart, $subtotal, $discount, $shippingCost, $total, $coupon) {
            $order = Order::create([
                'user_id'            => $user->id,
                'status'             => 'pending',
                'subtotal'           => $subtotal,
                'discount'           => $discount,
                'shipping_cost'      => $shippingCost,
                'total'              => $total,
                'coupon_id'          => $coupon?->id,
                'payment_method'     => $request->payment_method,
                'payment_status'     => 'pending',
                'notes'              => $request->notes,
                'shipping_name'      => $request->shipping_name,
                'shipping_phone'     => $request->shipping_phone,
                'shipping_email'     => $request->shipping_email,
                'shipping_address'   => $request->shipping_address,
                'shipping_city'      => $request->shipping_city,
                'shipping_latitude'  => $request->shipping_latitude,
                'shipping_longitude' => $request->shipping_longitude,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'       => $order->id,
                    'product_id'     => $item->product_id,
                    'product_name'   => $item->product->name,
                    'product_name_en'=> $item->product->name_en,
                    'product_price'  => $item->product->price,
                    'quantity'       => $item->quantity,
                    'subtotal'       => $item->product->price * $item->quantity,
                ]);

                $item->product->decrement('quantity', $item->quantity);
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            $cart->items()->delete();

            return $order;
        });

        return response()->json(
            new OrderResource($order->load(['items', 'coupon', 'user'])),
            201
        );
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items', 'coupon'])
            ->latest()
            ->paginate(15);

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with(['items', 'coupon'])
            ->firstOrFail();

        return response()->json(new OrderResource($order));
    }

    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can be cancelled.'], 422);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json(new OrderResource($order->load(['items', 'coupon'])));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'items', 'coupon']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $sortBy = in_array($request->sort_by, ['created_at', 'total', 'status']) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->get('per_page', 15), 100);
        $orders = $query->paginate($perPage);

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }

    public function adminShow(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items', 'coupon', 'user'])
            ->firstOrFail();

        return response()->json(new OrderResource($order));
    }

    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $request->validate([
            'status'         => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
        ]);

        $data = ['status' => $request->status];

        if ($request->filled('payment_status')) {
            $data['payment_status'] = $request->payment_status;
        }

        $order->update($data);

        return response()->json(new OrderResource($order->load(['items', 'coupon', 'user'])));
    }

    public function vendorOrders(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;

        $orders = Order::whereHas('items.product', fn($q) => $q->where('vendor_id', $vendorId))
            ->with([
                'items' => fn($q) => $q->whereHas('product', fn($p) => $p->where('vendor_id', $vendorId)),
                'items.product',
                'user',
            ])
            ->latest()
            ->paginate(15);

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }
}
