<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

// Health check endpoint for monitoring (can be protected with middleware)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString()
    ]);
})->middleware('throttle:10,1'); // 10 requests per minute

// CSRF cookie route for SPA authentication
Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);