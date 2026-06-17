<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryCategoryController;
use App\Http\Controllers\Api\InventoryClientController;
use App\Http\Controllers\Api\InventoryDashboardController;
use App\Http\Controllers\Api\InventoryDebtController;
use App\Http\Controllers\Api\InventorySaleController;
use App\Http\Controllers\Api\RetailClientController;
use App\Http\Controllers\Api\RetailDashboardController;
use App\Http\Controllers\Api\RetailDebtController;
use App\Http\Controllers\Api\RetailExpenseController;
use App\Http\Controllers\Api\RetailSaleController;
use Illuminate\Support\Facades\Route;

// ─── Public Auth Routes ───────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ─── Protected Routes ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // ─── Retail Store Routes (gated by EnsureStoreAccess) ────────────────────
    Route::middleware('store.access')->prefix('stores/{store}')->group(function () {

        // Dashboard
        Route::get('/dashboard', [RetailDashboardController::class, 'index']);

        // Sales
        Route::get('/sales', [RetailSaleController::class, 'index']);
        Route::post('/sales', [RetailSaleController::class, 'store']);
        Route::get('/sales/{sale}', [RetailSaleController::class, 'show']);

        // Debts
        Route::get('/debts', [RetailDebtController::class, 'index']);
        Route::post('/debts/{debt}/payments', [RetailDebtController::class, 'addPayment']);
        Route::get('/debts/{debt}/payments', [RetailDebtController::class, 'paymentHistory']);

        // Clients
        Route::get('/clients', [RetailClientController::class, 'index']);
        Route::post('/clients', [RetailClientController::class, 'store']);
        Route::get('/clients/{client}', [RetailClientController::class, 'show']);
        Route::put('/clients/{client}', [RetailClientController::class, 'update']);

        // Expenses
        Route::get('/expenses', [RetailExpenseController::class, 'index']);
        Route::post('/expenses', [RetailExpenseController::class, 'store']);
    });

    // Expense categories list (no store scope needed)
    Route::get('/expense-categories', [RetailExpenseController::class, 'categories']);

    // ─── Inventory Routes (gated by EnsureInventoryAccess) ───────────────────
    Route::middleware('inventory.access')->prefix('inventory')->group(function () {

        Route::get('/dashboard', [InventoryDashboardController::class, 'index']);

        // Clients
        Route::get('/clients', [InventoryClientController::class, 'index']);
        Route::post('/clients', [InventoryClientController::class, 'store']);
        Route::get('/clients/{client}', [InventoryClientController::class, 'show']);
        Route::put('/clients/{client}', [InventoryClientController::class, 'update']);

        // Sales
        Route::get('/sales', [InventorySaleController::class, 'index']);
        Route::post('/sales', [InventorySaleController::class, 'store']);

        // Debts
        Route::get('/debts', [InventoryDebtController::class, 'index']);
        Route::post('/debts/{debt}/payments', [InventoryDebtController::class, 'addPayment']);
        Route::get('/debts/{debt}/payments', [InventoryDebtController::class, 'paymentHistory']);

        // Categories & Product Types
        Route::get('/categories', [InventoryCategoryController::class, 'index']);
        Route::post('/categories', [InventoryCategoryController::class, 'storeCategory']);
        Route::get('/categories/{category}/types', [InventoryCategoryController::class, 'types']);
        Route::post('/categories/{category}/types', [InventoryCategoryController::class, 'storeType']);
    });
});
