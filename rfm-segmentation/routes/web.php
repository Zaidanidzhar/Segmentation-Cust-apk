<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\ClusterProcessController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Public Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingPageController::class, 'index'])->name('landing');

/*
|--------------------------------------------------------------------------
| Auth Routes (dari Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Dashboard Route — Terproteksi
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'admin'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin Routes — Terproteksi (Butuh Login + Role Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware(['auth'])
            ->name('dashboard');

        // Form unggah dataset mentah
        Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
        Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
        Route::post('/reset-data', [UploadController::class, 'resetData'])->name('reset-data');

        Route::get('/process', [ClusterProcessController::class, 'index'])->name('process.index');
        Route::post('/experiment-cluster', [ClusterProcessController::class, 'experiment'])->name('experiment-cluster');
        Route::post('/process-cluster', [ClusterProcessController::class, 'process'])->name('process-cluster');
    });