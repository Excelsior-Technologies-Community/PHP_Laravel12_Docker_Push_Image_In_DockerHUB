<?php

use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\SystemHealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('system.health');
});

/*
|--------------------------------------------------------------------------
| 1. Deployment History & Release Pipeline
|--------------------------------------------------------------------------
*/
Route::prefix('deployments')->name('deployments.')->group(function () {
    Route::get('/', [DeploymentController::class, 'index'])->name('index');
    Route::post('/', [DeploymentController::class, 'store'])->name('store');
    Route::get('/{id}', [DeploymentController::class, 'show'])->name('show');
    Route::post('/{id}/rollback', [DeploymentController::class, 'rollback'])->name('rollback');
});

/*
|--------------------------------------------------------------------------
| 2. Live Server Health & Telemetry
|--------------------------------------------------------------------------
*/
Route::prefix('system')->name('system.')->group(function () {
    Route::get('/health', [SystemHealthController::class, 'index'])->name('health');
    Route::post('/health/check', [SystemHealthController::class, 'check'])->name('health.check');
    Route::post('/health/alert/dispatch', [SystemHealthController::class, 'dispatchAlert'])->name('health.alert.dispatch');
    Route::delete('/health/logs', [SystemHealthController::class, 'clearLogs'])->name('health.logs.clear');
    Route::delete('/health/logs/cleanup', [SystemHealthController::class, 'cleanupLogs'])->name('health.logs.cleanup');
    Route::get('/health/logs/export', [SystemHealthController::class, 'exportLogs'])->name('health.logs.export');
    Route::get('/health/api', [SystemHealthController::class, 'apiHealth'])->name('health.api');
});

/*
|--------------------------------------------------------------------------
| 3. Multi-Source Log Viewer
|--------------------------------------------------------------------------
*/
Route::prefix('logs')->name('logs.')->group(function () {
    Route::get('/', [LogViewerController::class, 'index'])->name('index');
    Route::post('/clear', [LogViewerController::class, 'clear'])->name('clear');
    Route::get('/download', [LogViewerController::class, 'download'])->name('download');
});