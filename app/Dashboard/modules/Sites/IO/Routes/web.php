<?php

use App\Dashboard\Modules\Sites\IO\Http\Controllers\DeployController;
use App\Dashboard\Modules\Sites\IO\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [SiteController::class, 'index'])->name('dashboard');
    Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
    Route::post('sites/refresh-all', [SiteController::class, 'refreshAll'])->name('sites.refresh-all');
    Route::put('sites/{site}', [SiteController::class, 'update'])->name('sites.update');
    Route::post('sites/{site}/refresh', [SiteController::class, 'refresh'])->name('sites.refresh');
    Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
    Route::get('sites/{site}/preview', [SiteController::class, 'preview'])->name('sites.preview');

    Route::get('deploy/tags', [DeployController::class, 'tags'])->name('deploy.tags');
    Route::post('deploy/bulk', [DeployController::class, 'bulk'])
        ->middleware('throttle:5,1')
        ->name('deploy.bulk');
    Route::post('sites/{site}/deploy', [DeployController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('sites.deploy');
});
