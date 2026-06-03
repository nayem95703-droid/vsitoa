<?php

namespace Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $basePath = '';

    /**
     * Set base path for routing
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/');
    }

    /**
     * Add GET route
     */
    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Add POST route
     */
    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Add route for any method
     */
    public function any(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
        $this->addRoute('POST', $path, $handler, $middlewares);
        $this->addRoute('PUT', $path, $handler, $middlewares);
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Add route
     */
    private function addRoute(string $method, string $path, callable|array $handler, array $middlewares): void
    {
        [$pattern, $paramNames] = $this->convertPathToRegex($path);
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Convert path to regex pattern
     */
    private function convertPathToRegex(string $path): array
    {
        $paramNames = [];

        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            $regex = $m[2] ?? '[^/]+';
            return '(?P<' . $m[1] . '>' . $regex . ')';
        }, $path);

        $pattern = str_replace('/', '\/', $pattern);
        return ['/^' . $pattern . '$/', $paramNames];
    }

    /**
     * Dispatch request
     */
    public function dispatch(Request $request, Response $response): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();
        
        // Strip base path from request path
        if ($this->basePath !== '' && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
            $path = $path === '' ? '/' : $path;
        } elseif ($this->basePath !== '') {
            $rootBasePath = rtrim(str_replace('\\', '/', (string) dirname($this->basePath)), '/');
            if ($rootBasePath !== '' && $rootBasePath !== '.' && strpos($path, $rootBasePath) === 0) {
                $path = substr($path, strlen($rootBasePath));
                $path = $path === '' ? '/' : $path;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $routeParams = [];
                foreach (($route['paramNames'] ?? []) as $name) {
                    if (array_key_exists($name, $matches)) {
                        $routeParams[$name] = $matches[$name];
                    }
                }

                $request->setRouteParams($routeParams);
                
                // Execute middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $result = $this->executeMiddleware($middleware, $request, $response);
                    if ($result === false) {
                        return; // Middleware stopped the request
                    }
                }

                // Execute handler
                $paramValues = [];
                foreach (($route['paramNames'] ?? []) as $name) {
                    $paramValues[] = $routeParams[$name] ?? null;
                }
                $this->executeHandler($route['handler'], $request, $response, $paramValues);
                return;
            }
        }

        // 404 Not Found
        $this->handle404($request, $response);
    }

    /**
     * Execute middleware
     */
    private function executeMiddleware(callable|array $middleware, Request $request, Response $response): bool
    {
        if (is_callable($middleware)) {
            return call_user_func($middleware, $request, $response);
        }

        if (is_array($middleware)) {
            [$class, $method] = $middleware;
            if (class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, $method)) {
                    return $instance->$method($request, $response);
                }
            }
        }

        return true;
    }

    /**
     * Execute route handler
     */
    private function executeHandler(callable|array $handler, Request $request, Response $response, array $params): void
    {
        if (is_callable($handler)) {
            try {
                $callable = \Closure::fromCallable($handler);
                $ref = new \ReflectionFunction($callable);
                $baseArgs = [$request, $response];
                $extraMax = max(0, $ref->getNumberOfParameters() - count($baseArgs));
                $args = array_merge($baseArgs, array_slice($params, 0, $extraMax));
                call_user_func_array($handler, $args);
            } catch (\Throwable $e) {
                if (class_exists(\Core\Logger::class)) {
                    \Core\Logger::error('Route handler execution failed', [
                        'handler' => $handler,
                        'error' => $e->getMessage(),
                        'path' => $request->getPath(),
                        'method' => $request->getMethod()
                    ]);
                }
            }
            return;
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;
            
            if (class_exists($class)) {
                $instance = new $class();

                try {
                    $ref = new \ReflectionMethod($instance, $method);
                    $baseArgs = [$request, $response];
                    $extraMax = max(0, $ref->getNumberOfParameters() - count($baseArgs));
                    $args = array_merge($baseArgs, array_slice($params, 0, $extraMax));
                    call_user_func_array([$instance, $method], $args);
                    return;
                } catch (\Throwable $e) {
                    if (class_exists(\Core\Logger::class)) {
                        \Core\Logger::error('Route handler execution failed', [
                            'handler' => $handler,
                            'error' => $e->getMessage(),
                            'path' => $request->getPath(),
                            'method' => $request->getMethod()
                        ]);
                    }
                }
            }
        }

        if (class_exists(\Core\Logger::class)) {
            \Core\Logger::error('Invalid route handler', [
                'handler' => $handler,
                'path' => $request->getPath(),
                'method' => $request->getMethod()
            ]);
        }

        $isApiPath = preg_match('#(^|/)(api)(/|$)#', $request->getPath()) === 1;
        if ($request->isAjax() || $isApiPath) {
            $response->json([
                'success' => false,
                'message' => 'Invalid route handler'
            ], 500);
            return;
        }

        http_response_code(500);
        include ROOT_PATH . '/views/errors/500.php';
    }

    /**
     * Handle 404 Not Found
     */
    private function handle404(Request $request, Response $response): void
    {
        if (class_exists(\Core\Logger::class)) {
            \Core\Logger::warning('404 Not Found', [
                'method' => $request->getMethod(),
                'url' => $request->fullUrl(),
                'base_path' => $this->basePath
            ]);
        }

        if ($request->isAjax()) {
            $response->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        } else {
            http_response_code(404);
            include ROOT_PATH . '/views/errors/404.php';
        }
    }

    /**
     * Add global middleware
     */
    public function addMiddleware(callable|array $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Get all routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Generate URL from route name and parameters
     */
    public function url(string $name, array $params = []): string
    {
        // This would require named routes implementation
        // For now, return the path as-is
        return $name;
    }

    /**
     * Redirect to another URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }
}
