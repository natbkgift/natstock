<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\MovementController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('welcome');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('/movements', [MovementController::class, 'index'])->name('movements.index');
    Route::get('/movements/products/search', [MovementController::class, 'searchProducts'])->name('movements.products.search');
    Route::post('/movements/in', [MovementController::class, 'storeIn'])->middleware('throttle:60,1')->name('movements.store.in');
    Route::post('/movements/out', [MovementController::class, 'storeOut'])->middleware('throttle:60,1')->name('movements.store.out');
    Route::post('/movements/adjust', [MovementController::class, 'storeAdjust'])->middleware('throttle:60,1')->name('movements.store.adjust');
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/preview', [ImportController::class, 'preview'])->middleware('throttle:5,60')->name('import.preview');
    Route::post('/import/commit', [ImportController::class, 'commit'])->middleware('throttle:5,60')->name('import.commit');
    Route::get('/import/error/{token}', [ImportController::class, 'downloadErrors'])->name('import.errors.download');
    Route::middleware('can:access-staff')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/mark-all', [NotificationController::class, 'markAll'])->name('notifications.mark-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    });
    Route::middleware('can:access-admin')->group(function () {
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
        Route::get('/backup/{filename}', [BackupController::class, 'download'])->where('filename', '[A-Za-z0-9_.\-]+')->name('backup.download');
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-notification', [SettingController::class, 'testNotification'])->name('settings.test-notification');
        Route::post('/settings/run-scan', [SettingController::class, 'runScan'])->name('settings.run-scan');
    });
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/expiring', [ReportController::class, 'expiring'])->name('expiring');
        Route::get('/low-stock', [ReportController::class, 'lowStock'])->name('low-stock');
        Route::get('/valuation', [ReportController::class, 'valuation'])->name('valuation');
    });
});

require __DIR__.'/auth.php';
