<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Initiate a payment for an order.
     * For non-COD orders, creates a pending payment record and returns a payment reference.
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'order_number' => 'required|string',
            'gateway'      => 'required|in:stripe,paypal,cod',
        ]);

        $order = Order::where('order_number', $request->order_number)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order is already paid.'], 422);
        }

        if ($request->gateway === 'cod') {
            $order->update(['payment_method' => 'cash_on_delivery']);

            return response()->json([
                'message'        => 'Cash on delivery selected.',
                'payment_status' => $order->payment_status,
            ]);
        }

        $transactionId = 'TXN-' . strtoupper(Str::random(12));

        $payment = Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_gateway'  => $request->gateway,
                'transaction_id'   => $transactionId,
                'amount'           => $order->total,
                'currency'         => 'USD',
                'status'           => 'pending',
                'gateway_response' => null,
            ]
        );

        $order->update(['payment_method' => 'online']);

        return response()->json([
            'message'        => 'Payment initiated.',
            'transaction_id' => $transactionId,
            'amount'         => $order->total,
            'currency'       => $payment->currency,
            'order_number'   => $order->order_number,
            'payment_url'    => url("/api/payments/{$order->order_number}/complete?txn={$transactionId}"),
        ], 201);
    }

    /**
     * Handle payment webhook from gateway (simulated).
     */
    public function webhook(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'status'         => 'required|in:completed,failed,refunded',
            'gateway'        => 'required|string',
        ]);

        $payment = Payment::where('transaction_id', $request->transaction_id)->firstOrFail();

        $payment->update([
            'status'           => $request->status,
            'gateway_response' => $request->all(),
        ]);

        $order = $payment->order;

        if ($request->status === 'completed') {
            $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);
        } elseif ($request->status === 'failed') {
            $order->update(['payment_status' => 'failed']);
        } elseif ($request->status === 'refunded') {
            $order->update(['payment_status' => 'refunded', 'status' => 'refunded']);
        }

        return response()->json(['message' => 'Webhook processed successfully.']);
    }

    /**
     * Get payment status for an order.
     */
    public function status(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $payment = Payment::where('order_id', $order->id)->first();

        return response()->json([
            'order_number'   => $order->order_number,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment'        => $payment ? [
                'transaction_id'  => $payment->transaction_id,
                'gateway'         => $payment->payment_gateway,
                'amount'          => $payment->amount,
                'currency'        => $payment->currency,
                'status'          => $payment->status,
                'created_at'      => $payment->created_at,
            ] : null,
        ]);
    }

    /**
     * Request a refund for a paid order.
     */
    public function refund(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->payment_status !== 'paid') {
            return response()->json(['message' => 'Only paid orders can be refunded.'], 422);
        }

        if (! in_array($order->status, ['delivered', 'cancelled'])) {
            return response()->json(['message' => 'Order must be delivered or cancelled to request a refund.'], 422);
        }

        $payment = Payment::where('order_id', $order->id)->first();

        if ($payment) {
            $payment->update([
                'status'           => 'refunded',
                'gateway_response' => array_merge(
                    $payment->gateway_response ?? [],
                    ['refund_requested_at' => now()->toDateTimeString()]
                ),
            ]);
        }

        $order->update(['payment_status' => 'refunded', 'status' => 'refunded']);

        return response()->json(['message' => 'Refund processed successfully.', 'order_number' => $order->order_number]);
    }
}
