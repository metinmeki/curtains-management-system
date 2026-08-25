<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryCategoryController;
use App\Http\Controllers\Api\RetailDashboardController;
use App\Http\Controllers\Api\RetailSaleController;
use App\Http\Controllers\Api\RetailExpenseController;
use App\Http\Controllers\Api\RetailClientController;
use App\Http\Controllers\Api\RetailOrderController;
use App\Http\Controllers\Api\WarehouseSupplyController;
use App\Http\Controllers\Api\WarehouseStockController;
use App\Http\Controllers\Api\WarehouseClientController;
use App\Http\Controllers\Api\WarehouseKvController;
use App\Http\Controllers\Api\ItemTypeController;
use App\Http\Controllers\Api\RetailKvController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\CashierController;

Route::post("/auth/login", [AuthController::class, "login"]);
Route::post("/auth/cashier-login", [AuthController::class, "cashierLogin"]);
Route::post("/auth/register", [AuthController::class, "register"]);
Route::post("/auth/logout", [AuthController::class, "logout"])->middleware("auth:sanctum");
Route::get("/auth/user", [AuthController::class, "user"])->middleware("auth:sanctum");

// Public � needed on login page before any token exists
Route::get("/stores",   [StoreController::class,   "index"]);
Route::get("/cashiers", [CashierController::class, "index"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/user", function (Request $request) { return $request->user(); });

    // Item types � global catalog with cost & selling prices
    Route::get("/item-types", [ItemTypeController::class, "index"]);
    Route::post("/item-types", [ItemTypeController::class, "store"]);
    Route::put("/item-types/{id}", [ItemTypeController::class, "update"]);
    Route::delete("/item-types/{id}", [ItemTypeController::class, "destroy"]);

    Route::get("/settings/categories", [InventoryCategoryController::class, "index"]);
    Route::post("/settings/categories/main", [InventoryCategoryController::class, "storeMainCategory"]);
    Route::post("/settings/categories/sub", [InventoryCategoryController::class, "storeSubCategory"]);
    Route::get("/retail/dashboard/{storeId}", [RetailDashboardController::class, "index"]);
    Route::post("/retail/sales", [RetailSaleController::class, "store"]);
    Route::post("/retail/sales/{saleId}/pay", [RetailSaleController::class, "pay"]);
    Route::delete("/retail/sales/{saleId}", [RetailSaleController::class, "destroy"]);
    Route::get("/retail/sales/{storeId}", [RetailSaleController::class, "index"]);
    Route::get("/retail/expenses/{storeId}", [RetailExpenseController::class, "index"]);
    Route::post("/retail/expenses", [RetailExpenseController::class, "store"]);
    Route::put("/retail/expenses/{id}", [RetailExpenseController::class, "update"]);
    Route::delete("/retail/expenses/{id}", [RetailExpenseController::class, "destroy"]);
    Route::get("/retail/clients/{storeId}", [RetailClientController::class, "index"]);
    Route::post("/retail/orders", [RetailOrderController::class, "store"]);
    Route::post("/retail/orders/{orderId}/pay", [RetailOrderController::class, "pay"]);
    Route::post("/retail/orders/{orderId}/accept", [RetailOrderController::class, "accept"]);
    Route::delete("/retail/orders/{orderId}", [RetailOrderController::class, "destroy"]);
    Route::get("/retail/orders/{storeId}", [RetailOrderController::class, "index"]);

    // Warehouse � isolated from retail stores
    Route::post("/warehouse/supplies", [WarehouseSupplyController::class, "store"]);
    Route::get("/warehouse/supplies", [WarehouseSupplyController::class, "index"]);
    Route::post("/warehouse/supplies/{supplyId}/pay", [WarehouseSupplyController::class, "pay"]);
    Route::get("/warehouse/stock", [WarehouseStockController::class, "index"]);
    Route::post("/warehouse/stock/adjust", [WarehouseStockController::class, "adjust"]);
    Route::post("/warehouse/stock/seed", [WarehouseStockController::class, "seed"]);
    Route::get("/warehouse/clients", [WarehouseClientController::class, "index"]);
    Route::post("/warehouse/clients", [WarehouseClientController::class, "store"]);
    Route::get("/warehouse/clients/{clientId}/sales", [WarehouseClientController::class, "sales"]);
    Route::get("/warehouse/kv/{key}", [WarehouseKvController::class, "get"]);
    Route::post("/warehouse/kv/{key}", [WarehouseKvController::class, "set"]);

    // Retail KV store � variants, items, stock per store
    Route::get("/retail/kv/{storeId}/{key}", [RetailKvController::class, "get"]);
    Route::post("/retail/kv/{storeId}/{key}", [RetailKvController::class, "set"]);

    // Stores
    Route::get("/stores", [StoreController::class, "index"]);
    Route::post("/stores", [StoreController::class, "store"]);
    Route::put("/stores/{id}", [StoreController::class, "update"]);
    Route::delete("/stores/{id}", [StoreController::class, "destroy"]);

    // Cashiers
    Route::post("/cashiers", [CashierController::class, "store"]);
    Route::delete("/cashiers/{id}", [CashierController::class, "destroy"]);
});


