<?php
namespace Core;
class Router {
    protected $routes = [];
    public function get($uri, $callback, $middlewares = []) {
        $this->routes['GET'][$uri] = [
            'callback' => $callback,
            'middlewares' => (array)$middlewares
        ];
    }
    public function post($uri, $callback, $middlewares = []) {
        $this->routes['POST'][$uri] = [
            'callback' => $callback,
            'middlewares' => (array)$middlewares
        ];
    }
    public function dispatch($uri, $method) {
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        $parsedUri = '/' . trim($parsedUri, '/');
        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }
        foreach ($this->routes[$method] as $route => $routeInfo) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $parsedUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $callback = $routeInfo['callback'];
                $middlewares = $routeInfo['middlewares'];

                // ==========================================
                // 🛡️ 新增：执行中间件拦截逻辑
                // ==========================================
                foreach ($middlewares as $mwName) {
                    $mwClass = "\\App\\Middleware\\" . $mwName;
                    if (class_exists($mwClass)) {
                        $middleware = new $mwClass();
                        $middleware->handle(); // 如果不通过，这里会直接 exit()
                    } else {
                        throw new \Exception("中间件 {$mwClass} 不存在");
                    }
                }
                // ==========================================

                // 如果中间件全部放行，才执行真正的 Controller
                if (is_string($callback) && strpos($callback, '@') !== false) {
                    list($controllerName, $methodName) = explode('@', $callback);
                    $controllerClass = "App\\Controllers\\" . $controllerName;

                    // 检查类是否存在
                    if (!class_exists($controllerClass)) {
                        $this->sendNotFound();
                        return;
                    }

                    $controllerInstance = new $controllerClass();

                    // 检查方法是否存在
                    if (!method_exists($controllerInstance, $methodName)) {
                        $this->sendNotFound();
                        return;
                    }

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
        exit;
    }
}
