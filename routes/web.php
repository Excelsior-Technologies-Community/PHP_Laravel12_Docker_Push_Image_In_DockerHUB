<?php

use App\Http\Controllers\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::prefix('system')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Health Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/health',
        [SystemHealthController::class, 'index']
    )->name('system.health');

    /*
    |--------------------------------------------------------------------------
    | Run Health Check
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/health/check',
        [SystemHealthController::class, 'check']
    )->name('system.health.check');

    /*
    |--------------------------------------------------------------------------
    | Clear Logs
    |--------------------------------------------------------------------------
    */

    Route::delete(
        '/health/logs',
        [SystemHealthController::class, 'clearLogs']
    )->name('system.health.logs.clear');

    /*
    |--------------------------------------------------------------------------
    | Cleanup Old Logs
    |--------------------------------------------------------------------------
    */

    Route::delete(
        '/health/logs/cleanup',
        [SystemHealthController::class, 'cleanupLogs']
    )->name('system.health.logs.cleanup');

    /*
    |--------------------------------------------------------------------------
    | Export CSV
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/health/logs/export',
        [SystemHealthController::class, 'exportLogs']
    )->name('system.health.logs.export');

    /*
    |--------------------------------------------------------------------------
    | Health API
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/health/api',
        [SystemHealthController::class, 'apiHealth']
    )->name('system.health.api');
});