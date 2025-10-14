<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\MovementController;
use App\Http\Controllers\Admin\ProductMovementController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ImportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});
    // Lightweight health endpoint (does not require auth)
    Route::get('/ping', function () {
        return response()->json(['ok' => true, 'time' => now()->toIso8601String()]);
    });

Route::middleware(['web','auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('product-batches/expiring-view', [\App\Http\Controllers\Admin\ProductBatchController::class, 'expiringView'])->name('product-batches.expiring-view');
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products (override store/update to disable CSRF for test posts)
    Route::post('categories/ajax-create', [ProductController::class, 'ajaxCreateCategory'])->name('categories.ajax-create');
    Route::post('products', [ProductController::class, 'store'])
        ->name('products.store')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->name('products.update')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('products/{product}', [ProductController::class, 'update'])
        ->name('products.update')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->name('products.destroy')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::resource('products', ProductController::class)->except(['store', 'update', 'destroy']);
    // Product Batches nested routes
    Route::get('products/{product}/batches', [\App\Http\Controllers\Admin\ProductBatchController::class, 'index'])
        ->name('products.batches.index');
    Route::post('products/{product}/batches', [\App\Http\Controllers\Admin\ProductBatchController::class, 'store'])
        ->name('products.batches.store')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::put('products/{product}/batches/{batch}', [\App\Http\Controllers\Admin\ProductBatchController::class, 'update'])
        ->name('products.batches.update')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('products/{product}/batches/{batch}', [\App\Http\Controllers\Admin\ProductBatchController::class, 'update'])
        ->name('products.batches.update')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::delete('products/{product}/batches/{batch}', [\App\Http\Controllers\Admin\ProductBatchController::class, 'destroy'])
        ->name('products.batches.destroy')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    // Categories
    Route::resource('categories', CategoryController::class);

    // Movements
    Route::resource('movements', MovementController::class)->only(['index']);
    Route::get('movements/products/search', [MovementController::class, 'searchProducts'])->name('movements.products.search');
    Route::post('products/{product}/receive', [ProductMovementController::class, 'storeReceive'])->name('products.receive');
    Route::post('products/{product}/issue', [ProductMovementController::class, 'storeIssue'])->name('products.issue');
    Route::post('products/{product}/adjust', [ProductMovementController::class, 'storeAdjust'])->name('products.adjust');

    // Import
    Route::get('import', [ImportController::class, 'index'])->name('import.index');
    Route::post('import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('import/commit', [ImportController::class, 'commit'])->name('import.commit');
    // Back-compat: legacy link used query parameter ?token=... (keep working for existing links)
    Route::get('import/errors/download', [ImportController::class, 'downloadErrorsLegacy'])
        ->middleware('signed')
        ->name('import.errors.download.legacy');
    Route::get('import/errors/download/{token}', [ImportController::class, 'downloadErrors'])
        ->middleware('signed')
        ->name('import.errors.download');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-all', [NotificationController::class, 'markAll'])->name('notifications.mark-all');
    Route::post('notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    // Alerts actions (JSON) — disable CSRF but keep session/cookies
    Route::post('alerts/mark-read', [\App\Http\Controllers\Admin\AlertController::class, 'markRead'])
        ->name('alerts.mark-read')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('alerts/snooze', [\App\Http\Controllers\Admin\AlertController::class, 'snooze'])
        ->name('alerts.snooze')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    // Pricing routes already defined above; avoid duplicates

    // User Management (Admin only)
    Route::middleware('can:access-admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/test-notification', [SettingController::class, 'testNotification'])->name('settings.test-notification');
    // Allow GET trigger for admins (useful when accessing URL directly)
    Route::get('settings/test-notification', [SettingController::class, 'testNotificationGet'])->name('settings.test-notification.get');
    Route::post('settings/run-scan', [SettingController::class, 'runScan'])->name('settings.run-scan');
    // Allow GET trigger for admins (useful when accessing URL directly)
    Route::get('settings/run-scan', [SettingController::class, 'runScan'])->name('settings.run-scan.get');
        // Backup (index + create and explicit download)
        Route::resource('backup', BackupController::class)->only(['index', 'store']);
        Route::get('backup/{filename}/download', [BackupController::class, 'download'])
            ->where('filename', '.*')
            ->name('backup.download');
        Route::resource('audit', AuditController::class)->only(['index']);
    });

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/expiring', [ReportController::class, 'expiring'])->name('reports.expiring');
    Route::get('reports/expiring-batches', [ReportController::class, 'expiringBatches'])->name('reports.expiring-batches');
    Route::get('reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('reports/valuation', [ReportController::class, 'valuation'])->name('reports.valuation');
    Route::get('product-batches/expiring', [\App\Http\Controllers\Admin\ProductBatchController::class, 'expiring'])->name('product-batches.expiring');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/import-export', [ImportExportController::class, 'index'])->name('import_export.index');
    Route::post('/admin/import-export/preview', [ImportExportController::class, 'preview'])->name('import_export.preview');
});

// Remove auth-only duplication to preserve session for JSON endpoints

require __DIR__.'/auth.php';
