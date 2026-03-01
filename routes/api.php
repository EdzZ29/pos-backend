<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\TimeLogController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/orders/queue/upcoming', [OrderController::class, 'publicQueue']);
Route::post('/orders/{order}/confirm-served', [OrderController::class, 'publicConfirmServed']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Owner only
    Route::middleware('role:owner')->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('users', UserController::class);
    });

    // All authenticated users can browse products, categories & payment methods
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('payment-methods', function () {
        return response()->json(\App\Models\PaymentMethod::where('is_active', true)->get());
    });

    // Owner + Manager can create / update / delete
    Route::middleware('role:owner,manager')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    });

    // All roles
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('payments', PaymentController::class);

    // Time logs (owner can manage all, others can view)
    Route::apiResource('time-logs', TimeLogController::class)->except(['destroy']);
    Route::post('time-logs/mark-absent', [TimeLogController::class, 'markAbsent']);
    Route::post('time-logs/qr-scan', [TimeLogController::class, 'qrScan']);
});


Route::middleware('auth:sanctum')->group(function () {
    // JSON receipt
    Route::get('/orders/{order}/receipt', [ReceiptController::class, 'show']);
    // Plain text receipt (for thermal printers)
    Route::get('/orders/{order}/receipt/text', [ReceiptController::class, 'text']);
});