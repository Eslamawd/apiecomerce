<?php

use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');
Route::post('/auth/email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:auth');

// Auth routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
Route::get('/search', [SearchController::class, 'index']);
Route::get('/search/suggestions', [SearchController::class, 'suggestions']);

// Payment webhook (no auth — called by gateway)
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->middleware('throttle:sensitive');

// Authenticated routes (any logged-in user)
Route::middleware('auth:sanctum')->group(function () {
    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{productId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{productId}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'toggle']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);

    // Reviews
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Coupons (customer validate)
    Route::post('/coupons/validate', [CouponController::class, 'validateCoupon'])->middleware('throttle:sensitive');

    // Orders (customer)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::patch('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel']);

    // Payments
    Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->middleware('throttle:sensitive');
    Route::get('/payments/{orderNumber}/status', [PaymentController::class, 'status']);
    Route::post('/payments/{orderNumber}/refund', [PaymentController::class, 'refund'])->middleware('throttle:sensitive');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

// Vendor routes (auth:sanctum + role:vendor|admin)
Route::prefix('vendor')->middleware(['auth:sanctum', 'role:vendor|admin'])->group(function () {
    Route::get('products', [ProductController::class, 'vendorIndex']);
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::post('products/{id}/images', [ProductController::class, 'addImages']);
    Route::delete('products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
    Route::post('products/{id}/videos', [ProductController::class, 'addVideos']);
    Route::delete('products/{id}/videos/{videoId}', [ProductController::class, 'deleteVideo']);
    Route::patch('products/{id}/images/{imageId}/primary', [ProductController::class, 'setPrimaryImage']);
    Route::get('orders', [OrderController::class, 'vendorOrders']);
});

// Admin routes (auth:sanctum + role:admin)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Categories
    Route::get('categories', [CategoryController::class, 'adminIndex']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

    // Reviews
    Route::get('reviews', [ReviewController::class, 'adminIndex']);
    Route::patch('reviews/{id}/approve', [ReviewController::class, 'approve']);

    // Coupons
    Route::get('coupons', [CouponController::class, 'index']);
    Route::post('coupons', [CouponController::class, 'store']);
    Route::put('coupons/{id}', [CouponController::class, 'update']);
    Route::delete('coupons/{id}', [CouponController::class, 'destroy']);

    // Orders
    Route::get('orders', [OrderController::class, 'adminIndex']);
    Route::get('orders/statistics', [OrderController::class, 'statistics']);
    Route::get('orders/export', [OrderController::class, 'export']);
    Route::get('orders/{orderNumber}', [OrderController::class, 'adminShow']);
    Route::patch('orders/{orderNumber}/status', [OrderController::class, 'updateStatus']);

    // Dashboard
    Route::get('dashboard/overview', [AdminDashboardController::class, 'overview']);
    Route::get('dashboard/revenue-chart', [AdminDashboardController::class, 'revenueChart']);
    Route::get('dashboard/orders-chart', [AdminDashboardController::class, 'ordersChart']);
    Route::get('dashboard/top-products', [AdminDashboardController::class, 'topProducts']);
    Route::get('dashboard/top-vendors', [AdminDashboardController::class, 'topVendors']);
    Route::get('dashboard/top-customers', [AdminDashboardController::class, 'topCustomers']);
    Route::get('dashboard/recent-orders', [AdminDashboardController::class, 'recentOrders']);
    Route::get('dashboard/recent-reviews', [AdminDashboardController::class, 'recentReviews']);
    Route::get('dashboard/low-stock', [AdminDashboardController::class, 'lowStockProducts']);

    // Users
    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::put('users/{id}', [AdminUserController::class, 'update']);
    Route::patch('users/{id}/toggle-active', [AdminUserController::class, 'toggleActive']);
    Route::patch('users/{id}/role', [AdminUserController::class, 'changeRole']);
    Route::delete('users/{id}', [AdminUserController::class, 'destroy']);

    // Products (admin view)
    Route::get('products', [AdminProductController::class, 'index']);
    Route::get('products/{id}', [AdminProductController::class, 'show']);
    Route::patch('products/{id}/toggle-active', [AdminProductController::class, 'toggleActive']);
    Route::patch('products/{id}/toggle-featured', [AdminProductController::class, 'toggleFeatured']);
    Route::delete('products/{id}', [AdminProductController::class, 'destroy']);

    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index']);
    Route::put('settings', [AdminSettingsController::class, 'update']);
});

