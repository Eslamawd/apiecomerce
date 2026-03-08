<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Admin routes (auth:sanctum + role:admin)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
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
});
