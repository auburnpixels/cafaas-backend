<?php

use App\Http\Controllers\Internal\Auth\AuthController;
use App\Http\Controllers\Internal\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Internal\Regulator\DashboardController as RegulatorDashboardController;
use App\Http\Controllers\Api\ChainVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Dashboard API Routes
|--------------------------------------------------------------------------
|
| JWT-protected API for internal dashboards (operator and regulator views).
| These routes are for the Next.js frontend to consume.
|
*/

Route::prefix('internal')->name('internal.')->group(function () {

    // ========================================================================
    // Authentication Routes (no middleware)
    // ========================================================================
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    });

    // ========================================================================
    // Protected Routes (require JWT)
    // ========================================================================
    Route::middleware(['api', 'jwt.auth'])->group(function () {

        // Auth endpoints
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
        });

        // ====================================================================
        // Operator Dashboard Routes
        // ====================================================================
        Route::prefix('operator')->name('operator.')->group(function () {
            Route::get('/me', [OperatorDashboardController::class, 'me'])->name('me');
            Route::put('/details', [OperatorDashboardController::class, 'updateDetails'])->name('details.update');
            Route::get('/competitions', [OperatorDashboardController::class, 'competitions'])->name('competitions');
            Route::get('/competitions/{uuid}/audits', [OperatorDashboardController::class, 'competitionAudits'])->name('competitions.audits');
            Route::get('/competitions/{uuid}/events', [OperatorDashboardController::class, 'competitionEvents'])->name('competitions.events');
            Route::get('/draw-events', [OperatorDashboardController::class, 'drawEvents'])->name('draw-events');
            Route::get('/draw-events/filters', [OperatorDashboardController::class, 'drawEventsFilters'])->name('draw-events.filters');
            Route::get('/compliance-summary', [OperatorDashboardController::class, 'complianceSummary'])->name('compliance-summary');
            Route::get('/complaints', [OperatorDashboardController::class, 'complaints'])->name('complaints');
            Route::get('/draw-audits', [OperatorDashboardController::class, 'drawAudits'])->name('draw-audits');

            // API Key Management
            Route::get('/api-keys', [OperatorDashboardController::class, 'apiKeys'])->name('api-keys.index');
            Route::post('/api-keys', [OperatorDashboardController::class, 'createApiKey'])->name('api-keys.store');
            Route::delete('/api-keys/{keyId}', [OperatorDashboardController::class, 'revokeApiKey'])->name('api-keys.destroy');

            // Chain Verification (Internal Operator)
            Route::get('/chain/verify', [ChainVerificationController::class, 'verifyOperator'])->name('chain.verify');
            Route::get('/competitions/{uuid}/chain/verify', function($uuid) {
                // Convert UUID to competition and call verification
                $competition = \App\Models\Competition::where('uuid', $uuid)->firstOrFail();
                return app(ChainVerificationController::class)->verifyCompetitionOperator($competition->external_id);
            })->name('competitions.chain.verify');
        });

        // ====================================================================
        // Regulator Dashboard Routes (restricted to regulators only)
        // ====================================================================
        Route::middleware(['regulator'])
            ->prefix('regulator')
            ->name('regulator.')
            ->group(function () {
                Route::get('/operators', [RegulatorDashboardController::class, 'operators'])->name('operators');
                Route::get('/operators/{id}/competitions', [RegulatorDashboardController::class, 'operatorCompetitions'])->name('operators.competitions');
                Route::get('/competitions/{uuid}/audits', [RegulatorDashboardController::class, 'competitionAudits'])->name('competitions.audits');
                Route::get('/competitions/{uuid}/events', [RegulatorDashboardController::class, 'competitionEvents'])->name('competitions.events');
                Route::get('/compliance-dashboard', [RegulatorDashboardController::class, 'complianceDashboard'])->name('compliance-dashboard');
                Route::post('/verify-integrity', [RegulatorDashboardController::class, 'verifyIntegrity'])->name('verify-integrity');

                // Chain Verification (Regulator)
                Route::get('/chain/verify', [ChainVerificationController::class, 'verifyRegulator'])->name('chain.verify');
                Route::get('/chain/verify/{competitionId}', [ChainVerificationController::class, 'verifyCompetitionRegulator'])->name('chain.verify.competition');
            });
    });
});
