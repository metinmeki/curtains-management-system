<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

// The URL Abdulla's frontend will call
Route::get('/retail/dashboard/{store_id}', [DashboardController::class, 'index']);