<?php

use App\Http\Controllers\DrawAuditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CaaS Platform Web Routes
|--------------------------------------------------------------------------
|
| Minimal web routes for the CaaS platform. The primary frontend is Next.js.
| These routes are mainly for backwards compatibility with public audit pages.
|
*/

// Public Audit Pages (HTML views - optional, Next.js handles this)
Route::get('/draw-audit', [DrawAuditController::class, 'index'])->name('draw-audit.index');
Route::get('/draw-audit/{uuid}', [DrawAuditController::class, 'show'])->name('draw-audit.show');

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');

// Redirect root to Next.js frontend
Route::get('/', function () {
    return response()->json([
        'name' => 'CaaS Platform API',
        'version' => '1.0.0',
        'message' => 'Welcome to the CaaS Platform API',
        'documentation' => config('app.url').'/api/documentation',
        'frontend' => config('app.frontend_url', 'http://localhost:3000'),
    ]);
})->name('homepage');
