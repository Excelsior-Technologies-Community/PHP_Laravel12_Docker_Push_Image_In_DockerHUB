<?php

use App\Http\Controllers\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::prefix('system')->group(function () {

    Route::get(
        '/health',
        [SystemHealthController::class, 'index']
    )->name('system.health');

    Route::post(
        '/health/check',
        [SystemHealthController::class, 'check']
    )->name('system.health.check');

    Route::delete(
        '/health/logs',
        [SystemHealthController::class, 'clearLogs']
    )->name('system.health.logs.clear');

    Route::get(
        '/health/api',
        [SystemHealthController::class, 'apiHealth']
    )->name('system.health.api');

});