<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\MovementController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::resource('products', ProductController::class);

    // Categories
    Route::resource('categories', CategoryController::class);

    // Movements
    Route::resource('movements', MovementController::class)->only(['index']);

    // Import
    Route::get('import', [ImportController::class, 'index'])->name('import.index');
    Route::post('import', [ImportController::class, 'store'])->name('import.store');
    Route::get('import/errors/download', [ImportController::class, 'downloadErrors'])->name('import.errors.download');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // User Management (Admin only)
    Route::middleware('can:access-admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('settings', SettingController::class)->only(['index', 'store']);
        Route::resource('backup', BackupController::class)->only(['index', 'store', 'destroy']);
        Route::resource('audit', AuditController::class)->only(['index']);
    });

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/auth.php';