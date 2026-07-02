<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/{path}', function ($path) {
    $file = public_path($path);
    if (file_exists($file) && !is_dir($file)) {
        return response()->file($file);
    }
    return response()->file(public_path('index.html'));
})->where('path', '.*');
