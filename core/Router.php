<?php

declare(strict_types=1);

namespace Core;

/**
 * Minimal router for the modular monolith. Each module's routes.php
 * registers "METHOD pattern" => [Controller::class, 'action']. Patterns may
 * contain {param} segments. Dispatch input comes from Request::route()
 * (?r=leases/5), so no mod_rewrite is needed on XAMPP.
 */
final class Router
{
    /** @var array<string, list<array{0: string, 1: callable}>> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable|array $handler): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            // Controllers are dependency-light; instantiate at dispatch time.
            $handler = static fn(array $params): mixed => (new $class())->{$action}($params);
        }
        $this->routes[strtoupper($method)][] = [$pattern, $handler];
    }

    /** Load every enabled module's routes.php (module self-registration). */
    public function loadModules(): void
    {
        foreach (Config::get('modules', []) as $module) {
            $file = dirname(__DIR__) . "/modules/$module/routes.php";
            if (is_file($file)) {
                $register = require $file;
                if (is_callable($register)) {
                    $register($this);
                }
            }
        }
    }

    public function dispatch(string $route): void
    {
        $method = Request::method();
        $route  = trim($route, '/');

        // HEAD is served as GET (monitors and prefetchers send HEAD).
        $table = $this->routes[$method] ?? $this->routes['GET'] ?? [];

        foreach ($table as [$pattern, $handler]) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $route, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler($params);
                return;
            }
        }

        http_response_code(404);
        echo View::render('Dashboard::404', ['title' => 'Not found']);
    }
}
