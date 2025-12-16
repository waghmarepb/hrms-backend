<?php

class Router
{
    private $routes = [];
    private $middlewares = [];
    private $prefix = '';
    private $middlewareStack = [];

    public function prefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function middleware($middlewares)
    {
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }
        $this->middlewareStack = array_merge($this->middlewareStack, $middlewares);
        return $this;
    }

    public function group($callback)
    {
        $originalPrefix = $this->prefix;
        $originalMiddleware = $this->middlewareStack;
        
        $callback($this);
        
        $this->prefix = $originalPrefix;
        $this->middlewareStack = $originalMiddleware;
    }

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $fullPath = $this->prefix . $path;
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $this->middlewareStack
        ];
    }

    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        $requestUri = strtok($requestUri, '?');
        
        // Remove base path if exists
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            $pattern = $this->convertToRegex($route['path']);
            
            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Remove full match
                
                // Execute middleware
                foreach ($route['middleware'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    $result = $middlewareInstance->handle();
                    if ($result === false) {
                        return;
                    }
                }
                
                // Execute handler
                if (is_array($route['handler'])) {
                    $controller = new $route['handler'][0]();
                    $method = $route['handler'][1];
                    call_user_func_array([$controller, $method], $matches);
                } else {
                    call_user_func_array($route['handler'], $matches);
                }
                return;
            }
        }

        // No route found
        Response::json([
            'success' => false,
            'message' => 'Route not found'
        ], 404);
    }

    private function convertToRegex($path)
    {
        // Convert {id} to regex capture group
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);
        return '#^' . $pattern . '$#';
    }
}

