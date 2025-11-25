<?php

use App\Http\Controllers\Api\V1\Operator\CompetitionController;
use App\Http\Controllers\Api\V1\Operator\ComplianceController;
use App\Http\Controllers\Api\V1\Operator\DrawController;
use App\Http\Controllers\Api\V1\Operator\EntryController;
use App\Http\Controllers\Api\V1\Operator\WebhookController;
use App\Http\Controllers\Api\ChainVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CaaS Platform API Routes
|--------------------------------------------------------------------------
|
| This file contains all CaaS platform-specific API routes:
| - Operator External API (API key authentication)
| - Internal Dashboard API (JWT authentication)
| - Public read-only API (no authentication)
|
*/

// ============================================================================
// OPERATOR EXTERNAL API (v1)
// ============================================================================
Route::middleware(['api', 'api.key'])
    ->prefix('api/v1/operator')
    ->name('api.v1.operator.')
    ->group(function () {

        // Competition Management
        Route::post('/competitions', [CompetitionController::class, 'store'])->name('competitions.store');
        Route::get('/competitions/{externalId}', [CompetitionController::class, 'show'])->name('competitions.show');
        Route::put('/competitions/{externalId}', [CompetitionController::class, 'update'])->name('competitions.update');
        Route::post('/competitions/{externalId}/publish', [CompetitionController::class, 'publish'])->name('competitions.publish');
        Route::post('/competitions/{externalId}/close', [CompetitionController::class, 'close'])->name('competitions.close');
        Route::get('/competitions/{externalId}/audits', [DrawController::class, 'getAudits'])->name('competitions.audits');
        Route::get('/competitions/{externalId}/stats', [CompetitionController::class, 'stats'])->name('competitions.stats');

        // Entry Management
        Route::post('/competitions/{competitionExternalId}/entries', [EntryController::class, 'store'])->name('entries.store');
        Route::post('/competitions/{competitionExternalId}/free-entries', [EntryController::class, 'storeFreeEntry'])->name('free-entries.store');
        Route::delete('/competitions/{competitionExternalId}/entries/{entryExternalId}', [EntryController::class, 'destroy'])->name('entries.destroy');

        // Draw Management
        Route::post('/competitions/{competitionExternalId}/draws/run', [DrawController::class, 'runDraw'])->name('draws.run');
        Route::get('/draw-events', [DrawController::class, 'getDrawEvents'])->name('draw-events.index');

        // Compliance
        Route::get('/compliance', [ComplianceController::class, 'show'])->name('compliance.show');
        Route::post('/complaints', [ComplianceController::class, 'storeComplaint'])->name('complaints.store');

        // Webhooks
        Route::apiResource('webhooks', WebhookController::class);

        // Chain Verification (Operator API)
        Route::get('/chain/verify', [ChainVerificationController::class, 'verifyOperator'])->name('chain.verify');
        Route::get('/competitions/{externalId}/chain/verify', [ChainVerificationController::class, 'verifyCompetitionOperator'])->name('competitions.chain.verify');
    });

// ============================================================================
// INTERNAL DASHBOARD API
// ============================================================================
require_once __DIR__.'/internal.php';

// ============================================================================
// PUBLIC API (No authentication required)
// ============================================================================
Route::prefix('api/public')->name('api.public.')->group(function () {
    // Public draw audits
    Route::get('/draw-audits', [App\Http\Controllers\DrawAuditController::class, 'publicIndex'])->name('draw-audits.index');
    Route::get('/draw-audits/download', [App\Http\Controllers\DrawAuditController::class, 'downloadJson'])->name('draw-audits.download');
    Route::get('/operators', [App\Http\Controllers\DrawAuditController::class, 'getOperators'])->name('operators.index');

    // Public Chain Verification
    Route::get('/chain/verify', [ChainVerificationController::class, 'verifyPublic'])->name('chain.verify');
    Route::get('/chain/verify/{competitionId}', [ChainVerificationController::class, 'verifyCompetitionPublic'])->name('chain.verify.competition');
});
// PUBLIC READ-ONLY API (v1)
// ============================================================================
Route::prefix('api/v1')->name('api.v1.')->group(function () {

    // Public Audit Pages
    Route::get('/raffles/{uuid}/audit', [\App\Http\Controllers\DrawAuditController::class, 'showJson'])->name('raffles.audit');
    Route::get('/raffles/{uuid}/entries/stats', [\App\Http\Controllers\Api\RaffleStatsController::class, 'show'])->name('raffles.stats');
    Route::get('/raffles/{uuid}/odds', [\App\Http\Controllers\Api\RaffleStatsController::class, 'odds'])->name('raffles.odds');
});
