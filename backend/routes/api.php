<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryCategoryController;
use App\Http\Controllers\Api\RetailDashboardController;
use App\Http\Controllers\Api\RetailSaleController;
use App\Http\Controllers\Api\RetailExpenseController;
use App\Http\Controllers\Api\RetailClientController;

Route::post("/auth/login", [AuthController::class, "login"]);
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
    Route::get("/retail/sales/{storeId}", [RetailSaleController::class, "index"]);
    Route::get("/retail/expenses/{storeId}", [RetailExpenseController::class, "index"]);
    Route::post("/retail/expenses", [RetailExpenseController::class, "store"]);
    Route::get("/retail/clients/{storeId}", [RetailClientController::class, "index"]);
});
