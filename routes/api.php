<?php

use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\MonthController;
use App\Http\Controllers\API\MonthlyReportController;
use App\Http\Controllers\API\ReportAnalyticsController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\ServiceRequestController;
use App\Http\Controllers\API\YearController;
use App\Http\Controllers\API\YearlyReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json(['message' => 'API test route works!']);
});

// Public authentication routes (no CSRF for API)
Route::post('/login', [AuthController::class, 'login']);

// PUBLIC REPORT ROUTES (No Authentication Required)
// These routes are for public access to published reports
Route::prefix('reports')->group(function () {
    // Years and Months reference data (public)
    Route::get('/years', [YearController::class, 'index']);
    Route::get('/years/{id}', [YearController::class, 'show']);
    Route::get('/years/range', [YearController::class, 'range']);
    Route::get('/years/current', [YearController::class, 'current']);

    Route::get('/months', [MonthController::class, 'index']);
    Route::get('/months/{id}', [MonthController::class, 'show']);
    Route::get('/months/quarter', [MonthController::class, 'byQuarter']);

    // Published Reports Only (public access)
    Route::get('/monthly', [MonthlyReportController::class, 'index']); // Only published reports
    Route::get('/monthly/{id}', [MonthlyReportController::class, 'show']); // Only published reports
    Route::get('/monthly/year/{year}', [MonthlyReportController::class, 'byYear']); // Only published reports

    Route::get('/yearly', [YearlyReportController::class, 'index']); // Only published reports
    Route::get('/yearly/{id}', [YearlyReportController::class, 'show']); // Only published reports
    Route::get('/yearly/year/{year}', [YearlyReportController::class, 'byYear']); // Only published reports

    // Public Analytics (basic overview)
    Route::get('/analytics/overview', [ReportAnalyticsController::class, 'overview']);
    Route::get('/analytics/monthly-completion', [ReportAnalyticsController::class, 'monthlyCompletion']);
});

// AUTHENTICATED ROUTES (Login Required)
Route::middleware(['auth:sanctum', 'token.expiry'])->group(function () {
    // Authentication management
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('/token-status', [AuthController::class, 'tokenStatus']);

    // User info route
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'token_info' => [
                'expires_at' => $request->user()->currentAccessToken()->expires_at?->toISOString(),
                'last_used_at' => $request->user()->currentAccessToken()->last_used_at?->toISOString(),
            ],
        ]);
    });

    // STAFF REPORT ROUTES (Including Draft Reports)
    Route::prefix('reports/staff')->group(function () {
        // All Reports (including drafts) - for authenticated staff
        Route::get('/monthly/all', [MonthlyReportController::class, 'indexAll']); // Including drafts
        Route::get('/yearly/all', [YearlyReportController::class, 'indexAll']); // Including drafts
        Route::get('/monthly/{id}', [MonthlyReportController::class, 'showById']); // Including drafts
        Route::get('/yearly/{id}', [YearlyReportController::class, 'adminShow']); // Including drafts

        // Publishing controls (staff can publish/unpublish)
        Route::post('/monthly/{id}/publish', [MonthlyReportController::class, 'publish']);
        Route::post('/monthly/{id}/unpublish', [MonthlyReportController::class, 'unpublish']);
        Route::post('/yearly/{id}/publish', [YearlyReportController::class, 'publish']);
        Route::post('/yearly/{id}/unpublish', [YearlyReportController::class, 'unpublish']);

        // Detailed Analytics (for staff)
        Route::get('/analytics/dashboard', [ReportAnalyticsController::class, 'dashboard']);
        Route::get('/analytics/missing', [ReportAnalyticsController::class, 'missingReports']);
        Route::get('/analytics/completion', [ReportAnalyticsController::class, 'completionRates']);
        Route::get('/analytics/status', [ReportAnalyticsController::class, 'reportsByStatus']);
    });

    // ADMIN ONLY ROUTES (Admin Role Required)
    Route::middleware(['admin'])->group(function () {
        // News management
        Route::post('/news', [NewsController::class, 'store']);
        Route::put('/news/{news}', [NewsController::class, 'update']);
        Route::delete('/news/{news}', [NewsController::class, 'destroy']);

        // Categories management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Service request management (admin only)
        Route::patch('/service-requests/{id}/status', [ServiceRequestController::class, 'updateStatus']);
        Route::delete('/admin/service-requests/{id}', [ServiceRequestController::class, 'destroy']);
        Route::get('/admin/service-requests', [ServiceRequestController::class, 'adminIndex']);
        Route::get('/admin/service-requests/{id}', [ServiceRequestController::class, 'adminShow']);
        Route::get('/admin/service-requests/{id}/documents/{type}/{filename}', [ServiceRequestController::class, 'serveDocument']);
        Route::get('/test-s3-document-upload', [ServiceRequestController::class, 'testS3DocumentUpload']);

        // Dashboard routes (admin only)
        Route::prefix('admin/dashboard')->group(function () {
            Route::get('/stats', [DashboardController::class, 'stats']);
            Route::get('/customer-growth/{year?}', [DashboardController::class, 'customerGrowthData']);
            Route::get('/recent-news', [DashboardController::class, 'recentNews']);
            Route::get('/recent-reports', [DashboardController::class, 'recentReports']);
        });

        // Report management (CRUD operations)
        Route::prefix('reports/admin')->group(function () {
            // Monthly Reports CRUD
            Route::post('/monthly', [MonthlyReportController::class, 'store']);
            Route::put('/monthly/{id}', [MonthlyReportController::class, 'update']);
            Route::delete('/monthly/{id}', [MonthlyReportController::class, 'destroy']);

            // Yearly Reports CRUD
            Route::post('/yearly', [YearlyReportController::class, 'store']);
            Route::put('/yearly/{id}', [YearlyReportController::class, 'update']);
            Route::delete('/yearly/{id}', [YearlyReportController::class, 'destroy']);
        });

        // Admin utility routes
        Route::delete('/admin/cleanup-tokens', [AuthController::class, 'cleanupExpiredTokens']);
    });
});

// Public news routes
Route::get('/news/{news}', [NewsController::class, 'show']);
Route::get('/news', [NewsController::class, 'index']);

// Test routes for S3 functionality
Route::get('/test-s3-connection', [NewsController::class, 'testS3Connection']);
Route::get('/test-s3-image-upload', [NewsController::class, 'testS3ImageUpload']);

// Public categories routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/statuses', fn () => \App\Models\Status::all());

// Public service request routes
// Route::get('/service-requests', [ServiceRequestController::class, 'index']);
Route::post('/service-requests', [ServiceRequestController::class, 'store']);

// Public search routes
Route::get('/search', [SearchController::class, 'globalSearch']);
