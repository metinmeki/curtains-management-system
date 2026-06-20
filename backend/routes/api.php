<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryCategoryController;
use App\Http\Controllers\Api\RetailDashboardController;

// Auth Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Settings & Categories Routes
    Route::get('/settings/categories', [InventoryCategoryController::class, 'index']);
    Route::post('/settings/categories/main', [InventoryCategoryController::class, 'storeMainCategory']);
    Route::post('/settings/categories/sub', [InventoryCategoryController::class, 'storeSubCategory']);

    // Retail Store Routes
    Route::get('/retail/dashboard/{storeId}', [RetailDashboardController::class, 'index']);
});
