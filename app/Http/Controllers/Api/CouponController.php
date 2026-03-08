<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code'        => 'required|string',
            'order_total' => 'nullable|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (! $coupon || ! $coupon->isValid()) {
            return response()->json(['message' => 'Invalid or expired coupon.'], 422);
        }

        $orderTotal = (float) ($request->order_total ?? 0);

        if ($coupon->min_order_amount && $orderTotal < $coupon->min_order_amount) {
            return response()->json([
                'message' => "Minimum order amount is {$coupon->min_order_amount} to use this coupon.",
            ], 422);
        }

        $discount = $coupon->calculateDiscount($orderTotal);

        return response()->json([
            'coupon'   => new CouponResource($coupon),
            'discount' => $discount,
        ]);
    }

    public function index(): JsonResponse
    {
        $coupons = Coupon::latest()->paginate(15);

        return response()->json(CouponResource::collection($coupons)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'             => 'required|string|unique:coupons,code',
            'type'             => 'required|in:fixed,percentage',
            'value'            => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'starts_at'        => 'nullable|date',
            'expires_at'       => 'nullable|date|after_or_equal:starts_at',
            'is_active'        => 'nullable|boolean',
        ]);

        $coupon = Coupon::create($data);

        return response()->json(new CouponResource($coupon), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $data = $request->validate([
            'code'             => 'sometimes|string|unique:coupons,code,' . $id,
            'type'             => 'sometimes|in:fixed,percentage',
            'value'            => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'starts_at'        => 'nullable|date',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'nullable|boolean',
        ]);

        $coupon->update($data);

        return response()->json(new CouponResource($coupon));
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted successfully.']);
    }
}
