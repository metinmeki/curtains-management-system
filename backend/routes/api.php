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

Route::post("/auth/login", [AuthController::class, "login"]);
Route::post("/auth/cashier-login", [AuthController::class, "cashierLogin"]);
Route::post("/auth/register", [AuthController::class, "register"]);
Route::post("/auth/logout", [AuthController::class, "logout"])->middleware("auth:sanctum");
Route::get("/auth/user", [AuthController::class, "user"])->middleware("auth:sanctum");

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/user", function (Request $request) { return $request->user(); });
    Route::get("/settings/categories", [InventoryCategoryController::class, "index"]);
    Route::post("/settings/categories/main", [InventoryCategoryController::class, "storeMainCategory"]);
    Route::post("/settings/categories/sub", [InventoryCategoryController::class, "storeSubCategory"]);
    Route::get("/retail/dashboard/{storeId}", [RetailDashboardController::class, "index"]);
    Route::post("/retail/sales", [RetailSaleController::class, "store"]);
    Route::post("/retail/sales/{saleId}/pay", [RetailSaleController::class, "pay"]);
    Route::get("/retail/sales/{storeId}", [RetailSaleController::class, "index"]);
    Route::get("/retail/expenses/{storeId}", [RetailExpenseController::class, "index"]);
    Route::post("/retail/expenses", [RetailExpenseController::class, "store"]);
    Route::get("/retail/clients/{storeId}", [RetailClientController::class, "index"]);
    Route::post("/retail/orders", [RetailOrderController::class, "store"]);
    Route::get("/retail/orders/{storeId}", [RetailOrderController::class, "index"]);

    // Warehouse — isolated from retail stores
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
});
