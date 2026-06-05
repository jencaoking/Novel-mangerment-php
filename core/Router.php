<?php
namespace Core;
class Router {
    protected $routes = [];
    public function get($uri, $callback) {
        $this->routes['GET'][$uri] = $callback;
    }
    public function post($uri, $callback) {
        $this->routes['POST'][$uri] = $callback;
    }
    public function dispatch($uri, $method) {
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        $parsedUri = '/' . trim($parsedUri, '/');
        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }
        foreach ($this->routes[$method] as $route => $callback) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $parsedUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                if (is_string($callback) && strpos($callback, '@') !== false) {
                    list($controllerName, $methodName) = explode('@', $callback);
                    $controllerClass = "App\\Controllers\\" . $controllerName;
                    $controllerInstance = new $controllerClass();
                    return call_user_func_array([$controllerInstance, $methodName], $params);
                }
                if (is_callable($callback)) {
                    return call_user_func_array($callback, $params);
                }
            }
        }
        $this->sendNotFound();
    }
    protected function sendNotFound() {
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>The page you are looking for does not exist.</p>";
    }
}
