<?php
namespace App\Support;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $path, callable $handler, ?string $ability = null): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'ability');
    }

    public function dispatch(): void
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $requestUri) {
                if ($route['ability']) {
                    Gate::require($route['ability']);
                }

                echo call_user_func($route['handler']);
                return;
            }
        }

        http_response_code(404);
        echo 'ไม่พบหน้าที่ต้องการ';
    }
}
