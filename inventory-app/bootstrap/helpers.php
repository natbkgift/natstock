<?php
use App\Support\Auth;
use App\Support\Config;
use App\Support\Database;

function app_path(string $path = ''): string
{
    return __DIR__.'/../app'.($path ? '/'.$path : '');
}

function base_path(string $path = ''): string
{
    return __DIR__.'/..'.($path ? '/'.$path : '');
}

function resource_path(string $path = ''): string
{
    return __DIR__.'/../resources'.($path ? '/'.$path : '');
}

function storage_path(string $path = ''): string
{
    return __DIR__.'/../storage'.($path ? '/'.$path : '');
}

function asset(string $path): string
{
    return '/'.$path;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function auth(): Auth
{
    return Auth::instance();
}

function db(): PDO
{
    return Database::connection();
}

function redirect(string $url): void
{
    header('Location: '.$url);
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash(string $key, mixed $value = null)
{
    if ($value === null) {
        $data = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $data;
    }

    $_SESSION['_flash'][$key] = $value;
}

function errors(?string $field = null): mixed
{
    $errors = $_SESSION['_errors'] ?? [];
    if ($field === null) {
        return $errors;
    }

    return $errors[$field] ?? null;
}

function csrf_token(): string
{
    if (!isset($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['_token'];
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!$token || !isset($_SESSION['_token']) || !hash_equals($_SESSION['_token'], $token)) {
        http_response_code(419);
        echo 'ไม่สามารถยืนยันความปลอดภัยของคำขอได้';
        exit;
    }
}

function view(string $name, array $data = []): void
{
    extract($data);
    $viewPath = resource_path('views/'.$name.'.php');
    if (!file_exists($viewPath)) {
        http_response_code(500);
        echo 'ไม่พบมุมมอง';
        exit;
    }
    include $viewPath;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_currency(float $value): string
{
    return number_format($value, 2, '.', ',');
}

function paginate(array $items, int $perPage, int $page): array
{
    $total = count($items);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($pages, $page));
    $offset = ($page - 1) * $perPage;
    $slice = array_slice($items, $offset, $perPage);

    return [
        'data' => $slice,
        'total' => $total,
        'pages' => $pages,
        'page' => $page,
    ];
}
