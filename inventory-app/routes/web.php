<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\MovementController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('welcome');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('/movements', [MovementController::class, 'index'])->name('movements.index');
    Route::get('/movements/products/search', [MovementController::class, 'searchProducts'])->name('movements.products.search');
    Route::post('/movements/in', [MovementController::class, 'storeIn'])->name('movements.store.in');
    Route::post('/movements/out', [MovementController::class, 'storeOut'])->name('movements.store.out');
    Route::post('/movements/adjust', [MovementController::class, 'storeAdjust'])->name('movements.store.adjust');
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/commit', [ImportController::class, 'commit'])->name('import.commit');
    Route::get('/import/error/{token}', [ImportController::class, 'downloadErrors'])->name('import.errors.download');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/auth.php';
