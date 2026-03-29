<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    private function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
            'pattern'    => $this->buildPattern($path),
        ];
    }

    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function match(string $method, string $uri): ?array
    {
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [
                    'controller' => $route['controller'],
                    'action'     => $route['action'],
                    'params'     => $params,
                ];
            }
        }
        return null;
    }

    public function dispatch(string $uri, string $method): void
    {
        $match = $this->match($method, $uri);

        if ($match === null) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $controllerClass = 'App\\Controllers\\' . $match['controller'];
        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo '500 - Controller bulunamadı: ' . htmlspecialchars($match['controller']);
            return;
        }

        $controller = new $controllerClass();
        $action = $match['action'];

        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo '500 - Action bulunamadı: ' . htmlspecialchars($action);
            return;
        }

        call_user_func_array([$controller, $action], $match['params']);
    }
}
