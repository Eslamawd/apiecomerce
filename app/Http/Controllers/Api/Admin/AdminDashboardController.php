<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorPayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $thisMonthRevenue = Order::whereIn('status', ['delivered', 'processing', 'shipped', 'confirmed'])
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('total');

        $lastMonthRevenue = Order::whereIn('status', ['delivered', 'processing', 'shipped', 'confirmed'])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
            : ($thisMonthRevenue > 0 ? 100 : 0);

        $pendingPayouts = VendorPayout::pending()->count();
        $totalPayoutsAmount = VendorPayout::where('status', 'paid')->sum('amount');

        return response()->json([
            'stats' => [
                'total_users'               => User::count(),
                'new_users_this_month'      => User::where('created_at', '>=', $startOfMonth)->count(),
                'total_vendors'             => User::role('vendor')->count(),
                'pending_vendors'           => User::role('vendor')->where('is_active', false)->count(),
                'total_products'            => Product::withTrashed()->count(),
                'active_products'           => Product::where('is_active', true)->count(),
                'total_orders'              => Order::count(),
                'pending_orders'            => Order::where('status', 'pending')->count(),
                'processing_orders'         => Order::where('status', 'processing')->count(),
                'delivered_orders'          => Order::where('status', 'delivered')->count(),
                'cancelled_orders'          => Order::where('status', 'cancelled')->count(),
                'total_revenue'             => (float) Order::where('payment_status', 'paid')->sum('total'),
                'this_month_revenue'        => (float) $thisMonthRevenue,
                'last_month_revenue'        => (float) $lastMonthRevenue,
                'revenue_growth_percentage' => $revenueGrowth,
                'total_reviews'             => Review::count(),
                'average_platform_rating'   => (float) round((float) Review::approved()->avg('rating'), 2),
                'total_coupons_active'      => Coupon::where('is_active', true)->count(),
                'pending_payouts'           => $pendingPayouts,
                'total_payouts_amount'      => (float) $totalPayoutsAmount,
            ],
        ]);
    }

    public function revenueChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly');

        $data = match ($period) {
            'daily'   => $this->getRevenueByPeriod('day', 30),
            'weekly'  => $this->getRevenueByPeriod('week', 12),
            'yearly'  => $this->getRevenueByPeriod('year', 5),
            default   => $this->getRevenueByPeriod('month', 12),
        };

        return response()->json(['data' => $data, 'period' => $period]);
    }

    private function getRevenueByPeriod(string $groupBy, int $limit): array
    {
        $format = match ($groupBy) {
            'day'   => '%Y-%m-%d',
            'week'  => '%Y-%u',
            'year'  => '%Y',
            default => '%Y-%m',
        };

        return Order::where('payment_status', 'paid')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as date"),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'date'         => $row->date,
                'revenue'      => (float) $row->revenue,
                'orders_count' => (int) $row->orders_count,
            ])
            ->toArray();
    }

    public function ordersChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly');
        $days = (int) $request->get('days', 30);

        $format = match ($period) {
            'daily'  => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            default  => '%Y-%m',
        };

        $data = Order::select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as date"),
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date', 'status')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date')
            ->map(fn ($rows, $date) => [
                'date'    => $date,
                'statuses' => $rows->pluck('count', 'status')->toArray(),
            ])
            ->values()
            ->toArray();

        return response()->json(['data' => $data, 'period' => $period, 'days' => $days]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);

        $products = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.name_en',
                'products.price',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.name', 'products.name_en', 'products.price')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $products]);
    }

    public function topVendors(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);

        $vendors = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('users', 'products.vendor_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders'),
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $vendors]);
    }

    public function topCustomers(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);

        $customers = User::select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total) as total_spent')
            )
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_orders')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $customers]);
    }

    public function recentOrders(): JsonResponse
    {
        $orders = Order::with(['user', 'items'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($order) => [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'status'       => $order->status,
                'total'        => $order->total,
                'payment_status' => $order->payment_status,
                'user'         => [
                    'id'   => $order->user?->id,
                    'name' => $order->user?->name,
                ],
                'items_count'  => $order->items->count(),
                'created_at'   => $order->created_at,
            ]);

        return response()->json(['data' => $orders]);
    }

    public function recentReviews(): JsonResponse
    {
        $reviews = Review::with(['user', 'product'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($review) => [
                'id'          => $review->id,
                'rating'      => $review->rating,
                'comment'     => $review->comment,
                'is_approved' => $review->is_approved,
                'user'        => [
                    'id'   => $review->user?->id,
                    'name' => $review->user?->name,
                ],
                'product'     => [
                    'id'   => $review->product?->id,
                    'name' => $review->product?->name,
                ],
                'created_at'  => $review->created_at,
            ]);

        return response()->json(['data' => $reviews]);
    }

    public function lowStockProducts(Request $request): JsonResponse
    {
        $threshold = (int) $request->get('threshold', 10);

        $products = Product::with(['category', 'vendor'])
            ->where('quantity', '<', $threshold)
            ->where('is_active', true)
            ->orderBy('quantity', 'asc')
            ->get()
            ->map(fn ($product) => [
                'id'       => $product->id,
                'name'     => $product->name,
                'name_en'  => $product->name_en,
                'sku'      => $product->sku,
                'quantity' => $product->quantity,
                'category' => $product->category?->name ?? null,
                'vendor'   => $product->vendor?->name ?? null,
            ]);

        return response()->json(['data' => $products, 'threshold' => $threshold]);
    }
}
