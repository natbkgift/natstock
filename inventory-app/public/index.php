<?php
session_start();
require __DIR__.'/../bootstrap/autoload.php';

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockMovementController;
use App\Support\Config;
use App\Support\Database;
use App\Support\Gate;
use App\Support\Router;

Config::load();
Database::connection();

$router = new Router();

if (!auth()->check() && !in_array(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), ['/login', '/logout'])) {
    if ($_SERVER['REQUEST_URI'] !== '/login') {
        header('Location: /login');
        exit;
    }
}

$authController = new AuthController();
$dashboard = new DashboardController();
$category = new CategoryController();
$product = new ProductController();
$movement = new StockMovementController();
$import = new ImportController();
$report = new ReportController();

$router->add('GET', '/login', [$authController, 'showLogin']);
$router->add('POST', '/login', [$authController, 'login']);
$router->add('POST', '/logout', [$authController, 'logout'], 'view-dashboard');

$router->add('GET', '/admin/dashboard', [$dashboard, 'index'], 'view-dashboard');

$router->add('GET', '/admin/categories', [$category, 'index'], 'view-categories');
$router->add('GET', '/admin/categories/create', [$category, 'create'], 'manage-categories');
$router->add('POST', '/admin/categories', [$category, 'store'], 'manage-categories');
$router->add('GET', '/admin/categories/edit', [$category, 'edit'], 'manage-categories');
$router->add('POST', '/admin/categories/update', [$category, 'update'], 'manage-categories');
$router->add('POST', '/admin/categories/delete', [$category, 'destroy'], 'manage-categories');

$router->add('GET', '/admin/products', [$product, 'index'], 'view-products');
$router->add('GET', '/admin/products/create', [$product, 'create'], 'manage-products');
$router->add('POST', '/admin/products', [$product, 'store'], 'manage-products');
$router->add('GET', '/admin/products/edit', [$product, 'edit'], 'manage-products');
$router->add('POST', '/admin/products/update', [$product, 'update'], 'manage-products');
$router->add('POST', '/admin/products/delete', [$product, 'destroy'], 'delete-products');

$router->add('GET', '/admin/movements', [$movement, 'index'], 'view-stock');
$router->add('GET', '/admin/movements/create', [$movement, 'create'], 'manage-stock');
$router->add('POST', '/admin/movements', [$movement, 'store'], 'manage-stock');

$router->add('GET', '/admin/import', [$import, 'index'], 'manage-import');
$router->add('POST', '/admin/import/preview', [$import, 'preview'], 'manage-import');
$router->add('POST', '/admin/import/process', [$import, 'process'], 'manage-import');
$router->add('GET', '/admin/import/result', [$import, 'result'], 'manage-import');
$router->add('GET', '/admin/import/errors', [$import, 'downloadErrors'], 'manage-import');

$router->add('GET', '/admin/reports/expiring', [$report, 'expiring'], 'view-reports');
$router->add('GET', '/admin/reports/low-stock', [$report, 'lowStock'], 'view-reports');
$router->add('GET', '/admin/reports/stock-value', [$report, 'stockValue'], 'view-reports');

$router->dispatch();
