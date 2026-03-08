<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

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
    Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);

    // Orders (customer)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::patch('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel']);
});

// Vendor routes (auth:sanctum + role:vendor|admin)
Route::prefix('vendor')->middleware(['auth:sanctum', 'role:vendor|admin'])->group(function () {
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
    Route::get('orders/{orderNumber}', [OrderController::class, 'adminShow']);
    Route::patch('orders/{orderNumber}/status', [OrderController::class, 'updateStatus']);
});

