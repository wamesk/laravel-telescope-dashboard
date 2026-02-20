<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Wame\LaravelTelescopeDashboard\Http\Controllers\Api\EntriesController;
use Wame\LaravelTelescopeDashboard\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('telescope-dashboard');

Route::prefix('api')->group(function () {
    Route::post('/entries', [EntriesController::class, 'index'])->name('telescope-dashboard.api.entries');
    Route::get('/entries/{uuid}', [EntriesController::class, 'show'])->name('telescope-dashboard.api.entries.show');
    Route::get('/entries/{uuid}/detail', [EntriesController::class, 'showWithBatch'])->name('telescope-dashboard.api.entries.detail');
    Route::get('/filters/{type}', [EntriesController::class, 'filters'])->name('telescope-dashboard.api.filters');
});
