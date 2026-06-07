<?php
namespace Core;
class Router {
    protected $routes = [];
    protected $container;
    public function __construct(\Core\Container $container = null) {
        $this->container = $container ?: new \Core\Container();
    }

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
        if ($parsedUri === false || $parsedUri === null) {
            $this->sendNotFound();
            return;
        }
        $parsedUri = '/' . trim($parsedUri, '/');
        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }
        foreach ($this->routes[$method] as $route => $routeInfo) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $parsedUri, $matches)) {
                // 过滤出命名捕获组
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); 
                
                $callback = $routeInfo['callback'];
                $middlewares = $routeInfo['middlewares'];

                // ==========================================
                // 🛡️ 新增：执行中间件拦截逻辑
                // ==========================================
                foreach ($middlewares as $mwName) {
                    $mwClass = "\\App\\Middleware\\" . $mwName;
                    if (class_exists($mwClass)) {
                        $middleware = $this->container->get($mwClass);
                        if (!$middleware->handle()) {
                            // 中间件验证失败，由中间件内部处理响应，直接终止
                            return;
                        }
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

                    $controllerInstance = $this->container->get($controllerClass);

                    // 检查方法是否存在
                    if (!method_exists($controllerInstance, $methodName)) {
                        $this->sendNotFound();
                        return;
                    }

                    // 使用反射按参数名注入
                    $resolvedParams = $this->resolveMethodParams($controllerInstance, $methodName, $params);
                    return call_user_func_array([$controllerInstance, $methodName], $resolvedParams);
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

    /**
     * 使用反射按参数名注入参数
     * @param object $instance Controller 实例
     * @param string $method 方法名
     * @param array $namedParams 具名参数数组
     * @return array 按方法参数顺序排列的参数数组
     */
    protected function resolveMethodParams($instance, $method, $namedParams) {
        $reflection = new \ReflectionMethod($instance, $method);
        $resolved = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $namedParams)) {
                $resolved[] = $namedParams[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $resolved[] = $param->getDefaultValue();
            } else {
                throw new \Exception("路由参数 {$name} 未提供且无默认值");
            }
        }
        return $resolved;
    }
}
